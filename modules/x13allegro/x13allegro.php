<?php

// fix cache redirects
if (!headers_sent()) {
    header('Cache-Control: no-store, no-cache, must-revalidate');
}

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once (dirname(__FILE__) . '/x13allegro.ion.php');

define('X13_ALLEGRO_VERSION', '7.6.1');
define('X13_ALLEGRO_DEBUG', false);

class x13allegro extends x13allegro\XAllegroModuleCore
{
    public $installed;
    
    /** @var XAllegroUpdate|null */
    public $update = null;

    /** @var XAllegroConfiguration */
    public $config;

    /** @var x13allegro\Component\Session\SessionMessages|null */
    public $sessionMessages = null;

    /** @var bool */
    public $bootstrap;

    public function __construct()
    {
        $this->name = 'x13allegro';
        $this->tab = 'market_place';
        $this->version = X13_ALLEGRO_VERSION;
        $this->author = 'X13.pl';
        $this->need_instance = 0;
        $this->bootstrap = true;
        $this->ps_versions_compliancy = ['min' => '1.6.0.0', 'max' => '8.9.99'];

        parent::__construct();

        $this->config = new XAllegroConfiguration($this);

        if (strpos($_SERVER['SCRIPT_NAME'], 'sync.php') === false
            && ($this->context->controller instanceof AdminController
                || $this->context->controller instanceof ModuleAdminController)
        ) {
            // hack for Symfony sessions
            $this->context->link->getAdminLink('AdminProducts');
            $this->sessionMessages = new \x13allegro\Component\Session\SessionMessages();
        }

        if ($this->id && !$this->isHookRegistered('displayBackOfficeHeader')) {
            $this->registerHook('displayBackOfficeHeader');
        }

        $this->displayName = $this->l('Integracja PrestaShop z Allegro');
        $this->description = $this->l('Zaawansowany moduł integrujący sklep PrestaShop z Allegro. Szybkie wystawianie ofert, pełna kontrola ilości oraz cen. Opcja importu zamówień z Allegro.');
        $this->confirmUninstall = $this->l('Spowoduje usunięcie wszystkich powiązań ofert, kategorii, szablonów, oraz ustawień modułu!');
    }

    public function getContent()
    {
        Tools::redirectAdmin($this->context->link->getAdminLink('AdminXAllegroConfiguration'));
    }

    public function hookDisplayHeader()
    {
        if (XAllegroConfiguration::get('FRONT_DISPLAY_LINK')
            && version_compare(_PS_VERSION_, '1.7.0.0', '<')
            && Tools::getValue('controller') == 'product'
        ) {
            $this->context->controller->addJS($this->_path . 'views/js/x13allegro-front.js');
        }
    }

    public function hookDisplayLeftColumnProduct($params)
    {
        if (XAllegroConfiguration::get('FRONT_DISPLAY_LINK_HOOK') != 'displayLeftColumnProduct') {
            return null;
        }

        return $this->generateProductAllegroAuctionLink($params);
    }

    public function hookDisplayRightColumnProduct($params)
    {
        if (XAllegroConfiguration::get('FRONT_DISPLAY_LINK_HOOK') != 'displayRightColumnProduct') {
            return null;
        }

        return $this->generateProductAllegroAuctionLink($params);
    }

    public function hookDisplayProductButtons($params)
    {
        if (version_compare(_PS_VERSION_, '1.7.0.0', '<')
            && XAllegroConfiguration::get('FRONT_DISPLAY_LINK_HOOK') != 'displayProductButtons'
        ) {
            return null;
        }

        return $this->generateProductAllegroAuctionLink($params);
    }

    public function hookDisplayProductAdditionalInfo($params)
    {
        if (version_compare(_PS_VERSION_, '1.7.0.0', '<')
            && XAllegroConfiguration::get('FRONT_DISPLAY_LINK_HOOK') != 'displayProductAdditionalInfo'
        ) {
            return null;
        }

        return $this->generateProductAllegroAuctionLink($params);
    }

