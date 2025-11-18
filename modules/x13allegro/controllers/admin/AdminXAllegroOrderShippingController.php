<?php

require_once (dirname(__FILE__) . '/../../x13allegro.php');

use x13allegro\Api\DataProvider\CarrierOperatorsProvider;
use x13allegro\Api\Model\Order\CarrierOperator;

final class AdminXAllegroOrderShippingController extends XAllegroController
{
    protected $allegroAutoLogin = true;

    private $allegroOperators = [];
    private $allegroShipments = [];

    public function __construct()
    {
        $this->table = 'order';
        $this->identifier = 'id_order';
        $this->className = 'Order';
        $this->list_no_link = true;

        parent::__construct();

        $this->tabAccess = Profile::getProfileAccess($this->context->employee->id_profile, Tab::getIdFromClassName('AdminXAllegroOrderShipping'));
        $this->tpl_folder = 'x_allegro_order_shipping/';
    }

    public function init()
    {
        parent::init();

        if ($this->allegroApi) {
            try {
                $deliveryMethods = $this->allegroApi->sale()->deliveryMethods()->getAll();
                foreach ($deliveryMethods->deliveryMethods as $deliveryMethod) {
                    $this->allegroShipments[$deliveryMethod->id] = $deliveryMethod->name;
                }

                $carrierOperators = (new CarrierOperatorsProvider($this->allegroApi))->getCarriers();
                foreach ($carrierOperators as $carrierOperator) {
                    $this->allegroOperators[$carrierOperator['id']] = $carrierOperator['name'];
                }
            }
            catch (Exception $ex) {
                $this->errors[] = (string)$ex;
            }
        }

        $statuses = [];
        foreach (OrderState::getOrderStates((int)$this->context->language->id) as $status) {
            $statuses[$status['id_order_state']] = $status['name'];
        }

        $this->fields_list = array(
            'id_order' => array(
                'title' => $this->l('ID'),
                'align' => 'text-center',
                'class' => 'fixed-width-xs'
            ),
            'reference' => array(
                'title' => $this->l('Indeks')
            ),
            'order_status' => array(
                'title' => $this->l('Status zamówienia'),
                'type' => 'select',
                'color' => 'color',
                'list' => $statuses,
                'filter_key' => 'os!id_order_state',
                'filter_type' => 'int',
                'order_key' => 'order_status'
            ),
            'allegro_shipment' => array(
                'title' => $this->l('Metoda wysyłki'),
                'orderby' => false,
                'filter' => false,
                'search' => false
            ),
            'carrier_name' => array(
                'title' => $this->l('Przewoźnik'),
                'filter_key' => 'c!carrier_name',
                'order_key' => 'carrier_name'
            ),
            'operator_allegro' => array(
                'title' => $this->l('Operator Allegro'),
                'type' => 'select',
                'list' => $this->allegroOperators,
                'filter_key' => 'id_operator',
                'filter_type' => 'select',
                'order_key' => 'id_operator'
            ),
            'tracking_number' => array(
                'title' => $this->l('Numer śledzenia'),
                'callback' => 'printTrackingNumber',
                'filter_key' => 'oc!tracking_number',
                'order_key' => 'tracking_number'
            ),
            'send' => array(
                'title' => $this->l('Wysłano'),
                'align' => 'text-center',
                'type' => 'bool',
                'icon' => array(
                    '0' => array('class' => 'icon-remove color', 'src' => 'disabled.gif', 'alt' => $this->l('Niewysłane')),
                    '1' => array('class' => 'icon-check color', 'src' => 'enabled.gif', 'alt' => $this->l('Wysłane'))
                ),
                'class' => 'fixed-width-xs',
                'filter_key' => 'send'
            ),
            'send_enabled' => array(
                'title' => $this->l('Wysyłka'),
                'align' => 'text-center',
                'type' => 'bool',
                'active' => 'send_enabled',
                'icon' => array(
                    '0' => array('class' => 'icon-remove color', 'src' => 'disabled.gif', 'alt' => $this->l('Wyłączone')),
                    '1' => array('class' => 'icon-check color', 'src' => 'enabled.gif', 'alt' => $this->l('Włączone'))
                ),
            ),
            'error' => array(
                'title' => $this->l('Błędy'),
                'align' => 'text-center',
                'type' => 'int',
                'search' => false,
                'badge_danger' => true
            ),
        );

        $this->bulk_actions['xSendEnable'] = [
            'icon' => 'icon-power-off text-success bulkSendEnable',
            'text' => $this->l('Włącz wysyłanie')
        ];
        $this->bulk_actions['xSendDisable'] = [
            'icon' => 'icon-power-off text-danger bulkSendDisable',
            'text' => $this->l('Wyłącz wysyłanie')
        ];
    }

