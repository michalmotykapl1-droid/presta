<?php

require_once (dirname(__FILE__) . '/../../x13allegro.php');

use x13allegro\Exception\ModuleException;

final class AdminXAllegroOrderMainController extends XAllegroController
{
    public function ajaxProcessSendFulfilmentStatus()
    {
        $orderId = (int)Tools::getValue('orderId');
        $statusFulfillment = Tools::getValue('statusFulfillment', '');

        $result = XAllegroSyncFulfillmentStatus::sendFulfillmentStatus($orderId, $statusFulfillment, true);

        if ($result['status']) {
            if ($result['revisionReload']) {
                $this->module->sessionMessages->warnings($this->l('Zaktualizowano dane odnośnie zamówienia Allegro, możesz teraz zmienić jego status.'));
            } else {
                $this->module->sessionMessages->confirmations($this->l('Pomyślnie zaktualizowano status Allegro.'));
            }

            die(json_encode(array_merge($result, ['redirectLink' => $this->getRedirectLink($orderId)])));
        }

        die(json_encode($result));
    }

    public function ajaxProcessUploadInvoiceFile()
    {
        $invoiceType = Tools::getValue('invoiceType');
        $order = new Order((int)Tools::getValue('orderId'));

        try {
            if (!Validate::isLoadedObject($order)) {
                throw new UnexpectedValueException('Nie można znaleźć zamówienia w bazie danych.');
            }

            if ($invoiceType === 'file') {
                if (empty($_FILES['invoiceFile'])) {
                    throw new UnexpectedValueException('Nie załączono pliku do przesłania.');
                }
                // use ImageManager because there is no other class in PrestaShop to validate uploaded file
                if (!ImageManager::isCorrectImageFileExt($_FILES['invoiceFile']['name'], ['pdf'])
                    || $_FILES['invoiceFile']['type'] !== 'application/pdf'
                ) {
                    throw new UnexpectedValueException('Przesłany plik nie jest dokumentem PDF.');
                }
                if ((int)filesize($_FILES['invoiceFile']['tmp_name']) > 3 * 1024 * 1024) {
                    throw new UnexpectedValueException('Przesłany plik jest większy niż limit 3MB.');
                }

                $invoiceFile = file_get_contents($_FILES['invoiceFile']['tmp_name']);
                $invoiceFileName = $_FILES['invoiceFile']['name'];
                $invoiceNumber = Tools::getValue('invoiceNumber', '');
            }
            else if ($invoiceType === 'prestashop') {
                $prestaShopInvoice = XAllegroSyncInvoice::getPrestaShopInvoice($order, $this->context);

                $invoiceFile = $prestaShopInvoice['invoiceFile'];
                $invoiceFileName = $prestaShopInvoice['invoiceFileName'];
                $invoiceNumber = $prestaShopInvoice['invoiceNumber'];
            }
            else {
                throw new UnexpectedValueException('Wybrano nieprawidłowy typ dokumentu do wysłania.');
            }

            if (!XAllegroSyncInvoice::uploadOrderInvoice($order->id, $invoiceFile, $invoiceFileName, $invoiceNumber)) {
                throw new UnexpectedValueException('Zamówienie nie jest powiązane z modułem Allegro.');
            }

            $this->module->sessionMessages->confirmations($this->l('Przesłano dowód zakupu do Allegro.'));

            die(json_encode([
                'result' => true,
                'redirectLink' => $this->getRedirectLink($order->id)
            ]));
        }
        catch (Exception $ex) {
            die(json_encode([
                'result' => false,
                'message' => ($ex instanceof ModuleException ? (string)$ex : $ex->getMessage())
            ]));
        }
    }

    /**
     * @param int $orderId
     * @return string
     */
    private function getRedirectLink($orderId)
    {
        $params = [
            'vieworder' => 1,
            'id_order' => $orderId
        ];

        if (version_compare(_PS_VERSION_, '1.7.7.0', '>=')) {
            return $this->context->link->getAdminLink('AdminOrders', true, $params);
        }

        return $this->context->link->getAdminLink('AdminOrders') . '&' . http_build_query($params);
    }
}