    public function hookDisplayProductAllegroAuctionLink($params)
    {
        if (XAllegroConfiguration::get('FRONT_DISPLAY_LINK_HOOK') != 'displayProductAllegroAuctionLink') {
            return null;
        }

        return $this->generateProductAllegroAuctionLink($params);
    }

    private function generateProductAllegroAuctionLink($params)
    {
        if (!XAllegroConfiguration::get('FRONT_DISPLAY_LINK')) {
            return null;
        }

        if (version_compare(_PS_VERSION_, '1.7.0.0', '>=')) {
            if ($auction = XAllegroAuction::getAuctionByProduct($params['product']['id_product'], $params['product']['id_product_attribute'])) {
                $this->context->smarty->assign([
                    'href' => 'https://allegro.pl/show_item.php?item=' . $auction['id_auction']
                ]);
            }
        }

        $this->context->smarty->assign([
            'allegro_img' => $this->context->shop->getBaseURL(Configuration::get('PS_SSL_ENABLED')) . 'modules/x13allegro/img/AdminXAllegroMain.png'
        ]);

        return $this->display(__FILE__, 'product-allegro-auction-link.tpl');
    }

    public function hookDisplayAdminOrderTabShip($params)
    {
        if (!XAllegroOrder::exists($params['order']->id)) {
            return null;
        }

        return $this->display(__FILE__, 'views/templates/hook/admin-order-tab-ship.tpl');
    }

    public function hookDisplayAdminOrderContentShip($params)
    {
        $allegroOrder = XAllegroOrder::getByOrderId($params['order']->id);

        if (!$allegroOrder) {
            return null;
        }

        $package_info = XAllegroCarrier::getPackageInfo($params['order']->id);
        $order_shipping = $params['order']->getShipping();

        foreach ($package_info as &$package) {
            $package['same_number'] = (strcasecmp($package['order_tracking_number'], $package['send_tracking_number']) == 0);
        }

        foreach ($order_shipping as &$shipping) {
            $shipping['carrier_name'] = (method_exists(Cart::class, 'replaceZeroByShopName') ? Cart::replaceZeroByShopName($shipping['carrier_name'], null) : $shipping['carrier_name']);
            $shipping['package_info'] = (isset($package_info[$shipping['id_order_carrier']]) ? $package_info[$shipping['id_order_carrier']] : []);
        }

        try {
            $api = new \x13allegro\Api\XAllegroApi(new XAllegroAccount($allegroOrder->id_xallegro_account));
            $carrierOperators = (new \x13allegro\Api\DataProvider\CarrierOperatorsProvider($api))->getCarriers();
        }
        catch (Exception $ex) {
            $carrierOperators = [];
        }

        $this->context->smarty->assign([
            'order' => $params['order'],
            'order_shipping' => $order_shipping,
            'order_shipping_token' => Tools::getAdminTokenLite('AdminXAllegroOrderShipping'),
            'carrier_list' => $carrierOperators
        ]);

        return $this->display(__FILE__, 'views/templates/hook/admin-order-content-ship.tpl');
    }