    public function initPageHeaderToolbar()
    {
        $this->page_header_toolbar_btn['send_numbers'] = array(
            'href' => '#',
            'desc' => $this->l('Wyślij numery śledzenia'),
            'icon' => 'process-icon-send icon-send',
            'class' => 'xallegro-sync-shipping'
        );

        parent::initPageHeaderToolbar();
    }

    public function renderList()
    {
        $this->informations[] = $this->l('Aby wysłać numer śledzenia do Allegro z tej podstrony, konieczne jest zmapowanie przewoźnika w Twoim sklepie do operatora Allegro i uzupełnienie numeru na podglądzie zamówienia.');

        $this->addRowAction('xSendNumber');
        $this->addRowAction('xOrderView');
        $this->addRowAction('xSendDisable');

        $this->context->smarty->assign('new_shipping_info', (int)XAllegroConfiguration::get('NEW_SHIPPING_INFO'));

        return parent::renderList();
    }

    public function getList($id_lang, $orderBy = null, $orderWay = null, $start = 0, $limit = null, $id_lang_shop = null)
    {
        $this->_select .= '
            xo.`delivery_method`,
            IFNULL(xcp.`id_operator`, IFNULL(xca.`id_operator`, xc.`id_operator`)) as `id_operator`,
            IFNULL(xcp.`operator_name`, IFNULL(xca.`operator_name`, xc.`operator_name`)) as `operator_name`,
            os.`color`,
            osl.`name` AS `order_status`,
            c.`carrier_name`,
            oc.`tracking_number`,
            xcp.`tracking_number` as `send_tracking_number`,
            xcp.`send_enabled`,
            IF(xcp.`error` > 0, xcp.`error`, "--") AS `error`,
            IF(xcp.`error` > 0, 1, 0) AS `badge_danger`,
            IFNULL(xcp.`send`, 0) as `send`';

        $this->_join .= '
            JOIN `' . _DB_PREFIX_ . 'xallegro_order` xo ON (a.`id_order` = xo.`id_order`)
            LEFT JOIN `' . _DB_PREFIX_ . 'xallegro_carrier` xc ON (xo.`delivery_method` = xc.`id_fields_shipment`)
            LEFT JOIN `' . _DB_PREFIX_ . 'xallegro_carrier_account` xca ON (xo.`delivery_method` = xca.`id_fields_shipment` AND xca.`id_xallegro_account` = xo.`id_xallegro_account`)
            LEFT JOIN `' . _DB_PREFIX_ . 'xallegro_carrier_package_info` xcp ON (a.`id_order` = xcp.`id_order`)
            LEFT JOIN `' . _DB_PREFIX_ . 'order_carrier` oc ON (oc.`id_order` = a.`id_order`)
            LEFT JOIN `' . _DB_PREFIX_ . 'order_state` os ON (os.`id_order_state` = a.`current_state`)
		    LEFT JOIN `' . _DB_PREFIX_ . 'order_state_lang` osl ON (os.`id_order_state` = osl.`id_order_state` AND osl.`id_lang` = ' . (int)$this->context->language->id . ')
		    INNER JOIN (
		        SELECT o.`id_order`,
		            CASE WHEN carrier.`name` = "0"
                    THEN "' . pSQL(method_exists(Carrier::class, 'getCarrierNameFromShopName') ? Carrier::getCarrierNameFromShopName() : 'Click and collect') . '"
                    ELSE carrier.`name` END as `carrier_name`
                FROM `' . _DB_PREFIX_ . 'carrier` carrier
                LEFT JOIN `' . _DB_PREFIX_ . 'order_carrier` oc
                    ON (carrier.`id_carrier` = oc.`id_carrier`)
                LEFT JOIN `' . _DB_PREFIX_ . 'orders` o
                    ON (o.`id_order` = oc.`id_order`)
		    ) c ON (c.`id_order` = a.`id_order`)';

        $this->_group = 'GROUP BY a.`id_order`';
        $this->_orderBy = 'id_order';
        $this->_orderWay = 'DESC';

        parent::getList($id_lang, $orderBy, $orderWay, $start, $limit, $this->context->shop->id);

        foreach ($this->_list as &$item) {
            $item['allegro_shipment'] = (isset($this->allegroShipments[$item['delivery_method']]) ? $this->allegroShipments[$item['delivery_method']] : null);
            $item['same_number'] = ($item['send'] && strcasecmp(trim($item['tracking_number']), trim($item['send_tracking_number'])) == 0);
            $item['operator_allegro'] = null;

            if (isset($this->allegroOperators[$item['id_operator']])) {
                $item['operator_allegro'] = $this->allegroOperators[$item['id_operator']] . ($item['id_operator'] == CarrierOperator::OTHER()->getKey() ? ': ' . $item['operator_name'] : '');
            }
        }
    }

