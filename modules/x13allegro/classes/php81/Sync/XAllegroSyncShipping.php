<?php

use x13allegro\Adapter\DbAdapter;
use x13allegro\Api\Model\Order\CarrierOperator;
use x13allegro\Api\Model\Order\ParcelTrackingNumber;
use x13allegro\Api\Model\Order\Enum\FulfillmentStatus;
use x13allegro\Component\Logger\Log;
use x13allegro\Component\Logger\LogType;
use x13allegro\Json\JsonMapBuilder;

final class XAllegroSyncShipping extends XAllegroSync
{
    public static function syncShipping()
    {
        if (!XAllegroConfiguration::get('IMPORT_ORDERS')
            || !XAllegroConfiguration::get('ORDER_ALLEGRO_SEND_SHIPPING')
        ) {
            return;
        }

        foreach (self::getSyncShipping(true) as $order) {
            self::sendShippingNumber($order['id_order'], 'sync');
        }
    }

    /**
     * @param int $id_order
     * @param string $method
     * @return array
     */
    public static function sendShippingNumber($id_order, $method)
    {
        $error = '';

        $order = new Order((int)$id_order);
        /** @var XAllegroOrder $allegroOrder */
        $allegroOrder = XAllegroOrder::getByOrderId($order->id);
        $package_info = array_values(XAllegroCarrier::getPackageInfo($order->id));
        $order_carrier = new OrderCarrier((int)$package_info[0]['id_order_carrier']);

        if (!self::changeAccount($allegroOrder->id_xallegro_account)) {
            return array(
                'result' => false,
                'message' => 'Wystąpił błąd podczas połączenia z API Allegro'
            );
        }

        if (!Validate::isLoadedObject($order)) {
            $error = 'Nie można znaleźć zamówienia w bazie danych.';
        }
        else if (!$allegroOrder || empty($package_info)) {
            $error = 'To zamówienie nie jest powiązane z Allegro.';
        }
        else if (!Validate::isLoadedObject($order_carrier)) {
            $error = 'Identyfikator przewoźnika jest nieprawidłowy.';
        }
        else if (!$package_info[0]['id_operator']) {
            $error = 'Nie powiązano operatora do tego przewoźnika.';
        }
        else if (!$order_carrier->tracking_number) {
            $error = 'Nie uzupełniono numeru śledzenia dla tego przewożnika.';
        }
        else if ($package_info[0]['id_operator'] == CarrierOperator::OTHER()->getKey() && empty($package_info[0]['operator_name'])) {
            $error = 'Wybierając przewoźnika "Inny", należy podać jego nazwę.';
        }

        if (!empty($error)) {
            if ($method == 'sync') {
                Log::instance()
                    ->account(self::$api->getAccount()->id)
                    ->order($order->id)
                    ->logDatabase()
                    ->error(LogType::ORDER_SHIPPING_TRACKING_NUMBER(), $error);
            }

            return array(
                'result' => false,
                'message' => $error
            );
        }

        $hookResult = Hook::exec(
            'actionX13AllegroTrackingNumberModifier',
            array(
                'order' => $order,
                'order_carrier' => &$order_carrier,
                'id_xallegro_account' => $allegroOrder->id_xallegro_account,
            ),
            null,
            true // return as array
        );

        if (is_array($hookResult)) {
            foreach ($hookResult as $module) {
                if (isset($module['skip']) && $module['skip']) {
                    return array(
                        'result' => true,
                        'message' => 'Pominięto wysłanie numeru śledzenia.'
                    );
                }
            }
        }

        try {
            /** @var ParcelTrackingNumber $ptn */
            $ptn = (new JsonMapBuilder('ParcelTrackingNumber'))->map(new ParcelTrackingNumber());
            $ptn->waybill = trim($order_carrier->tracking_number);
            $ptn->carrierId = $package_info[0]['id_operator'];
            $ptn->carrierName = $package_info[0]['operator_name'];

            if (empty($allegroOrder->checkout_form_content->lineItems->items)) {
                $checkoutForm = self::$api->order()->checkoutForms($allegroOrder->checkout_form)->getCheckoutForm();
                $allegroOrder->checkout_form_content->lineItems->items = $checkoutForm->lineItems;
            }

            foreach ($allegroOrder->checkout_form_content->lineItems->items as $item) {
                $ptn->lineItem($item->id);
            }

            // insert data (without tracking number) before API request to correctly mark errors
            XAllegroCarrier::updatePackageInfo(
                $order_carrier->id,
                $order->id,
                $package_info[0]['id_operator'],
                $package_info[0]['operator_name']
            );

            self::$api->order()->checkoutForms($allegroOrder->checkout_form)->addTrackingNumber($ptn);

            XAllegroCarrier::updateTrackingNumber($order->id, $order_carrier->tracking_number);
            XAllegroCarrier::markAsSend($order->id);

            Log::instance()
                ->account(self::$api->getAccount()->id)
                ->order($order->id)
                ->logDatabase()
                ->info(LogType::ORDER_SHIPPING_TRACKING_NUMBER());

            $changeFulfillmentStatus = XAllegroConfiguration::get('ORDER_ALLEGRO_SHIPPING_STATUS');
            $fulfillmentStatus = XAllegroConfiguration::get('ORDER_ALLEGRO_SHIPPING_STATUS_FULFILLMENT');

            if ($changeFulfillmentStatus && FulfillmentStatus::isValidKey($fulfillmentStatus)) {
                XAllegroSyncFulfillmentStatus::sendFulfillmentStatus($order->id, $fulfillmentStatus);
            }
        }
        catch (Exception $ex) {
            Log::instance()
                ->account(self::$api->getAccount()->id)
                ->order($order->id)
                ->logDatabase()
                ->error(LogType::ORDER_SHIPPING_TRACKING_NUMBER(), (string)$ex);

            // manage only when there is 4xx error
            if ($ex->getCode() < 500) {
                XAllegroCarrier::markAsError($order->id);
            }

            return array(
                'result' => false,
                'message' => (string)$ex
            );
        }

        return array(
            'result' => true,
            'message' => 'Numer śledzenia został wysłany do Allegro.'
        );
    }