    public function hookDisplayAdminOrderLeft($params)
    {
        /** @var XAllegroOrder $allegroOrder */
        $allegroOrder = XAllegroOrder::getByOrderId($params['id_order']);

        if (!$allegroOrder) {
            return null;
        }

        $order = new Order($params['id_order']);
        $allegroAccount = new XAllegroAccount($allegroOrder->id_xallegro_account);

        try {
            $api = new \x13allegro\Api\XAllegroApi($allegroAccount);
            $invoices = $api->order()->checkoutForms($allegroOrder->checkout_form)->invoices()->getInvoices()->invoices;
        }
        catch (Exception $ex) {
            $invoices = [];
        }

        $countries = [];
        foreach (Country::getCountries($this->context->language->id) as $country) {
            $countries[$country['iso_code']] = $country['name'];
        }

        $this->context->smarty->assign([
            'countries' => $countries,
            'orderStateId' => $order->current_state,
            'orderHasInvoice' => Configuration::get('PS_INVOICE') && $order->hasInvoice(),
            'allegroAccount' => $allegroAccount,
            'allegroOrder' => $allegroOrder,
            'allegroInvoices' => $invoices,
            'salesCenterOrderUrl' => \x13allegro\Api\XAllegroApi::getSalesCenterOrderUrl($allegroOrder->checkout_form_content->id, $allegroAccount->sandbox),
            'unsupportedEvents' => \x13allegro\Api\Model\Order\Enum\FulfillmentStatus::getUnsupportedEvents(),
            'fulfilmentStatuses' => \x13allegro\Api\Model\Order\Enum\FulfillmentStatus::toChoseList(),
            'marketplaceProvider' => new \x13allegro\Api\DataProvider\MarketplacesProvider($allegroOrder->marketplace),
            'ALLEGRO_STATUS_FILLED_IN' => XAllegroConfiguration::get('ALLEGRO_STATUS_FILLED_IN'),
            'orderMainToken' => Tools::getAdminTokenLite('AdminXAllegroOrderMain')
        ]);

        return $this->display(__FILE__, 'views/templates/hook/admin-order-left.tpl');
    }

    public function hookDisplayAdminOrder($params)
    {
        if (version_compare(_PS_VERSION_, '1.6.1.0', '<')) {
            return $this->hookDisplayAdminOrderLeft($params);
        }

        return null;
    }

    public function hookDisplayAdminOrderMain($params)
    {
        if (!isset($params['order'])) {
            $params['order'] = new Order($params['id_order']);
        }

        return $this->hookDisplayAdminOrderContentShip($params) . $this->hookDisplayAdminOrderLeft($params);
    }

    public function hookActionObjectOrderCarrierUpdateAfter($params)
    {
        if (XAllegroConfiguration::get('ORDER_CARRIER_CHANGE_MAPPED_OPERATOR')
            && Validate::isLoadedObject($params['object'])
            && XAllegroOrder::exists($params['object']->id_order)
        ) {
            /** @var XAllegroOrder $allegroOrder */
            $allegroOrder = XAllegroOrder::getByOrderId($params['object']->id_order);
            $operatorDetails = XAllegroCarrier::getOperatorByIdCarrier($allegroOrder->id_xallegro_account, $params['object']->id_carrier);

            if ($operatorDetails) {
                XAllegroCarrier::updateOperator($params['object']->id_order, $operatorDetails['id_operator'], $operatorDetails['operator_name']);
            }
        }
    }

    public function hookActionOrderStatusUpdate($params)
    {
        if (XAllegroConfiguration::get('ORDER_ADD_PAYMENT_WHEN_COD')) {
            return;
        }

        /** @var XAllegroOrder $allegroOrder */
        $allegroOrder = XAllegroOrder::getByOrderId($params['id_order']);

        if (!$allegroOrder || $allegroOrder->checkout_form_content->payment->type != \x13allegro\Api\Model\Order\Enum\PaymentType::CASH_ON_DELIVERY) {
            return;
        }

        $order = new Order((int)$params['id_order']);

        if (isset($params['oldOrderStatus']) && $params['oldOrderStatus']->id) {
            /** @var OrderState $orderStateCurrent */
            $orderStateCurrent = $params['oldOrderStatus'];
        } else {
            $orderStateCurrent = new OrderState($order->current_state, $order->id_lang);
        }

        /** @var OrderState $orderStateNew */
        $orderStateNew = $params['newOrderStatus'];

        if (!$orderStateCurrent->paid
            && $orderStateNew->paid
            && empty(OrderPayment::getByOrderReference($order->reference))
        ) {
            $orderPaymentFactory = new \x13allegro\SyncManager\Order\Data\Factory\OrderPaymentFactory(
                $order,
                $allegroOrder->checkout_form_content->payment,
                $allegroOrder->checkout_form_content->summary,
                true
            );

            if ($orderPaymentFactory->build()) {
                \x13allegro\Component\Logger\Log::instance()
                    ->env(\x13allegro\Component\Logger\LogEnv::HOOK())
                    ->order($order->id)
                    ->info(\x13allegro\Component\Logger\LogType::ORDER_PAYMENT_CREATE(), [
                        'payment.id' => $allegroOrder->checkout_form_content->payment->id,
                        'payment.type' => $allegroOrder->checkout_form_content->payment->type,
                        'payment.provider' => $allegroOrder->checkout_form_content->payment->provide,
                        'isCOD' => true
                    ]);
            }
        }
    }