    public function processFilter()
    {
        // process parent and return when submitFilter
        // this will set correct cookie filters values
        if (isset($_POST) && count($_POST) && (int)Tools::getValue('submitFilter' . $this->list_id)) {
            parent::processFilter();
            return;
        }

        $filterCache = [];
        $filters = [
            'orderFilter_send',
            'orderFilter_id_operator'
        ];

        $cookiePrefix = $this->getCookieFilterPrefix();

        foreach ($filters as $filter) {
            $filterName = $cookiePrefix . $filter;

            if (isset($this->context->cookie->{$filterName})) {
                $filterValue = $this->context->cookie->{$filterName};
                $filterCache[$filterName] = $filterValue;

                // unset filters before process parent to avoid duplicates is sql query
                unset($this->context->cookie->{$filterName});

                switch ($filter) {
                    case 'orderFilter_send':
                        $this->_where .= ' AND IFNULL(xcp.`send`, 0) = ' . (int)$filterValue;
                        break;

                    case 'orderFilter_id_operator':
                        $this->_where .= ' AND IFNULL(xcp.`id_operator`, IFNULL(xca.`id_operator`, xc.`id_operator`)) = "' . pSQL($filterValue) . '"';
                        break;
                }
            }
        }

        parent::processFilter();

        if (!empty($filterCache)) {
            foreach ($filterCache as $cookie => $value) {
                $this->context->cookie->{$cookie} = $value;
            }
        }
    }

    public function postProcess()
    {
        if (Tools::isSubmit('send_number') && Tools::getValue('id_order')) {
            $result = XAllegroSyncShipping::sendShippingNumber(Tools::getValue('id_order'), 'list');

            if (!$result['result']) {
                $this->errors[] = $this->l($result['message']);
            }
            else {
                $this->module->sessionMessages->confirmations($this->l($result['message']));

                Tools::redirectAdmin($this->context->link->getAdminLink('AdminXAllegroOrderShipping'));
            }
        }
        else if (Tools::getIsset('send_enabled' . $this->table) && Tools::getValue('id_order')) {
            $orderId = (int)Tools::getValue('id_order');

            foreach (XAllegroCarrier::getPackageInfo($orderId) as $packageInfo) {
                if ($packageInfo['send'] && strcasecmp(trim($packageInfo['order_tracking_number']), trim($packageInfo['send_tracking_number'])) == 0) {
                    $this->module->sessionMessages->confirmations("Zamówienie <b>#$orderId</b> Numer śledzenia został już wysłany");
                } else {
                    XAllegroCarrier::updateSendEnabled(!$packageInfo['send_enabled'], $orderId);

                    if (!$packageInfo['send_enabled']) {
                        $this->module->sessionMessages->confirmations("Zamówienie <b>#$orderId</b> Włączono wysyłanie");
                    } else {
                        $this->module->sessionMessages->confirmations("Zamówienie <b>#$orderId</b> Wyłączone wysyłanie");
                    }
                }
            }
        }
        else if (Tools::isSubmit('send_disable') && Tools::getValue('id_order')) {
            XAllegroCarrier::updateSendEnabled((int)!Tools::getValue('send_disable'), Tools::getValue('id_order'));
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminXAllegroOrderShipping') . '&conf=4');
        }

        return parent::postProcess();
    }

    public function processBulkXSendEnable()
    {
        $identifierList = Tools::getValue($this->list_id . 'Box', []);

        if (empty($identifierList)) {
            $this->errors[] = $this->l('Nie wybrano zamówienien do włączenia.');
        } else {
            foreach ($identifierList as $orderId) {
                foreach (XAllegroCarrier::getPackageInfo($orderId) as $packageInfo) {
                    if ($packageInfo['send'] && strcasecmp(trim($packageInfo['order_tracking_number']), trim($packageInfo['send_tracking_number'])) == 0) {
                        $this->module->sessionMessages->confirmations("Zamówienie <b>#$orderId</b> Numer śledzenia został już wysłany");
                    } else {
                        XAllegroCarrier::updateSendEnabled(1, $orderId);
                        $this->module->sessionMessages->confirmations("Zamówienie <b>#$orderId</b> Włączono wysyłanie");
                    }
                }
            }
        }
    }

