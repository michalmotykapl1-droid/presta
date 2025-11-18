<?php

use x13allegro\Api\Model\Order\Invoice;
use x13allegro\Api\XAllegroApi;
use x13allegro\Component\Logger\Log;
use x13allegro\Component\Logger\LogType;
use x13allegro\Exception\ModuleException;
use x13allegro\Json\JsonMapBuilder;

final class XAllegroSyncInvoice
{
    /**
     * @param int $orderId
     * @param string $invoiceFile
     * @param string $invoiceFileName
     * @param string $invoiceNumber
     * @return bool
     * @throws UnexpectedValueException|ModuleException
     */
    public static function uploadOrderInvoice($orderId, $invoiceFile, $invoiceFileName, $invoiceNumber = '')
    {
        $allegroOrder = XAllegroOrder::getByOrderId($orderId);
        if (!$allegroOrder) {
            return false;
        }

        /** @var Invoice $invoice */
        $invoice = (new JsonMapBuilder('Invoice'))->map(new Invoice());
        $invoice->invoiceNumber = $invoiceNumber;
        $invoice->file->name = $invoiceFileName;

        $api = new XAllegroApi(new XAllegroAccount($allegroOrder->id_xallegro_account));
        $resourceInvoices = $api->order()->checkoutForms($allegroOrder->checkout_form)->invoices();

        $resultInvoice = $resourceInvoices->createInvoiceFile($invoice);
        $resourceInvoices->uploadInvoiceFile($resultInvoice->id, $invoiceFile);

        Log::instance()
            ->account($api->getAccount()->id)
            ->order($orderId)
            ->logDatabase()
            ->info(LogType::ORDER_INVOICE_UPLOAD(), [
                'checkoutForm.id' => $allegroOrder->checkout_form,
                'invoice.id' => $resultInvoice->id,
                'invoice.file.name' => $invoiceFileName
            ]);

        return true;
    }

    /**
     * @param Order $order
     * @param Context $context
     * @return array
     * @throws UnexpectedValueException|PrestaShopException
     */
    public static function getPrestaShopInvoice(Order $order, Context $context)
    {
        /** @var OrderInvoice[] $orderInvoiceList */
        $orderInvoiceList = $order->getInvoicesCollection()->getResults();

        if (empty($orderInvoiceList)) {
            throw new UnexpectedValueException('Brak dokumentów wygenerowanych przez PrestaShop.');
        }

        /** @see src/Adapter/PDF/OrderInvoicePdfGenerator.php */
        Hook::exec('actionPDFInvoiceRender', ['order_invoice_list' => $orderInvoiceList]);

        $pfd = new PDF($orderInvoiceList, PDF::TEMPLATE_INVOICE, $context->smarty);
        $invoiceFile = $pfd->render(false);

        return [
            'invoiceFile' => $invoiceFile,
            'invoiceFileName' => $pfd->filename,
            'invoiceNumber' => $orderInvoiceList[0]->getInvoiceNumberFormatted($context->language->id)
        ];
    }
}