    public function hookActionOrderHistoryAddAfter($params)
    {
        $fulfilmentStatus = \x13allegro\Repository\FulfillmentStatusRepository::getStatusByOrderState($params['order_history']->id_order_state);

        if (empty($fulfilmentStatus)) {
            return;
        }

        \x13allegro\Component\Logger\Log::instance()->env(\x13allegro\Component\Logger\LogEnv::HOOK());

        if (false === ($result = XAllegroSyncFulfillmentStatus::sendFulfillmentStatus($params['order_history']->id_order, $fulfilmentStatus))) {
            return;
        }

        if ($this->sessionMessages === null || strpos($_SERVER['SCRIPT_NAME'], 'sync.php') !== false) {
            return;
        }

        $bulkAction = '';
        if (Tools::getValue('change_orders_status') || Tools::getIsset('submitUpdateOrderStatus')) {
            $bulkAction = '[' . $this->l('Zamówienie ID') . ': ' . $result['orderId'] . '] ';
        }

        if ($result['status']) {
            if ($result['revisionReload']) {
                $this->sessionMessages->warnings($bulkAction . $this->l('Zaktualizowano dane odnośnie zamówienia Allegro, możesz teraz zmienić jego status.'));
            } else {
                $this->sessionMessages->confirmations($bulkAction . $this->l('Pomyślnie zaktualizowano status Allegro.'));
            }
        } else {
            $this->sessionMessages->errors($bulkAction . $result['message']);
        }
    }

    public function hookActionSetInvoice($params)
    {
        if (!XAllegroOrder::exists($params['Order']->id)
            || !Configuration::get('PS_INVOICE')
            || !XAllegroConfiguration::get('IMPORT_ORDERS')
            || !XAllegroConfiguration::get('ORDER_INVOICE_AUTO_PS_INVOICE')
        ) {
            return;
        }

        \x13allegro\Component\Logger\Log::instance()->env(\x13allegro\Component\Logger\LogEnv::HOOK());

        try {
            $prestaShopInvoice = XAllegroSyncInvoice::getPrestaShopInvoice($params['Order'], Context::getContext());

            XAllegroSyncInvoice::uploadOrderInvoice(
                $params['Order']->id,
                $prestaShopInvoice['invoiceFile'],
                $prestaShopInvoice['invoiceFileName'],
                $prestaShopInvoice['invoiceNumber']
            );
        }
        catch (Exception $ex) {}
    }

    public function hookActionAdminControllerSetMedia()
    {
        if ($this->context->controller->controller_name == 'AdminProducts'
            && (version_compare(_PS_VERSION_, '9.0.0', '>=')
                || (version_compare(_PS_VERSION_, '8.0.0', '>=')
                    && \x13allegro\Adapter\FeatureFlagAdapter::isEnabled('product_page_v2')))
        ) {
            $this->context->controller->addJS(_PS_JS_DIR_ . 'admin/tinymce.inc.js');
            $this->context->controller->addJS(_PS_JS_DIR_ . 'admin/tinymce_loader.js');
        }

        if ($this->context->controller->controller_name == 'AdminProducts'
            || $this->context->controller->controller_name == 'AdminOrders'
        ) {
            $this->context->controller->addCSS(
                $this->getPathUri() . 'views/css/x13allegro.css' .
                (version_compare(_PS_VERSION_, '1.6.1.11', '>') ? '?v=' . X13_ALLEGRO_VERSION : '')
            );

            if (version_compare(_PS_VERSION_, '1.7.8.0', '>=')) {
                $this->context->controller->addCSS(
                    $this->getPathUri() . 'views/css/x13allegro-modern.css' .
                    (version_compare(_PS_VERSION_, '1.6.1.11', '>') ? '?v=' . X13_ALLEGRO_VERSION : '')
                );
            }

            $this->context->controller->addJqueryUI('ui.sortable');
            $this->context->controller->addJS(
                $this->getPathUri() . 'views/js/x13allegro.js' .
                (version_compare(_PS_VERSION_, '1.6.1.11', '>') ? '?v=' . X13_ALLEGRO_VERSION : '')
            );
        }
    }