    public function processBulkXSendDisable()
    {
        $identifierList = Tools::getValue($this->list_id . 'Box', []);

        if (empty($identifierList)) {
            $this->errors[] = $this->l('Nie wybrano zamówienien do wyłączenia.');
        } else {
            foreach ($identifierList as $orderId) {
                foreach (XAllegroCarrier::getPackageInfo($orderId) as $packageInfo) {
                    if ($packageInfo['send'] && strcasecmp(trim($packageInfo['order_tracking_number']), trim($packageInfo['send_tracking_number'])) == 0) {
                        $this->module->sessionMessages->confirmations("Zamówienie <b>#$orderId</b> Numer śledzenia został już wysłany");
                    } else {
                        XAllegroCarrier::updateSendEnabled(0, $orderId);
                        $this->module->sessionMessages->confirmations("Zamówienie <b>#$orderId</b> Wyłączono wysyłanie");
                    }
                }
            }
        }
    }

    public function printTrackingNumber($id, $row)
    {
        if ($row['send'] && $row['same_number']) {
            return '<span class="badge badge-success">' . $row['send_tracking_number'] . '</span>';
        }
        else if ($row['send'] && !$row['same_number']) {
            return $row['send_tracking_number'] . '&nbsp;<span class="badge badge-warning">' . $this->l('uaktualnij') . '</span>';
        }
        else if (!$row['carrier_name']) {
            return '<span class="badge badge-danger">' . $this->l('brak przewoźnika') . '</span>';
        }
        else if (!$row['operator_allegro']) {
            return '<span class="badge badge-danger">' . $this->l('uzupełnij operatora') . '</span>';
        }

        return $id;
    }

    public function displayXSendNumberLink($token = null, $id, $name = null)
    {
        $row = $this->findElementByKeyValue($this->_list, 'id_order', $id);

        if (!$row['tracking_number'] || !$row['delivery_method'] || !$row['operator_allegro'] || $row['same_number'] || !$row['send_enabled']) {
            return null;
        }

        $tpl = $this->context->smarty->createTemplate($this->module->getLocalPath() . 'views/templates/admin/' . $this->tpl_folder . 'helpers/list/action_send_number.tpl');
        $tpl->assign(array(
            'href' => $this->context->link->getAdminLink('AdminXAllegroOrderShipping') . '&send_number&id_order=' . (int)$id,
            'is_send' => (bool)$row['send'],
            'title' => (!$row['same_number'] && $row['send'] ? $this->l('Wyślij numer śledzenia ponownie') : $this->l('Wyślij numer śledzenia')),
            'action' => (!$row['same_number'] && $row['send'] ? $this->l('Wyślij ponownie') : $this->l('Wyślij')),
        ));

        return $tpl->fetch();
    }

    public function displayXSendDisableLink($token = null, $id, $name = null)
    {
        $row = $this->findElementByKeyValue($this->_list, 'id_order', $id);

        if (!$row['delivery_method'] || ($row['send'] && $row['same_number'])) {
            return null;
        }

        $tpl = $this->context->smarty->createTemplate($this->module->getLocalPath() . 'views/templates/admin/' . $this->tpl_folder . 'helpers/list/action_send_disabled.tpl');
        $tpl->assign(array(
            'href' => $this->context->link->getAdminLink('AdminXAllegroOrderShipping') . '&send_disable=' . (int)$row['send_enabled'] . '&id_order=' . (int)$id,
            'icon' => ($row['send_enabled'] ? 'text-danger' : 'text-success'),
            'title' => ($row['send_enabled'] ? $this->l('Wyłącz wysyłanie numeru śledzenia') : $this->l('Włącz wysyłanie numeru śledzenia')),
            'action' => ($row['send_enabled'] ? $this->l('Wyłącz wysyłanie') : $this->l('Włącz wysyłanie'))
        ));

        return $tpl->fetch();
    }

