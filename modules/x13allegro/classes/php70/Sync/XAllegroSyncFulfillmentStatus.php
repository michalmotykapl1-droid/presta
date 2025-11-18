<?php

use x13allegro\Api\Model\Order\Enum\FulfillmentStatus;
use x13allegro\Api\Model\Order\Fulfillment;
use x13allegro\Api\Model\Order\Enum\CheckoutFormStatus;
use x13allegro\Component\Logger\Log;
use x13allegro\Component\Logger\LogType;
use x13allegro\Json\JsonMapBuilder;
use x13allegro\SyncManager\Order\OrderController;

final class XAllegroSyncFulfillmentStatus extends XAllegroSync
{
    /**
     * @param int $orderId
     * @param string $fulfillmentStatus
     * @param bool $ajaxController
     * @return array|false
     */
    public static function sendFulfillmentStatus($orderId, $fulfillmentStatus, $ajaxController = false)
    {
        $error = '';
        $order = new Order($orderId);
        /** @var XAllegroOrder $allegroOrder */
        $allegroOrder = XAllegroOrder::getByOrderId($order->id);

        if (!Validate::isLoadedObject($order)) {
            $error = 'Nie można znaleźć zamówienia w bazie danych.';
        }
        else if (!$allegroOrder) {
            $error = 'To zamówienie nie jest powiązane z Allegro.';
        }
        else if ($allegroOrder->fulfillment_status === $fulfillmentStatus) {
            $error = 'To zamówienie posiada już status: ' . FulfillmentStatus::{$fulfillmentStatus}()->getValue();
        }
        else if (in_array($fulfillmentStatus, FulfillmentStatus::getNotAllowedManually())) {
            $error = 'Status "' . FulfillmentStatus::{$fulfillmentStatus}()->getValue() . '" nie może być ustawiony ręcznie przez sprzedawcę.';
        }
        else if (!self::changeAccount($allegroOrder->id_xallegro_account)) {
            $error = 'Wystąpił błąd podczas połączenia z API Allegro.';
        }

        if (!empty($error)) {
            Log::instance()
                ->order($order->id)
                ->error(LogType::ORDER_FULFILLMENT_STATUS_SEND(), $error);

            if ($ajaxController) {
                return [
                    'status' => false,
                    'orderId' => $order->id,
                    'message' => $error
                ];
            }

            return false;
        }

        if (in_array($allegroOrder->event_type, FulfillmentStatus::getUnsupportedEvents())) {
            Log::instance()
                ->order($order->id)
                ->error(LogType::ORDER_FULFILLMENT_STATUS_SEND(), 'Unsupported Event');

            return [
                'status' => false,
                'orderId' => $order->id,
                'message' => 'Dla tego zamówienia nie jest możliwa zmiana statusu Allegro.'
            ];
        }

        try {
            /** @var Fulfillment $fulfillment */
            $fulfillment = (new JsonMapBuilder('Fulfillment'))->map(new Fulfillment());
            $fulfillment->status = $fulfillmentStatus;
            $fulfillment->shipmentSummary->lineItemsSent = 'ALL';

            self::$api->order()->checkoutForms($allegroOrder->checkout_form)->setOrderStatus($allegroOrder->checkout_form_content->revision, $fulfillment);

            $allegroOrder->fulfillment_status = $fulfillmentStatus;
            $allegroOrder->save();

            Log::instance()
                ->account(self::$api->getAccount()->id)
                ->order($order->id)
                ->logDatabase()
                ->info(LogType::ORDER_FULFILLMENT_STATUS_SEND(), ['status' => $fulfillmentStatus]);
        }
        catch (Exception $ex) {
            // revision for this order is outdated
            // try to update order
            if ($ex->getCode() == 409) {
                try {
                    $checkoutForm = self::$api->order()->checkoutForms($allegroOrder->checkout_form)->getCheckoutForm();

                    switch ($checkoutForm->status) {
                        case CheckoutFormStatus::READY_FOR_PROCESSING:
                            $action = 'readyForProcessing';
                            break;

                        case CheckoutFormStatus::CANCELLED:
                            $action = 'cancelled';
                            break;

                        default:
                            throw new UnexpectedValueException("Unexpected checkoutForm.status: {$checkoutForm->status}");
                    }

                    Db::getInstance()->execute('START TRANSACTION');
                    Log::instance()
                        ->account(self::$api->getAccount()->id)
                        ->order($order->id)
                        ->logDatabase()
                        ->info(LogType::ORDER_REVISION_UPDATE(), ['checkoutForm.id' => $allegroOrder->checkout_form]);

                    (new OrderController($allegroOrder, self::$account, $checkoutForm))->execute($action);

                    Log::instance()->info(LogType::ORDER_REVISION_UPDATE_FINISH());
                    Db::getInstance()->execute('COMMIT');

                    return [
                        'status' => true,
                        'orderId' => $order->id,
                        'revisionReload' => true
                    ];
                }
                catch (Exception $ex) {
                    Db::getInstance()->execute('ROLLBACK');

                    Log::instance()
                        ->account(self::$api->getAccount()->id)
                        ->order($order->id)
                        ->logDatabase()
                        ->error(LogType::ORDER_REVISION_UPDATE(), (string)$ex);

                    return [
                        'status' => false,
                        'orderId' => $order->id,
                        'message' => (string)$ex
                    ];
                }
            }
            else {
                Log::instance()
                    ->account(self::$api->getAccount()->id)
                    ->order($order->id)
                    ->logDatabase()
                    ->error(LogType::ORDER_FULFILLMENT_STATUS_SEND(), (string)$ex);

                return [
                    'status' => false,
                    'orderId' => $order->id,
                    'message' => (string)$ex
                ];
            }
        }

        return [
            'status' => true,
            'orderId' => $order->id,
            'revisionReload' => false
        ];
    }
}