    public function hookDisplayAdminProductsExtra($params)
    {
        $product = new Product(version_compare(_PS_VERSION_, '1.7.0.0', '>=')
            ? (isset($params['id_product']) ? (int)$params['id_product'] : null)
            : (int)Tools::getValue('id_product'));

        if (!Validate::isLoadedObject($product)) {
            return false;
        }

        $xProduct = new XAllegroProduct(null, $product->id);
        $helper = new XAllegroHelperProductExtra($xProduct);

        $this->context->smarty->assign([
            'allegroAccounts' => XAllegroAccount::getAll(),
            'productId' => $product->id,
            'productsExtraController' => $this->context->link->getAdminLink('AdminXAllegroProductsExtra', false),
            'productsExtraToken' => Tools::getAdminTokenLite('AdminXAllegroProductsExtra'),
            'productCustomForm' => $helper->generateProductCustomForm(),
            'tagManagerForm' => $helper->generateTagManagerForm(),
            'imagesAdditionalForm' => $helper->generateImagesAdditionalForm(),
            'imagesAdditionalMaxCount' => XAllegroProduct::IMAGES_ADDITIONAL_MAX,
            'descriptionsAdditionalForm' => $helper->generateDescriptionsAdditionalForm(),
            'descriptionsAdditionalMaxCount' => XAllegroProduct::DESCRIPTIONS_ADDITIONAL_MAX,
            'displayX13AllegroAdminProductsExtra' => Hook::exec('displayX13AllegroAdminProductsExtra', [
                'id_product' => $product->id
            ])
        ]);

        return $this->display(__FILE__, 'views/templates/hook/admin-products-extra.tpl');
    }

    public function hookActionProductSave($params)
    {
        if (!isset($params['id_product'])
            || !Tools::getValue('x13allegro_product_extra')
            || version_compare(_PS_VERSION_, '1.7.0.0', '>=')
        ) {
            return false;
        }

        $helper = new XAllegroHelperProductExtra(new XAllegroProduct(null, $params['id_product']));
        $helper->setAccountId((int)Tools::getValue('xallegro_product_custom_account'));

        return $helper->processProductExtra($this->context->controller->errors);
    }

    public function hookActionObjectProductDeleteAfter($params)
    {
        if (Validate::isLoadedObject($params['object'])) {
            $hookParams = [
                'method' => 'productDeleted',
                'id_shop' => $this->context->shop->id,
                'id_product' => $params['object']->id
            ];

            (new \x13allegro\SyncManager\Offer\Updater\PublicationStatusEnd())->updateFromHook($hookParams);
            \x13allegro\Repository\ObjectAssociationRepository::deleteAssociationWithProduct($params['object']->id);

            \x13allegro\Component\Logger\Log::instance()
                ->env(\x13allegro\Component\Logger\LogEnv::HOOK())
                ->product($this->context->shop->id, $params['object']->id)
                ->info(\x13allegro\Component\Logger\LogType::ASSOCIATION_DELETE(), ['hook' => 'actionObjectProductDeleteAfter']);
        }
    }

    public function hookActionObjectCombinationDeleteAfter($params)
    {
        if (Validate::isLoadedObject($params['object'])) {
            $hookParams = [
                'method' => 'combinationDeleted',
                'id_shop' => $this->context->shop->id,
                'id_product' => $params['object']->id_product,
                'id_product_attribute' => $params['object']->id
            ];

            (new \x13allegro\SyncManager\Offer\Updater\PublicationStatusEnd())->updateFromHook($hookParams);
            \x13allegro\Repository\ObjectAssociationRepository::deleteAssociationWithProductAttribute($params['object']->id);

            \x13allegro\Component\Logger\Log::instance()
                ->env(\x13allegro\Component\Logger\LogEnv::HOOK())
                ->product($this->context->shop->id, $params['object']->id_product, $params['object']->id)
                ->info(\x13allegro\Component\Logger\LogType::ASSOCIATION_DELETE(), ['hook' => 'actionObjectCombinationDeleteAfter']);
        }
    }