    public function displayXOrderViewLink($token = null, $id, $name = null)
    {
        $params = [
            'vieworder' => 1,
            'id_order' => $id
        ];

        if (version_compare(_PS_VERSION_, '1.7.7.0', '>=')) {
            $href = $this->context->link->getAdminLink('AdminOrders', true, $params);
        } else {
            $href = $this->context->link->getAdminLink('AdminOrders') . '&' . http_build_query($params);
        }

        $tpl = $this->context->smarty->createTemplate($this->module->getLocalPath() . 'views/templates/admin/' . $this->tpl_folder . 'helpers/list/action_view_order.tpl');
        $tpl->assign(array(
            'href' => $href,
            'title' => $this->l('Zobacz zamówienie'),
            'action' => $this->l('Zobacz zamówienie'),
        ));

        return $tpl->fetch();
    }

    public function ajaxProcessSaveShippingInfo()
    {
        $error = false;
        $order = new Order((int)Tools::getValue('id_order'));
        $order_carrier = new OrderCarrier((int)Tools::getValue('id_order_carrier'));
        $operator_id = Tools::getValue('operatorId');
        $operator_name = Tools::getValue('operatorName');

        if ($operator_id != CarrierOperator::OTHER()->getKey()) {
            $operator_name = '';
        }

        if (!Validate::isLoadedObject($order)) {
            $error = $this->l('Nie można znaleźć zamówienia w bazie danych.');
        }
        else if (!Validate::isLoadedObject($order_carrier)) {
            $error = $this->l('Identyfikator przewoźnika jest nieprawidłowy.');
        }
        else if ($operator_id == CarrierOperator::OTHER()->getKey() && empty($operator_name)) {
            $error = $this->l('Wybierając przewoźnika "Inny", należy podać jego nazwę.');
        }

        if ($error) {
            die(json_encode(array(
                'result' => false,
                'message' => $error
            )));
        }

        if (!XAllegroOrder::exists($order->id)) {
            die(json_encode(array(
                'result' => false,
                'message' => $this->l('To zamówienie nie jest powiązane z Allegro.')
            )));
        }

        XAllegroCarrier::updatePackageInfo($order_carrier->id, $order->id, $operator_id, $operator_name);

        $adminUrl = $this->context->link->getAdminLink('AdminOrders') . '&conf=4&vieworder&id_order=' . $order->id;
        if (version_compare(_PS_VERSION_, '1.7.7.0', '>=')) {
            $adminUrl = $this->context->link->getAdminLink('AdminOders', true, array(
                'route' => 'admin_orders_view',
                'action' => 'view',
                'orderId' =>  (int) $order->id,
            ));
        }

        die(json_encode(array(
            'result' => true,
            'redirect' => $adminUrl
        )));
    }

    public function ajaxProcessSyncShippingNumbers()
    {
        $ids_added = Tools::getValue('ids_added', array());
        $ids_not_added = Tools::getValue('ids_not_added', array());
        $count = (int)Tools::getValue('count');
        $offset = (int)Tools::getValue('offset');

        if ($count == -1) {
            $count = XAllegroSyncShipping::getCountSyncShipping();
        }

        $orders = XAllegroSyncShipping::getSyncShipping(false, $offset);
        $offset++;

        if (empty($orders)) {
            if (empty($ids_added)) {
                $this->module->sessionMessages->confirmations($this->l('Brak numerów do wysłania'));
            } else {
                foreach ($ids_added as $id) {
                    $this->module->sessionMessages->confirmations($id);
                }
            }

            foreach ($ids_not_added as $id) {
                $this->module->sessionMessages->errors($id);
            }

            die(json_encode(array(
                'success' => false,
                'link' => $this->context->link->getAdminLink('AdminXAllegroOrderShipping')
            )));
        }

        foreach ($orders as $order) {
            $result = XAllegroSyncShipping::sendShippingNumber($order['id_order'], 'list');

            if (!$result['result']) {
                $ids_not_added[] = 'Zamówienie <b>#' . $order['id_order'] . '</b> ' . $this->l($result['message']) . '<br>';
            } else {
                $ids_added[] = 'Zamówienie <b>#' . $order['id_order'] . '</b> ' . $this->l($result['message']) . '<br>';
            }
        }

        die(json_encode(array(
            'success' => true,
            'count' => $count,
            'offset' => $offset,
            'ids_added' => $ids_added,
            'ids_not_added' => $ids_not_added
        )));
    }

    /**
     * @param array $list
     * @param string $key
     * @param int $value
     * @return mixed
     */
    private function findElementByKeyValue(array $list, $key, $value)
    {
        foreach ($list as $item)
        {
            if (is_array($item) && isset($item[$key]) && $item[$key] == $value) {
                return $item;
            }
            else if (is_object($item) && property_exists($item, $key) && $item->{$key} == $value) {
                return $item;
            }
        }

        return false;
    }
}