    /**
     * @param bool $checkBlockedStates
     * @param int $offset
     * @return array
     */
    public static function getSyncShipping($checkBlockedStates = false, $offset = 0)
    {
        $orderStates = false;
        if ($checkBlockedStates) {
            $orderStates = json_decode(XAllegroConfiguration::get('BLOCK_ORDER_SEND_SHIPPING_STATUS'), true);
        }

        $result = Db::getInstance()->executeS('
            SELECT o.`id_order`, xac.`id_xallegro_account`
            FROM `' . _DB_PREFIX_ . 'orders` o
            JOIN `' . _DB_PREFIX_ . 'xallegro_order` xo 
                ON (o.`id_order` = xo.`id_order`)
            JOIN `' . _DB_PREFIX_ . 'xallegro_account` xac
			    ON (xo.`id_xallegro_account` = xac.`id_xallegro_account`)
            LEFT JOIN `' . _DB_PREFIX_ . 'xallegro_carrier` xc 
                ON (xo.`delivery_method` = xc.`id_fields_shipment`)
            LEFT JOIN `' . _DB_PREFIX_ . 'xallegro_carrier_account` xca
                ON (xo.`delivery_method` = xca.`id_fields_shipment`
                    AND xca.`id_xallegro_account` = xo.`id_xallegro_account`)
            LEFT JOIN `' . _DB_PREFIX_ . 'order_carrier` oc 
                ON (o.`id_order` = oc.`id_order`)
            LEFT JOIN `' . _DB_PREFIX_ . 'xallegro_carrier_package_info` xcp 
                ON (o.`id_order` = xcp.`id_order`)
            WHERE xo.`delivery_method` IS NOT NULL
                AND xo.`delivery_method` NOT LIKE "0"
                AND ((xc.`id_operator` IS NOT NULL AND xc.`id_operator` != "")
                    OR (xca.`id_operator` IS NOT NULL AND xca.`id_operator` != "")
                    OR (xcp.`id_operator` IS NOT NULL AND xcp.`id_operator` != ""))
                AND (oc.`tracking_number` IS NOT NULL AND oc.`tracking_number` != "")
                AND (xcp.`send_enabled` = 1 OR xcp.`send_enabled` IS NULL)
                AND (xcp.`id_order_carrier` IS NULL
                    OR xcp.`send` = 0 
                    OR replace(xcp.`tracking_number`, " ", "") COLLATE ' . pSQL(DbAdapter::getColumnCollation('xallegro_carrier_package_info', 'tracking_number')) . ' NOT LIKE replace(oc.`tracking_number`, " ", ""))
                AND xac.`active` = 1'
                . (is_array($orderStates) && !empty($orderStates) ? ' AND o.`current_state` NOT IN (' . implode(',', array_map('intval', $orderStates)) . ')' : '') . '
            GROUP BY o.`id_order`
            LIMIT ' . ($offset ? ($offset * (int)XAllegroConfiguration::get('IMPORT_ORDERS_CHUNK')) . ', ' : '') . (int)XAllegroConfiguration::get('IMPORT_ORDERS_CHUNK')
        );

        if (!$result) {
            return array();
        }

        return $result;
    }