    public function hookActionObjectProductUpdateAfter($params)
    {
        if (Validate::isLoadedObject($params['object'])) {
            if (!$params['object']->active) {
                (new \x13allegro\SyncManager\Offer\Updater\PublicationStatusEnd())->updateFromHook([
                    'method' => 'productNotActive',
                    'id_shop' => $this->context->shop->id,
                    'id_product' => $params['object']->id
                ]);
            } else {
                (new \x13allegro\SyncManager\Offer\Updater\PublicationStatusActive())->updateFromHook([
                    'method' => 'productActive',
                    'id_product' => $params['object']->id
                ]);
            }
        }
    }

    public function hookActionObjectManufacturerDeleteAfter($params)
    {
        Db::getInstance()->delete('xallegro_manufacturer', 'id_manufacturer = ' . (int)$params['object']->id);
    }

    public function hookActionObjectGroupDeleteAfter($params)
    {
        $customerGroupsSelected = json_decode(XAllegroConfiguration::get('REGISTER_CUSTOMER_GROUP'), true);
        $customerGroupDefault = (int)XAllegroConfiguration::get('REGISTER_CUSTOMER_GROUP_DEFAULT');
        $priceGroupDefault = (int)XAllegroConfiguration::get('AUCTION_PRICE_CUSTOMER_GROUP');
        $psCustomerGroupDefault = (int)Configuration::get('PS_CUSTOMER_GROUP');
        $psUndefinedGroupDefault = (int)Configuration::get('PS_UNIDENTIFIED_GROUP');

        if (array_key_exists($params['object']->id, $customerGroupsSelected)) {
            unset($customerGroupsSelected[$params['object']->id]);
        }

        if (empty($customerGroupsSelected)) {
            $customerGroupsSelected[$psCustomerGroupDefault] = true;
        }

        if ($params['object']->id === $customerGroupDefault) {
            XAllegroConfiguration::updateValue('REGISTER_CUSTOMER_GROUP_DEFAULT', $psCustomerGroupDefault);

            if (!array_key_exists($psCustomerGroupDefault, $customerGroupsSelected)) {
                $customerGroupsSelected[$psCustomerGroupDefault] = true;
            }
        }

        XAllegroConfiguration::updateValue('REGISTER_CUSTOMER_GROUP', json_encode($customerGroupsSelected));

        if ($params['object']->id === $priceGroupDefault) {
            XAllegroConfiguration::updateValue('AUCTION_PRICE_CUSTOMER_GROUP', $psUndefinedGroupDefault);
        }
    }

    public function hookActionObjectStockAvailableUpdateAfter($params)
    {
        if (strpos($_SERVER['SCRIPT_NAME'], 'sync.php') === false) {
            (new \x13allegro\SyncManager\Offer\Updater\QuantityUpdater())->updateFromHook([
                'id_product' => $params['object']->id_product,
                'id_product_attribute' => $params['object']->id_product_attribute
            ]);

            (new \x13allegro\SyncManager\Offer\Updater\PublicationStatusActive())->updateFromHook([
                'method' => 'quantityUpdate',
                'id_product' => $params['object']->id_product,
                'id_product_attribute' => $params['object']->id_product_attribute
            ]);
        }
    }

    public function hookActionAdminPerformanceControllerAfter()
    {
        if (version_compare(_PS_VERSION_, '1.7.1.0', '<')) {
            $this->hookActionClearSf2Cache();
        }
    }

    public function hookActionClearSf2Cache()
    {
        XAllegroAutoLoader::getInstance()
            ->generateClassIndex()
            ->autoload();

        (new \x13allegro\Component\Cache\Json())->clearAll();
    }

    public function hookActionDispatcher()
    {
        if (version_compare(_PS_VERSION_, '1.7.1.0', '<')
            && Tools::getValue('controller') == 'AdminOrders'
            && Tools::isSubmit('submitShippingNumber')
            && false !== ($orderId = Tools::getValue('id_order'))
            && XAllegroOrder::exists($orderId)
            && !XAllegroConfiguration::get('ORDER_SEND_CUSTOMER_MAIL')
            && Configuration::get('PS_MAIL_METHOD') != 3
        ) {
            Configuration::set('PS_MAIL_METHOD', 3);
        }

        if (strpos($_SERVER['SCRIPT_NAME'], 'sync.php') !== false) {
            Configuration::set('PS_SMARTY_CLEAR_CACHE', 'never');
        }
    }

    public function hookActionAdminX13GPSRResponsibleManufacturerFormModifier(array &$params)
    {
        $x13gpsrAdapter = new \x13allegro\Adapter\Module\x13gpsrAdapter();
        $objectId = isset($params['object']) ? $params['object']->id : (int)Tools::getValue('id_x13gpsr_responsible_manufacturer');

        $params['fields'][0]['form']['input'][] = [
            'name' => 'html_content',
            'type' => 'html',
            'html_content' => '<h4 style="font-size:17px">' . $this->displayName . '</h4>'
        ];
        $params['fields'][0]['form']['input'][] = [
            'name' => 'html_content',
            'type' => 'html',
            'html_content' => $x13gpsrAdapter->getFormModifier(\x13allegro\Adapter\Module\x13gpsrAdapter::RESPONSIBLE_PRODUCER, $objectId)
        ];
    }

    public function hookActionAdminX13GPSRResponsibleManufacturerControllerSaveAfter()
    {
        $responsibleManufacturerId = (int)Tools::getValue('id_x13gpsr_responsible_manufacturer');
        $allegroResponsibleProducer = Tools::getValue('x13allegro_responsible_producer', []);

        if ($responsibleManufacturerId && !empty($allegroResponsibleProducer)) {
            $x13gpsrAdapter = new \x13allegro\Adapter\Module\x13gpsrAdapter();
            $x13gpsrAdapter->assignResponsibleManufacturer($responsibleManufacturerId, $allegroResponsibleProducer);
        }
    }

    public function hookActionObjectXGpsrResponsibleManufacturerDeleteAfter($params)
    {
        $x13gpsrAdapter = new \x13allegro\Adapter\Module\x13gpsrAdapter();
        $x13gpsrAdapter->removeResponsibleManufacturer((int)$params['object']->id);
    }

    public function hookActionAdminX13GPSRResponsiblePersonFormModifier(array &$params)
    {
        $x13gpsrAdapter = new \x13allegro\Adapter\Module\x13gpsrAdapter();
        $objectId = isset($params['object']) ? $params['object']->id : (int)Tools::getValue('id_x13gpsr_responsible_person');

        $params['fields'][0]['form']['input'][] = [
            'name' => 'html_content',
            'type' => 'html',
            'html_content' => '<h4 style="font-size:17px">' . $this->displayName . '</h4>'
        ];
        $params['fields'][0]['form']['input'][] = [
            'name' => 'html_content',
            'type' => 'html',
            'html_content' => $x13gpsrAdapter->getFormModifier(\x13allegro\Adapter\Module\x13gpsrAdapter::RESPONSIBLE_PERSON, $objectId)
        ];
    }

    public function hookActionAdminX13GPSRResponsiblePersonControllerSaveAfter()
    {
        $responsiblePersonId = (int)Tools::getValue('id_x13gpsr_responsible_person');
        $allegroResponsiblePerson = Tools::getValue('x13allegro_responsible_person', []);

        if ($responsiblePersonId && !empty($allegroResponsiblePerson)) {
            $x13gpsrAdapter = new \x13allegro\Adapter\Module\x13gpsrAdapter();
            $x13gpsrAdapter->assignResponsiblePerson($responsiblePersonId, $allegroResponsiblePerson);
        }
    }