    /**
     * @param bool $checkBlockedStates
     * @return int
     */
    public static function getCountSyncShipping($checkBlockedStates = false)
    {
        $orderStates = false;
        if ($checkBlockedStates) {
            $orderStates = json_decode(XAllegroConfiguration::get('BLOCK_ORDER_SEND_SHIPPING_STATUS'), true);
        }

        return (int)Db::getInstance()->getValue('
            SELECT COUNT(o.`id_order`)
            FROM `' . _DB_PREFIX_ . 'orders` o
            JOIN `' . _DB_PREFIX_ . 'xallegro_order` xo 
                ON (o.`id_order` = xo.`id_order`)
            JOIN `' . _DB_PREFIX_ . 'xallegro_account` xac
                ON (xo.`id_xallegro_account` = xac.`id_xallegro_account`)
            LEFT JOIN `' . _DB_PREFIX_ . 'xallegro_carrier` xc 
                ON (xo.`delivery_method` = xc.`id_fields_shipment`)
            LEFT JOIN `' . _DB_PREFIX_ . 'order_carrier` oc 
                ON (o.`id_order` = oc.`id_order`)
            LEFT JOIN `' . _DB_PREFIX_ . 'xallegro_carrier_account` xca
                ON (xo.`delivery_method` = xca.`id_fields_shipment`
                    AND xca.`id_xallegro_account` = xo.`id_xallegro_account`)
            LEFT JOIN `' . _DB_PREFIX_ . 'xallegro_carrier_package_info` xcp 
                ON (o.`id_order` = xcp.`id_order`)
            WHERE xo.`delivery_method` IS NOT NULL
                AND xo.`delivery_method` NOT LIKE "0"
                AND ((xc.`id_operator` IS NOT NULL AND xc.`id_operator` != "")
                    OR (xca.`id_operator` IS NOT NULL AND xca.`id_operator` != "")
                    OR (xcp.`id_operator` IS NOT NULL AND xcp.`id_operator` != ""))
                AND (oc.`tracking_number` IS NOT NULL AND oc.`tracking_number` != "")
                AND (xcp.`send_enabled` = 1 OR xcp.`send_enabled` IS NULL)
                AND (xcp.`id_order_carrier` IS NULL
                    OR xcp.`send` = 0 
                    OR replace(xcp.`tracking_number`, " ", "") COLLATE ' . pSQL(DbAdapter::getColumnCollation('xallegro_carrier_package_info', 'tracking_number')) . ' NOT LIKE replace(oc.`tracking_number`, " ", ""))
                AND xac.`active` = 1'
                . (is_array($orderStates) && !empty($orderStates) ? ' AND o.`current_state` NOT IN (' . implode(',', array_map('intval', $orderStates)) . ')' : '') . '
            GROUP BY o.`id_order`'
        );
    }
}