    public function hookActionObjectXGpsrResponsiblePersonDeleteAfter($params)
    {
        $x13gpsrAdapter = new \x13allegro\Adapter\Module\x13gpsrAdapter();
        $x13gpsrAdapter->removeResponsiblePerson((int)$params['object']->id);
    }

    public function hookActionX13AllegroOrderInvoiceList(array $params)
    {
        if (!isset($params['id_order'])) {
            throw new InvalidArgumentException('Nie podano ID zamówienia PrestaShop.');
        }

        $allegroOrder = XAllegroOrder::getByOrderId($params['id_order']);
        if (!$allegroOrder) {
            return false;
        }

        \x13allegro\Component\Logger\Log::instance()->env(\x13allegro\Component\Logger\LogEnv::HOOK());

        try {
            $api = new \x13allegro\Api\XAllegroApi(new XAllegroAccount($allegroOrder->id_xallegro_account));
            return $api->order()->checkoutForms($allegroOrder->checkout_form)->invoices()->getInvoices()->invoices;
        }
        catch (\x13allegro\Exception\ModuleException $ex) {
            throw new RuntimeException((string)$ex);
        }
    }

    public function hookActionX13AllegroOrderInvoiceUpload(array $params)
    {
        if (!isset($params['id_order'])) {
            throw new InvalidArgumentException('Nie podano ID zamówienia PrestaShop.');
        }
        if (!isset($params['invoice_file'])) {
            throw new InvalidArgumentException('Nie podano pliku faktury do wysłania.');
        }
        if (!isset($params['invoice_filename'])) {
            throw new InvalidArgumentException('Nie podano nazwy pliku faktury do wysłania.');
        }

        \x13allegro\Component\Logger\Log::instance()->env(\x13allegro\Component\Logger\LogEnv::HOOK());

        try {
            return XAllegroSyncInvoice::uploadOrderInvoice(
                $params['id_order'],
                $params['invoice_file'],
                $params['invoice_filename'],
                isset($params['invoice_number']) ? $params['invoice_number'] : ''
            );
        }
        catch (\x13allegro\Exception\ModuleException $ex) {
            throw new RuntimeException((string)$ex);
        }
    }

    public function install()
    {
        if (!parent::install() || !$this->config->installDependencies()) {
            return false;
        }

        return true;
    }

    public function uninstall()
    {
        if (!parent::uninstall() || !$this->config->uninstallDependencies() ) {
            return false;
        }

        return true;
    }

    /**
     * @return array
     */
    public function runUpgradeModule()
    {
        $result = parent::runUpgradeModule();

        XAllegroAutoLoader::getInstance()->autoload();
        \x13allegro\Component\Logger\Log::instance()->info(\x13allegro\Component\Logger\LogType::MODULE_UPGRADE_RUN(), $result);

        return $result;
    }

    /**
     * @param string $module_name
     * @return bool
     */
    public static function getUpgradeStatus($module_name)
    {
        $result = parent::getUpgradeStatus($module_name);

        XAllegroAutoLoader::getInstance()->autoload();
        \x13allegro\Component\Logger\Log::instance()->info(\x13allegro\Component\Logger\LogType::MODULE_UPGRADE_STATUS(), $result);

        return $result;
    }

    /**
     * @return bool
     */
    public function reinstallTabs()
    {
        return $this->config->reinstallTabs();
    }

    /**
     * @return bool
     */
    public function reinstallMetas()
    {
        return $this->config->reinstallMetas();
    }

    /**
     * @return bool
     */
    public function installDemoTemplates()
    {
        return $this->config->installDemoTemplates();
    }

    /**
     * @param string $message
     * @param string $className
     * @param bool $prefix
     * @return string
     */
    public function renderAdminMessage($message, $className = 'warning', $prefix = true)
    {
        return str_replace($this->displayName, '<span class="badge badge-' . $className . '"><b>' . $this->displayName . '</b></span>', ($prefix ? $this->displayName . ' ' : '') . $message);
    }
}
