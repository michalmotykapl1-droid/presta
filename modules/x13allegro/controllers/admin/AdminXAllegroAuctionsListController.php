<?php
/**
 * 2007-2025 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2025 PrestaShop SA
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */

require_once (dirname(__FILE__) . '/../../x13allegro.php');

use x13allegro\Api\XAllegroApi;
use x13allegro\Api\XAllegroApiTools;
use x13allegro\Api\DataProvider\MarketplacesProvider;
use x13allegro\Api\DataProvider\OfferProvider;
use x13allegro\Api\DataUpdater\Updater;
use x13allegro\Api\DataUpdater\EntityUpdaterFinder;
use x13allegro\Api\Model\Marketplace\Enum\Marketplace;
use x13allegro\Api\Model\Marketplace\Enum\MarketplacePublicationStatus;
use x13allegro\Api\Model\Offers\OfferUpdate;
use x13allegro\Api\Model\Offers\Publication;
use x13allegro\Api\Model\Offers\Stock;
use x13allegro\Api\Model\Offers\Enum\PublicationStatus;
use x13allegro\Component\Logger\LogType;

final class AdminXAllegroAuctionsListController extends XAllegroController
{
    // START: Progi do weryfikacji ceny
    private const MIN_WEIGHT_FOR_CHECK = 6.0;     // Sprawdzaj tylko produkty cięższe niż ta waga (w kg)
    private const REF_PRODUCT_MIN_WEIGHT = 0.2;   // Minimalna waga produktu referencyjnego (np. 0.2kg)
    private const REF_PRODUCT_MAX_WEIGHT = 2.0;   // Maksymalna waga produktu referencyjnego (np. 2.0kg)
    private const PRICE_CHECK_MULTIPLIER = 3.0;   // Mnożnik dla ceny referencyjnej
    private const PRICE_FALLBACK_THRESHOLD = 80.0; // Cena, powyżej której produkt bez ref. jest uznawany za OK
    // KONIEC
    protected $allegroAutoLogin = true;
    protected $allegroAccountSwitch = true;

    public $multiple_fieldsets = true;

    /** @var XAllegroAuction */
    protected $object;

    protected $_default_pagination = 50;

    /** @var array */
    private $auctions = [];

    public function __construct()
    {
        $this->table = 'xallegro_auction';
        $this->identifier = 'id_xallegro_auction';
        $this->className = 'XAllegroAuction';
        $this->list_no_link = true;

        parent::__construct();

        $this->tabAccess = Profile::getProfileAccess($this->context->employee->id_profile, Tab::getIdFromClassName('AdminXAllegroAuctionsList'));
        $this->tpl_folder = 'x_allegro_auctions/';

        // Połączone komunikaty
        $this->_conf[101] = $this->l('Usunięto powiązanie oferty.');
        $this->_conf[102] = $this->l('Utworzono powiązanie oferty z produktem.');
        $this->_conf[103] = $this->l('Wybrane oferty zostały ukryte.');
    }

    public function init()
    {
        // offer format
        if (Tools::getIsset('offerType') || !isset($this->allegroCookie->{$this->getAllegroCookieFilter('offerType')})) {
            $this->allegroCookie->{$this->getAllegroCookieFilter('offerType')} = Tools::getValue('offerType', 'buy_now');
        }

        // offer status
        if (Tools::getIsset('xallegroFilterStatus') || !isset($this->allegroCookie->{$this->getAllegroCookieFilter('offerStatus')})) {
            $this->allegroCookie->{$this->getAllegroCookieFilter('offerStatus')} = Tools::getValue('xallegroFilterStatus', 'active');
        }

        $currentOfferStatus = $this->allegroCookie->{$this->getAllegroCookieFilter('offerStatus')};

        // offer marketplace
        if (Tools::getIsset('xallegroFilterMarketplace') || !isset($this->allegroCookie->{$this->getAllegroCookieFilter('offerMarketplace')})) {
            $this->allegroCookie->{$this->getAllegroCookieFilter('offerMarketplace')} = Tools::getValue('xallegroFilterMarketplace', 'all');
        }

        $_GET['offerType'] = $this->allegroCookie->{$this->getAllegroCookieFilter('offerType')};

        if ($this->tabAccess['edit'] === '1') {
            $this->bulk_actions['update'] = array(
                'text' => $this->l('Aktualizuj wybrane'),
                'icon' => 'icon-cogs bulkUpdate',
            );

            if ($this->allegroCookie->{$this->getAllegroCookieFilter('offerType')} === 'buy_now') {
                $this->bulk_actions['auto_renew'] = array(
                    'text' => $this->l('Ustaw auto wznawianie'),
                    'icon' => 'icon-cogs bulkAutoRenew',
                );

                $this->bulk_actions['redo'] = array(
                    'text' => $this->l('Wznów wybrane'),
                    'icon' => 'icon-repeat bulkRedo',
                );
            }

            $this->bulk_actions['finish'] = array(
                'text' => $this->l('Zakończ wybrane'),
                'icon' => 'icon-flag-checkered bulkFinish',
            );

            $this->bulk_actions['unbind'] = array(
                'text' => $this->l('Usuń powiązania'),
                'icon' => 'icon-unlink bulkUnbind'
            );

            if (stripos($currentOfferStatus, 'ended') !== false || $currentOfferStatus === 'all') {
                $this->bulk_actions['hide'] = array(
                    'text' => $this->l('Ukryj (wyczyść z listy)'),
                    'icon' => 'icon-eye-slash bulkHide',
                    'confirm' => $this->l('Czy na pewno chcesz ukryć te oferty z listy? Ta akcja wpłynie tylko na widoczność w module.')
                );
            }
        }

        parent::init();
    }
    
    public function setMedia($isNewTheme = false)
    {
        parent::setMedia($isNewTheme);

        $this->addJqueryPlugin('autocomplete');
        $this->addJqueryUI('ui.sortable');

        $this->addJS($this->module->getPathUri() . 'views/js/tinymce/tinymce.min.js');
        $this->addJS($this->module->getPathUri() . 'views/js/tinymce/jquery.tinymce.min.js');

        // Dołączenie JS, który nadpisuje funkcję zbierania zazaczonych ofert.
        $this->addJS($this->module->getPathUri().'views/js/stats_loader.js?v=20250803');
    }

    public function initToolbarTitle()
    {
        parent::initToolbarTitle();

        if ($this->allegroCookie->{$this->getAllegroCookieFilter('offerType')} === 'auction') {
            $title = 'Licytacje';
        }
        else {
            $title = 'Kup teraz';
        }

        if ($this->display !== 'edit' && method_exists($this, 'addMetaTitle')) {
            $this->addMetaTitle($title);
            $this->toolbar_title = $title;
        }
    }

    public function initPageHeaderToolbar()
    {
        if (empty($this->display))
        {
            $this->page_header_toolbar_btn['allegro_buy_now'] = array(
                'href' => $this->context->link->getAdminLink('AdminXAllegroAuctionsList') . (Tools::getIsset('id_xallegro_account') ? '&id_xallegro_account=' . Tools::getValue('id_xallegro_account') : '') . '&offerType=buy_now',
                'desc' => $this->l('Kup teraz'),
                'icon' => 'process-icon-cart-arrow-down icon-cart-arrow-down',
                'class' => 'x-allegro_buy_now'
            );

            $this->page_header_toolbar_btn['allegro_auction'] = array(
                'href' => $this->context->link->getAdminLink('AdminXAllegroAuctionsList') . (Tools::getIsset('id_xallegro_account') ? '&id_xallegro_account=' . Tools::getValue('id_xallegro_account') : '') . '&offerType=auction',
                'desc' => $this->l('Licytacje'),
                'icon' => 'process-icon-gavel icon-gavel',
                'class' => 'x-allegro_auction'
            );
        }

        parent::initPageHeaderToolbar();
    }

    public function initProcess()
    {
        parent::initProcess();

        if ((Tools::isSubmit('edit' . $this->table) || Tools::isSubmit('delete_link')) && Tools::getValue('id_auction')) {
            if ($this->tabAccess['edit'] === '1') {
                $this->display = 'edit';
            } else {
                $this->errors[] = $this->l('Nie masz uprawnień do edycji w tym miejscu.');
            }
        }
    }

    public function renderList()
    {
        $this->fields_list = $this->getFieldsList('default');
        // Bulk: Pobierz VAT z Allegro (do bazy)
        $this->bulk_actions['fetchVat'] = [
            'text' => $this->l('Pobierz VAT z Allegro (do bazy)'),
            'confirm' => $this->l('Pobrać VAT dla zaznaczonych ofert?'),
        ];
    

        if ($this->tabAccess['edit'] === '1') {
            $this->addRowAction('xAuctionBind');
            $this->addRowAction('xAuctionUpdate');
            $this->addRowAction('xAuctionRedo');
        }

        $this->addRowAction('xAuctionUrl');

        if ($this->tabAccess['edit'] === '1') {
            $this->addRowAction('xAuctionEditBind');
            $this->addRowAction('xAuctionUnbind');
            $this->addRowAction('xAuctionEditProduct');
            $this->addRowAction('xAuctionFinish');
        }

        $helper = new HelperList();
        $this->setHelperDisplay($helper);

        $helper->simple_header = false;
        $helper->show_toolbar = true;
        $helper->toolbar_scroll = true;
        $helper->table = $this->table;
        $helper->identifier = $this->identifier;
        $helper->orderBy = $this->context->cookie->xallegroauctionslistxallegro_auctionOrderby;
        $helper->orderWay = strtoupper($this->context->cookie->xallegroauctionslistxallegro_auctionOrderway);
        $helper->tpl_vars = $this->tpl_list_vars;
        $helper->tpl_delete_link_vars = $this->tpl_delete_link_vars;
        $helper->currentIndex = $this->context->link->getAdminLink('AdminXAllegroAuctionsList') . '&id_xallegro_account=' . $this->allegroApi->getAccount()->id . '&offerType=' . Tools::getValue('offerType');

        // override default action attribute
        $helper->tpl_vars['override_action'] = $helper->currentIndex;

        // filters
        $helper->tpl_vars['xallegroFilterStatus'] = $this->allegroCookie->{$this->getAllegroCookieFilter('offerStatus')};
        $helper->tpl_vars['xallegroFilterMarketplace'] = $this->allegroCookie->{$this->getAllegroCookieFilter('offerMarketplace')};
        $helper->tpl_vars['filterByProductization'] = (isset($this->context->cookie->submitFilterxallegro_productizationNeeded) && $this->context->cookie->submitFilterxallegro_productizationNeeded);
        $helper->tpl_vars['marketplaceFilters'] = Marketplace::toChoseList();

        $auctionFieldsList = $this->getFieldsList();
        $auctionFieldsListSettings = json_decode(XAllegroConfiguration::get('AUCTION_FIELDS_LIST_SETTINGS'), true);

        if (isset($auctionFieldsListSettings['default'])) {
            $auctionFieldsListSettingsMissing = array_diff(
                array_keys($auctionFieldsList),
                array_keys($auctionFieldsListSettings['default'])
            );

            // Poprawka: Zabezpieczenie przed błędem, gdy klucz do wstawienia nie istnieje.
            foreach ($auctionFieldsListSettingsMissing as $field) {
                $insertAfterKey = substr($field, 0, -3);
                if (array_key_exists($insertAfterKey, $auctionFieldsListSettings['default'])) {
                    $auctionFieldsListSettings['default'] = $this->arrayInsertAfter(
                        $auctionFieldsListSettings['default'],
                        $insertAfterKey,
                        [$field => '0']
                    );
                } else {
                    // Fallback: dodaj na końcu, jeśli klucz nie został znaleziony
                    $auctionFieldsListSettings['default'][$field] = '0';
                }
            }
        }

        $helper->tpl_vars['auctionFieldsList'] = $auctionFieldsList;
        $helper->tpl_vars['auctionFieldsListSettings'] = $auctionFieldsListSettings;

        $this->getAuctionList();

        $helper->listTotal = $this->_listTotal;

        foreach ($this->actions_available as $action) {
            if (!in_array($action, $this->actions) && isset($this->$action) && $this->$action) {
                $this->actions[] = $action;
            }
        }

        $list = $helper->generateList($this->_list, $this->fields_list);

        // ZMIANA: Renderuj statystyki tylko, jeśli opcja jest włączona w konfiguracji
        $statsPlaceholder = '';
        if (XAllegroConfiguration::get('X13_ALLEGRO_STATS_ENABLED')) {
            $statsPlaceholder = $this->renderStatisticsPlaceholder();
        }

        return $statsPlaceholder . $list;
    }

    public function renderForm()
    {
        if (!Validate::isLoadedObject($this->object) && (Shop::isFeatureActive() && Shop::getContext() !== Shop::CONTEXT_SHOP)) {
            $this->fields_form[]['form'] = [
                'legend' => [
                    'title' => $this->l('Powiąż ofertę z produktem')
                ],
                'warning' => $this->l('Wybierz konkretny kontekst sklepu aby powiązać ofertę z PrestaShop')
            ];
        }
        else {
            $this->fields_form[]['form'] = [
                'legend' => [
                    'title' => Validate::isLoadedObject($this->object) ? $this->l('Pogląd powiązania oferty z produktem') : $this->l('Powiąż ofertę z produktem'),
                ],
                'description' => ((Shop::isFeatureActive() && Shop::getContext() !== Shop::CONTEXT_SHOP) || $this->context->shop->id !== $this->object->id_shop
                    ? $this->l('Oferta powiązana z produktem w sklepie') . ': ' . (new Shop($this->object->id_shop))->name
                    : null
                ),
                'submit' => [
                    'title' => $this->l('Zapisz'),
                    'class' => (Validate::isLoadedObject($this->object) ? 'hidden' : 'btn btn-default pull-right')
                ],
                'input' => [
                    ['type' => 'hidden', 'name' => 'id_auction'],
                    ['type' => 'hidden', 'name' => 'closed'],
                    ['type' => 'hidden', 'name' => 'closedDb'],
                    ['type' => 'hidden', 'name' => 'start'],
                    ['type' => 'hidden', 'name' => 'startDb'],
                    ['type' => 'hidden', 'name' => 'id_xallegro_account'],
                    ['type' => 'hidden', 'name' => 'id_product'],
                    ['type' => 'hidden', 'name' => 'id_shop'],
                    ['type' => 'hidden', 'name' => 'offerType'],
                    [
                        'type' => 'text',
                        'label' => $this->l('Nazwa produktu'),
                        'name' => 'name',
                        'size' => 70,
                        'class' => 'custom_ac_input',
                        'desc' => (Validate::isLoadedObject($this->object) ? false : $this->l('Zacznij wpisywać pierwsze litery nazwy produktu, kodu referencyjnego lub jego ID, następnie wybierz produkt z listy rozwijalnej')),
                        'disabled' => (Validate::isLoadedObject($this->object) ? true : null)
                    ],
                    [
                        'type' => 'select',
                        'label' => $this->l('Nazwa atrybutu'),
                        'name' => 'id_product_attribute',
                        'options' => [
                            'query' => [
                                ['id' => 0, 'name' => (Validate::isLoadedObject($this->object) ? $this->object->name_attribute : 'Brak')]
                            ],
                            'id' => 'id',
                            'name' => 'name'
                        ],
                        'disabled' => (Validate::isLoadedObject($this->object) ? true : null)
                    ]
                ],
                'buttons' => [
                    [
                        'href' => $this->context->link->getAdminLink('AdminXAllegroAuctionsList') . '&offerType=' . Tools::getValue('offerType'),
                        'title' => $this->l('Wróć'),
                        'class' => 'pull-left',
                        'icon' => 'process-icon-back'
                    ]
                ]
            ];
        }

        $this->fields_form[]['form'] = [
            'legend' => [
                'title' => $this->l('Informacje o ofercie pobrane z Allegro'),
            ],
            'input' => [
                [
                    'type' => 'text',
                    'label' => $this->l('Tytuł oferty'),
                    'name' => 'title',
                    'size' => 70,
                    'disabled' => true
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('Cena Kup Teraz'),
                    'name' => 'price_buy_now',
                    'size' => 10,
                    'class' => 'fixed-width-sm',
                    'disabled' => true,
                    'suffix' => ' zł',
                    'callback' => 'priceFormat',
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('Pozostała ilość przedmiotów'),
                    'name' => 'quantity',
                    'size' => 10,
                    'class' => 'fixed-width-sm',
                    'disabled' => true
                ],
                [
                    'type' => $this->bootstrap ? 'switch' : 'radio',
                    'label' => $this->l('Zaplanowana'),
                    'name' => 'start',
                    'class' => 't',
                    'is_bool' => true,
                    'values' => [
                        ['id' => 'start_on', 'value' => 1, 'label' => $this->l('Tak')],
                        ['id' => 'start_off', 'value' => 0, 'label' => $this->l('Nie')]
                    ],
                    'disabled' => true
                ],
                [
                    'type' => $this->bootstrap ? 'switch' : 'radio',
                    'label' => $this->l('Zakończona'),
                    'name' => 'closed',
                    'class' => 't',
                    'is_bool' => true,
                    'values' => [
                        ['id' => 'closed_on', 'value' => 1, 'label' => $this->l('Tak')],
                        ['id' => 'closed_off', 'value' => 0, 'label' => $this->l('Nie')]
                    ],
                    'disabled' => true
                ]
            ]
        ];

        if (Validate::isLoadedObject($this->object)) {
            $this->fields_form[]['form'] = [
                'legend' => [
                    'title' => $this->l('Informacje o ofercie przechowywane przez moduł')
                ],
                'input' => [
                    [
                        'type' => 'text',
                        'label' => $this->l('Cena Kup Teraz'),
                        'name' => 'priceBuyNowDb',
                        'size' => 10,
                        'class' => 'fixed-width-sm',
                        'disabled' => true,
                        'suffix' => ' zł',
                        'callback' => 'priceFormat',
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Pozostała ilość przedmiotów'),
                        'name' => 'quantityDb',
                        'size' => 10,
                        'class' => 'fixed-width-sm',
                        'disabled' => true
                    ],
                    [
                        'type' => $this->bootstrap ? 'switch' : 'radio',
                        'label' => $this->l('Zaplanowana'),
                        'name' => 'startDb',
                        'class' => 't',
                        'is_bool' => true,
                        'values' => [
                            ['id' => 'start_on', 'value' => 1, 'label' => $this->l('Tak')],
                            ['id' => 'start_off', 'value' => 0, 'label' => $this->l('Nie')]
                        ],
                        'disabled' => true
                    ],
                    [
                        'type' => $this->bootstrap ? 'switch' : 'radio',
                        'label' => $this->l('Zakończona'),
                        'name' => 'closedDb',
                        'class' => 't',
                        'is_bool' => true,
                        'values' => [
                            ['id' => 'closed_on', 'value' => 1, 'label' => $this->l('Tak')],
                            ['id' => 'closed_off', 'value' => 0, 'label' => $this->l('Nie')]
                        ],
                        'disabled' => true,
                        'auctionDbInfo' => (Validate::isLoadedObject($this->object) && $this->object->closedDb && !$this->object->startDb
                            ? 'Ta oferta została zamknięta w bazie danych.<br>'
                                . 'Jeśli nie zgadza się to ze stanem faktycznym, należy wymusić stan oferty.<br>'
                                . 'Możesz to zrobić <a href="' . $this->context->link->getAdminLink('AdminXAllegroConfiguration') . '#xallegro_configuration_fieldset_cron" target="_blank">TUTAJ</a>, klikając na przycisk "Wymuś stan ofert według Allegro".'
                            : null)
                    ],
                    [
                        'type' => $this->bootstrap ? 'switch' : 'radio',
                        'label' => $this->l('Zarchiwizowana'),
                        'name' => 'archived',
                        'class' => 't',
                        'is_bool' => true,
                        'values' => [
                            ['id' => 'archived_on', 'value' => 1, 'label' => $this->l('Tak')],
                            ['id' => 'archived_off', 'value' => 0, 'label' => $this->l('Nie')]
                        ],
                        'disabled' => true,
                        'auctionDbInfo' => (Validate::isLoadedObject($this->object) && $this->object->archived
                            ? 'Ta oferta została zarchiwizowana w bazie danych, dnia <strong>' . (new DateTime($this->object->archived_date))->format('d.m.Y H:i') . '</strong>.<br>'
                                . 'Jeśli nie zgadza się to ze stanem faktycznym, należy wymusić stan oferty.<br>'
                                . 'Możesz to zrobić <a href="' . $this->context->link->getAdminLink('AdminXAllegroConfiguration') . '#xallegro_configuration_fieldset_cron" target="_blank">TUTAJ</a>, klikając na przycisk "Wymuś stan ofert według Allegro".'
                            : null)
                    ]
                ]
            ];
        }

        $this->show_form_cancel_button = false;

        return parent::renderForm();
    }

    protected function loadObject($opt = false)
    {
        if (!$this->allegroApi instanceof XAllegroApi) {
            $this->object = null;
            return false;
        }

        if (Validate::isLoadedObject($this->object)) {
            return $this->object;
        }

        $this->object = (new PrestaShopCollection(XAllegroAuction::class))
            ->where('id_auction', '=', Tools::getValue('id_auction'))
            ->getFirst();

        if ($this->object) {
            $productObj = new Product($this->object->id_product, true, $this->allegroApi->getAccount()->id_language, $this->object->id_shop);
            $productObjAttr = $productObj->getAttributeCombinationsById($this->object->id_product_attribute, $this->allegroApi->getAccount()->id_language);

            $this->object->name = Product::getProductName($this->object->id_product, $this->object->id_product_attribute, $this->allegroApi->getAccount()->id_language);
            $this->object->name_attribute = (!empty($productObjAttr) ? $productObjAttr[0]['group_name'] . ' - ' . $productObjAttr[0]['attribute_name'] : '');
            $this->object->priceBuyNowDb = $this->object->price_buy_now;
            $this->object->quantityDb = (int)$this->object->quantity;
            $this->object->closedDb = (int)$this->object->closed;
            $this->object->startDb = (int)$this->object->start;

            if (!Validate::isLoadedObject($productObj)) {
                $this->errors[] = $this->l('Powiązanie odnosi się do nieistniejącego produktu.');
            }
        }
        else {
            $this->object = new XAllegroAuction();
            $this->object->id_xallegro_account = (int)$this->allegroApi->getAccount()->id;
            $this->object->id_auction = Tools::getValue('id_auction');
            $this->object->id_shop = (int)$this->context->shop->id;
            $this->object->id_shop_group = (int)$this->context->shop->id_shop_group;
            $this->object->priceBuyNowDb = '0.00';
            $this->object->quantityDb = 0;
            $this->object->closedDb = 0;
            $this->object->startDb = 0;
        }

        try {
            $offer = (new OfferProvider($this->allegroApi, true))->getOfferProductDetails($this->object->id_auction);
            $priceBuyNow = ($offer->sellingMode->price ? $offer->sellingMode->price->amount : 0);

            $this->object->title = $offer->name;
            $this->object->price_buy_now = number_format($priceBuyNow, 2, '.', '');
            $this->object->quantity = $offer->stock->available;
            $this->object->closed = (in_array($offer->publication->status, [PublicationStatus::INACTIVE, PublicationStatus::ENDED]) ? 1 : 0);
            $this->object->start = (in_array($offer->publication->status, [PublicationStatus::INACTIVE, PublicationStatus::ACTIVATING]) ? 1 : 0);

            $marketplaceProvider = new MarketplacesProvider($offer->publication->marketplaces->base->id);
            $marketplaces = [];
            $marketplaces[] = [
                'id' => $offer->publication->marketplaces->base->id,
                'name' => $marketplaceProvider->getMarketplaceName(),
                'offerUrl' => $marketplaceProvider->getMarketplaceOfferUrl($offer->id, $this->allegroApi->getAccount()->sandbox)
            ];

            foreach ($offer->publication->marketplaces->additional as $marketplace) {
                if (!Marketplace::isValid($marketplace->id)) {
                    continue;
                }

                $marketplaceProvider = new MarketplacesProvider($marketplace->id);
                $marketplaces[] = [
                    'id' => $marketplace->id,
                    'name' => $marketplaceProvider->getMarketplaceName(),
                    'offerUrl' => $marketplaceProvider->getMarketplaceOfferUrl($offer->id, $this->allegroApi->getAccount()->sandbox)
                ];
            }

            // @todo fix when refactoring offer association preview
            $this->tpl_form_vars['offerMarketplaces'] = $marketplaces;
        }
        catch (Exception $ex) {
            $this->errors[] = (string)$ex;
            $this->object = null;
            return false;
        }

        $this->object->offerType = Tools::getValue('offerType');

        return $this->object;
    }

    public function postProcess()
    {
        if (Tools::isSubmit('delete_link') && $this->tabAccess['edit'] === '1') {
            XAllegroAuction::deleteAuctions([Tools::getValue('id_auction')]);
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminXAllegroAuctionsList') . '&conf=101' . '&offerType=' . Tools::getValue('offerType'));
        }
        else if (Tools::getValue('action')) {
            $method = 'process' . Tools::toCamelCase(Tools::getValue('action'), true);
            if (method_exists($this, $method)) {
                return $this->$method();
            }
        }

        return parent::postProcess();
    }

    public function processSave()
    {
        $this->loadObject();

        if (Validate::isLoadedObject($this->object)) {
            return false;
        }

        $this->validateRules('XAllegroAuction');

        if (!Tools::getValue('id_product')) {
            $this->errors[] = $this->l('Brak wybranego produktu do powiązania');
        }

        if (!empty($this->errors)) {
            $this->display = 'edit';
            return false;
        }

        $offer = (new OfferProvider($this->allegroApi))->getOfferProductDetails($this->object->id_auction);

        if ($offer->publication->status == PublicationStatus::ENDED) {
            $offerList = $this->allegroApi->sale()->offers()->getList(
                ['offer.id' => $this->object->id_auction]
            );

            if (isset($offerList->offers[0])) {
                $endDate = (new \DateTime($offerList->offers[0]->publication->endedAt))
                    ->setTimezone(new \DateTimeZone(date_default_timezone_get()))
                    ->format('Y-m-d H:i:s');
            }
        }

        $this->object->id_product = (int)Tools::getValue('id_product');
        $this->object->id_product_attribute = (int)Tools::getValue('id_product_attribute');
        $this->object->id_shop = (int)$this->context->shop->id;
        $this->object->id_shop_group = (int)$this->context->shop->id_shop_group;
        $this->object->selling_mode = strtoupper(Tools::getValue('offerType'));
        $this->object->start = (int)Tools::getValue('start');
        $this->object->closed = (int)Tools::getValue('closed');
        $this->object->end_date = (isset($endDate) ? $endDate : null);
        $this->object->add();

        foreach ($offer->publication->marketplaces->additional as $marketplace) {
            $marketplacePriceBuyNow = '0.00';
            if (is_object($offer->additionalMarketplaces->{$marketplace->id}->sellingMode)
                && is_object($offer->additionalMarketplaces->{$marketplace->id}->sellingMode->price)
            ) {
                $marketplacePriceBuyNow = $offer->additionalMarketplaces->{$marketplace->id}->sellingMode->price->amount;
            }

            $this->object->addAuctionMarketplace($marketplace->id, $marketplacePriceBuyNow);
        }

        $this->redirect_after = $this->context->link->getAdminLink('AdminXAllegroAuctionsList') . '&conf=102&offerType=' . Tools::getValue('offerType');

        return $this->object;
    }

    public function processBulkUnbind()
    {
        $selected_boxes = $this->boxes;
        if (empty($selected_boxes)) {
            $selected_boxes = Tools::getValue($this->table.'Box', []);
        }

        if (is_array($selected_boxes) && !empty($selected_boxes)) {
            $auctionIdsToDelete = [];

            foreach ($selected_boxes as $compositeId) {
                $parts = explode('|', $compositeId);
                if (isset($parts[0]) && is_numeric($parts[0])) {
                    $auctionIdsToDelete[] = (int)$parts[0];
                }
            }

            if (!empty($auctionIdsToDelete)) {
                XAllegroAuction::deleteAuctions($auctionIdsToDelete);
                Tools::redirectAdmin($this->context->link->getAdminLink('AdminXAllegroAuctionsList') . '&conf=101' . '&offerType=' . Tools::getValue('offerType'));
            } else {
                $this->errors[] = $this->l('Nie wybrano prawidłowych ofert lub wystąpił błąd podczas przetwarzania zaznaczenia.');
            }
        } else {
            $this->errors[] = $this->l('Nie wybrano żadnych ofert do usunięcia powiązania.');
        }
    }

    public function processBulkHide()
    {
        $selected_boxes = $this->boxes;
        if (empty($selected_boxes)) {
            $selected_boxes = Tools::getValue($this->table.'Box', []);
        }

        if (is_array($selected_boxes) && !empty($selected_boxes)) {
            $auctionIdsToHide = [];
            $accountId = (int)$this->allegroApi->getAccount()->id;

            foreach ($selected_boxes as $compositeId) {
                $parts = explode('|', $compositeId);
                if (isset($parts[0]) && is_numeric($parts[0]) && isset($parts[1]) && (int)$parts[1] === (int)$accountId) {
                    $auctionIdsToHide[] = (string)$parts[0];
                }
            }

            if (!empty($auctionIdsToHide)) {
                if ($this->hideAuctions($auctionIdsToHide, $accountId)) {
                    Tools::redirectAdmin($this->context->link->getAdminLink('AdminXAllegroAuctionsList') . '&conf=103&offerType=' . Tools::getValue('offerType'));
                } else {
                    $this->errors[] = $this->l('Wystąpił błąd podczas zapisywania ukrytych ofert.');
                }
            } else {
                $this->errors[] = $this->l('Nie wybrano prawidłowych ofert do ukrycia lub wystąpił błąd przetwarzania.');
            }
        } else {
            $this->errors[] = $this->l('Nie wybrano żadnych ofert do ukrycia.');
        }
    }

    public function displayXAuctionUrlLink($token = null, $id, $name = null)
    {
        $ids = explode('|', $id);
        $row = $this->findElementByKeyValue($this->_list, 'id_auction', $ids[0]);
        
        // START: DODAJ TEN WARUNEK ZABEZPIECZAJĄCY
        if (!$row || !isset($row['marketplaces']) || !is_array($row['marketplaces'])) {
            return null; // Jeśli nie znaleziono wiersza lub marketplace'ów, nie rób nic
        }
        // KONIEC: DODAJ TEN WARUNEK ZABEZPIECZAJĄCY

        $linkHTML = [];

        foreach ($row['marketplaces'] as $marketplaceId => $marketplace) {
            $tpl = $this->context->smarty->createTemplate($this->module->getLocalPath() . 'views/templates/admin/' . $this->tpl_folder . 'helpers/list/action_auction_url.tpl');
            $tpl->assign([
                'href' => (new MarketplacesProvider($marketplaceId))->getMarketplaceOfferUrl($row['id_auction'], $this->allegroApi->getAccount()->sandbox),
                'title' => $this->l('Zobacz na Allegro') . ' ' . $marketplace['name'],
                'action' => $marketplace['name']
            ]);

            $linkHTML[] = $tpl->fetch();
        }

        return implode('<br>', $linkHTML);
    }

    public function displayXAuctionBindLink($token = null, $id, $name = null)
    {
        $ids = explode('|', $id);
        $row = $this->findElementByKeyValue($this->auctions, 'id_auction', $ids[0]);

        if ($row) {
            return null;
        }

        $tpl = $this->context->smarty->createTemplate($this->module->getLocalPath() . 'views/templates/admin/' . $this->tpl_folder . 'helpers/list/action_auction_bind.tpl');
        $tpl->assign('href', $this->context->link->getAdminLink('AdminXAllegroAuctionsList') . '&edit' . $this->table . '&id_xallegro_account=' . $ids[1] . '&id_auction=' . $ids[0] . '&offerType=' . Tools::getValue('offerType'));

        $tpl->assign(array(
            'title' => $this->l('Powiąż ofertę z produktem'),
            'action' => $this->l('Powiąż'),
            'icon' => 'icon-link',
            'img' => 'themes/default/img/tree-multishop-url.png'
        ));

        return $tpl->fetch();
    }

    public function displayXAuctionEditBindLink($token = null, $id, $name = null)
    {
        $ids = explode('|', $id);
        $row = $this->findElementByKeyValue($this->auctions, 'id_auction', $ids[0]);

        if (!$row) {
            return null;
        }

        $tpl = $this->context->smarty->createTemplate($this->module->getLocalPath() . 'views/templates/admin/' . $this->tpl_folder . 'helpers/list/action_auction_bind.tpl');
        $tpl->assign('href', $this->context->link->getAdminLink('AdminXAllegroAuctionsList') . '&edit' . $this->table . '&id_xallegro_account=' . $ids[1] . '&id_auction=' . $ids[0] . '&offerType=' . Tools::getValue('offerType'));

        $tpl->assign(array(
            'title' => $this->l('Pogląd powiązania oferty z produktem'),
            'action' => $this->l('Zobacz powiązanie'),
            'icon' => 'icon-search',
            'img' => '../img/admin/subdomain.gif'
        ));

        return $tpl->fetch();
    }

    public function displayXAuctionUnbindLink($token = null, $id, $name = null)
    {
        $ids = explode('|', $id);
        $rowAuctions = $this->findElementByKeyValue($this->auctions, 'id_auction', $ids[0]);
        $rowList = $this->findElementByKeyValue($this->_list, 'id_auction', current($ids));

        if ($rowAuctions && $rowList['binded']) {
            $tpl = $this->context->smarty->createTemplate($this->module->getLocalPath() . 'views/templates/admin/' . $this->tpl_folder . 'helpers/list/action_auction_unbind.tpl');
            $tpl->assign(array(
                'href' => $this->context->link->getAdminLink('AdminXAllegroAuctionsList') . '&id_auction=' . $rowList['id_auction'] . '&delete_link' . '&offerType=' . Tools::getValue('offerType'),
                'title' => $this->l('Usuń powiązanie produktu'),
                'action' => $this->l('Usuń powiązanie'),
                'data_id' => $rowList['id_auction'],
                'data_title' => htmlspecialchars($rowList['name'])
            ));

            return $tpl->fetch();
        }

        return null;
    }

    public function displayXAuctionEditProductLink($token = null, $id, $name = null)
    {
        $ids = explode('|', $id);
        $rowAuctions = $this->findElementByKeyValue($this->auctions, 'id_auction', $ids[0]);
        $rowList = $this->findElementByKeyValue($this->_list, 'id_auction', current($ids));

        if ($rowAuctions && $rowList['binded']) {
            $href = version_compare(_PS_VERSION_, '1.7.0.0', '<')
                ? $this->context->link->getAdminLink('AdminProducts').'&updateproduct&id_product='.$rowList['id_product']
                : $this->context->link->getAdminLink('AdminProducts', true, ['id_product' => $rowList['id_product']]);

            $tpl = $this->context->smarty->createTemplate($this->module->getLocalPath() . 'views/templates/admin/' . $this->tpl_folder . 'helpers/list/action_auction_edit_product.tpl');
            $tpl->assign(array(
                'href' => $href,
                'title' => $this->l('Edytuj produkt'),
                'action' => $this->l('Edytuj produkt'),
            ));

            return $tpl->fetch();
        }

        return null;
    }

    public function displayXAuctionFinishLink($token = null, $id, $name = null)
    {
        $ids = explode('|', $id);
        $rowAuctions = $this->findElementByKeyValue($this->auctions, 'id_auction', $ids[0]);
        $rowList = $this->findElementByKeyValue($this->_list, 'id_auction', current($ids));

        // Pozwól zakończyć gdy oferta jest AKTYWNA.
        // Dla powiązanych utrzymaj dodatkowe blokady (start/closed/archived z DB).
        $canFinish = ($rowList['status'] == PublicationStatus::ACTIVE)
            && (
                !$rowAuctions // przypadek 1: oferta niepowiązana – OK, można kończyć
                || (!$rowAuctions['start'] && !$rowAuctions['closed'] && !$rowAuctions['archived']) // przypadek 2: oferta powiązana – sprawdzamy dotychczasowe warunki
            );

        if ($canFinish) {
            $tpl = $this->context->smarty->createTemplate(
                $this->module->getLocalPath() . 'views/templates/admin/' . $this->tpl_folder . 'helpers/list/action_auction_finish.tpl'
            );
            $tpl->assign(array(
                'href' => '#finish',
                'title' => $this->l('Zakończ ofertę'),
                'action' => $this->l('Zakończ'),
                'data_id' => $rowList['id_auction'],
                'data_title' => htmlspecialchars($rowList['name'], ENT_QUOTES, 'UTF-8')
            ));
            return $tpl->fetch();
        }

        return null;
    }

    public function displayXAuctionRedoLink($token = null, $id, $name = null)
    {
        $ids = explode('|', $id);
        $rowAuctions = $this->findElementByKeyValue($this->auctions, 'id_auction', $ids[0]);
        $rowList = $this->findElementByKeyValue($this->_list, 'id_auction', current($ids));

        if ($rowAuctions
            && ($rowList['status'] == PublicationStatus::ENDED || $rowAuctions['closed'])
            && !$rowAuctions['start']
            && !$rowAuctions['archived']
            && $this->allegroCookie->{$this->getAllegroCookieFilter('offerType')} === 'buy_now'
        ) {
            $tpl = $this->context->smarty->createTemplate($this->module->getLocalPath() . 'views/templates/admin/' . $this->tpl_folder . 'helpers/list/action_auction_redo.tpl');
            $tpl->assign(array(
                'href' => '#redo',
                'title' => $this->l('Wznów ponownie wybraną ofertę'),
                'action' => $this->l('Wznów'),
                'data_id' => $rowList['id_auction'],
                'data_title' =>  htmlspecialchars($rowList['name'])
            ));

            return $tpl->fetch();
        }

        return null;
    }

    public function displayXAuctionUpdateLink($token = null, $id, $name = null)
    {
        $ids = explode('|', $id);
        $rowList = $this->findElementByKeyValue($this->_list, 'id_auction', current($ids));

        $tpl = $this->context->smarty->createTemplate($this->module->getLocalPath() . 'views/templates/admin/' . $this->tpl_folder . 'helpers/list/action_auction_update.tpl');
        $tpl->assign(array(
            'href' => '#update',
            'title' => $this->l('Aktualizuj ofertę'),
            'action' => $this->l('Aktualizuj'),
            'data_id' => $rowList['id_auction'],
            'data_title' =>  htmlspecialchars($rowList['name'])
        ));

        return $tpl->fetch();
    }
    
    /**
     * Zwraca sformatowany HTML dla kolumny zgodności SKU.
     * Używane jako callback w HelperList.
     *
     * @param int $value Wartość statusu zgodności (1: Tak, 0: Nie)
     * @param array $row Pełne dane wiersza
     * @return string
     */
    public function printMatchOk($value, $row)
    {
        if ($value) {
            return '<span class="badge" style="background-color:#5cb85c;">' . $this->l('Tak') . '</span>';
        } else {
            return '<span class="badge" style="background-color:#d9534f;">' . $this->l('Nie') . '</span>';
        }
    }

    /**
     * Zwraca sformatowany HTML dla kolumny zgodności ilości.
     * Używane jako callback w HelperList.
     *
     * @param string|null $value Wartość statusu zgodności ('eq', 'lt', 'gt')
     * @param array $row Pełne dane wiersza
     * @return string
     */
    public function printQuantityMatch($value, $row)
    {
        if (!isset($row['binded']) || !$row['binded'] || $value === null) {
            return '—'; // Nie dotyczy ofert niepowiązanych
        }

        switch ($value) {
            case 'eq': // Zgodne
                return '<span class="badge" style="background-color:#5cb85c;">' . $this->l('Tak') . '</span>';
            case 'gt': // Allegro ma więcej
                return '<span class="badge" style="background-color:#d9534f;">' . $this->l('Allegro > Sklep') . '</span>';
            case 'lt': // W sklepie jest więcej
                return '<span class="badge" style="background-color:#f0ad4e;">' . $this->l('Sklep > Allegro') . '</span>';
            default:
                return '—';
        }
    }

    /**
     * Zwraca sformatowany HTML dla kolumny zgodności EAN.
     * Używane jako callback w HelperList.
     *
     * @param bool|null $value Wartość statusu zgodności (true: Tak, false: Nie, null: nie dotyczy)
     * @param array $row Pełne dane wiersza
     * @return string
     */
    public function printEanMatch($value, $row)
    {
        if (!isset($row['binded']) || !$row['binded'] || $value === null) {
            return '—';
        }

        if ($value) {
            return '<span class="badge" style="background-color:#5cb85c;">' . $this->l('Tak') . '</span>';
        } else {
            return '<span class="badge" style="background-color:#d9534f;">' . $this->l('Nie') . '</span>';
        }
    }

    /**
     * Renderuje informację o VAT (TAK/NIE/—).
     * @param mixed $value
     * @param array $row
     * @return string
     */
    public function printVatDefined($value, $row)
    {
        // — (brak danych)
        if ($value === null || $value === '') {
            return '<span class="badge">—</span>';
        }
        // TAK z liczbą stawki (jeśli dostępna)
        if ((string)$value === '1') {
            $rate = isset($row['vat_rate']) && $row['vat_rate'] !== '' 
                ? (is_numeric($row['vat_rate']) ? number_format((float)$row['vat_rate'], 2).'%' : htmlspecialchars((string)$row['vat_rate']))
                : $this->l('Tak');
            return '<span class="badge" style="background-color:#5cb85c;">'.$rate.'</span>';
        }
        // NIE
        return '<span class="badge" style="background-color:#d9534f;">'.$this->l('Nie').'</span>';
    }

    public function printMargin($marginData, $row)
    {
        // Sprawdzenie, czy dane są dostępne i czy oferta jest powiązana
        if (!is_array($marginData) || $marginData['value'] === null || !isset($row['binded']) || !$row['binded']) {
            return '<div style="text-align:right; margin:-4px -8px; padding: 4px 8px;">—</div>';
        }

        $value = $marginData['value'];
        $percentage = $marginData['percentage'];

        // Ustalenie koloru tła na podstawie progu procentowego
        $bgColor = '';
        if ($percentage < 20) {
            $bgColor = '#f2dede'; // Czerwony
        } elseif ($percentage <= 35) {
            $bgColor = '#fcf8e3'; // Żółty
        } else {
            $bgColor = '#dff0d8'; // Zielony
        }

        // Pobranie waluty do poprawnego wyświetlania ceny
        $currency_iso = $row['marketplaces'][$row['base_marketplace']]['currencyIso'];
        $currency = Currency::getIdByIsoCode($currency_iso);

        // Sformatowanie stringu wyjściowego
        $formattedValue = Tools::displayPrice($value, (int)$currency);
        $formattedPercentage = number_format($percentage, 1, ',', '') . '%';
        $content = sprintf('%s (%s)', $formattedValue, $formattedPercentage);

        // Zwrócenie finalnego HTML dla komórki
        return sprintf(
            '<div style="background-color: %s; text-align:right; margin:-4px -8px; padding: 4px 8px;">%s</div>',
            $bgColor,
            $content
        );
    }
    // KONIEC: ZMIANA - NARZUT

    /**
     * Callback do renderowania komórki dla nowej kolumny "EAN (Allegro)".
     *
     * @param string $value Wartość EAN z bazy danych
     * @param array $row Pełne dane wiersza
     * @return string
     */
    public function printAllegroEan($value, $row)
    {
        $auctionId = isset($row['id_auction']) ? (string)$row['id_auction'] : '';
        $display   = $value ? htmlspecialchars((string)$value) : '—';
        return '<span id="allegro-ean-'.pSQL($auctionId).'">'.$display.'</span>';
    }


    /************************************************************************************************
     * ZASTĄP CAŁĄ METODĘ getAuctionList
     * Zmiany: Dodano 'allegro_ean' i 'ean13' do bloku sortowania usort().
     ************************************************************************************************/
    private function getAuctionList()
    {
        $offerFilters = array();
        $offerStatus = ($this->allegroCookie->{$this->getAllegroCookieFilter('offerStatus')} === 'all'
            ? 'inactive,active,activating,ended'
            : $this->allegroCookie->{$this->getAllegroCookieFilter('offerStatus')});

        if ($this->allegroCookie->{$this->getAllegroCookieFilter('offerMarketplace')} !== 'all') {
            $offerFilters['publication.marketplace'] = $this->allegroCookie->{$this->getAllegroCookieFilter('offerMarketplace')};
        }

        if (isset($this->context->cookie->xallegroauctionslistxallegro_auctionFilter_id_auction) && !empty($this->context->cookie->xallegroauctionslistxallegro_auctionFilter_id_auction)) {
            $offerFilters['offer.id'] = trim($this->context->cookie->xallegroauctionslistxallegro_auctionFilter_id_auction);
        }

        if (isset($this->context->cookie->xallegroauctionslistxallegro_auctionFilter_name) && !empty($this->context->cookie->xallegroauctionslistxallegro_auctionFilter_name)) {
            $offerFilters['name'] = urlencode($this->context->cookie->xallegroauctionslistxallegro_auctionFilter_name);
        }

        if (isset($this->context->cookie->xallegroauctionslistxallegro_auctionFilter_external) && !empty($this->context->cookie->xallegroauctionslistxallegro_auctionFilter_external)) {
            $offerFilters['external.id'] = urlencode($this->context->cookie->xallegroauctionslistxallegro_auctionFilter_external);
        }

        $filterReference = !empty($this->context->cookie->xallegroauctionslistxallegro_auctionFilter_reference) ? $this->context->cookie->xallegroauctionslistxallegro_auctionFilter_reference : false;
        $filterEan13 = !empty($this->context->cookie->xallegroauctionslistxallegro_auctionFilter_ean13) ? $this->context->cookie->xallegroauctionslistxallegro_auctionFilter_ean13 : false;
        $filterAllegroEan = !empty($this->context->cookie->xallegroauctionslistxallegro_auctionFilter_allegro_ean) ? $this->context->cookie->xallegroauctionslistxallegro_auctionFilter_allegro_ean : false;


        if (Tools::getIsset('xallegro_auction_pagination') || !isset($this->context->cookie->xallegro_auction_pagination) || !$this->context->cookie->xallegro_auction_pagination) {
            $this->context->cookie->xallegro_auction_pagination = (int)Tools::getValue('xallegro_auction_pagination', $this->_default_pagination);
        }

        $maxInputVars = (int)ini_get('max_input_vars');
        if ($maxInputVars <= ($this->context->cookie->xallegro_auction_pagination * 2) -10) {
            $this->warnings[] = $this->module->renderAdminMessage(sprintf($this->l('Uwaga! Twoja maksymalna liczba pól w formularzu (max_input_vars) %s może uniemożliwić poprawną obsługę listy ofert.'), '<b>' . $maxInputVars . '</b>'));
        }
        
        if (Tools::getIsset('submitFilterxallegro_auction') || !isset($this->context->cookie->submitFilterxallegro_auction) || !$this->context->cookie->submitFilterxallegro_auction) {
            $this->context->cookie->submitFilterxallegro_auction = max((int)Tools::getValue('submitFilterxallegro_auction', 1), 1);
        }

        if (Tools::getValue('filterByProductization')) {
            $this->context->cookie->submitFilterxallegro_productizationNeeded = 1;
        }

        if (Tools::getValue('resetFilterByProductization')) {
            $this->context->cookie->submitFilterxallegro_productizationNeeded = 0;
        }

        if (isset($this->context->cookie->submitFilterxallegro_productizationNeeded) && $this->context->cookie->submitFilterxallegro_productizationNeeded) {
            $offerFilters['productizationRequired'] = 'true';
            $offerFilters['product.id.empty'] = 'true';
        }

        $filterMatchKey = $this->getCookieFilterPrefix() . $this->table . 'Filter_match_ok';
        $filterMatchRaw = isset($this->context->cookie->{$filterMatchKey}) ? (string)$this->context->cookie->{$filterMatchKey} : '';
        $isMatchFilterActive = in_array($filterMatchRaw, ['0', '1'], true);

        $filterQuantityMatchKey = $this->getCookieFilterPrefix().$this->table.'Filter_quantity_match';
        $isQuantityMatchFilterActive = isset($this->context->cookie->{$filterQuantityMatchKey}) && $this->context->cookie->{$filterQuantityMatchKey} !== '';

        $filterEanMatchKey = $this->getCookieFilterPrefix() . $this->table . 'Filter_ean_match';
        $filterEanMatchRaw = isset($this->context->cookie->{$filterEanMatchKey}) ? (string)$this->context->cookie->{$filterEanMatchKey} : '';
        $isEanMatchFilterActive = in_array($filterEanMatchRaw, ['0', '1'], true);
        
        
        
        // Filtr VAT (TAK/NIE/—)
        $filterVatKey = $this->getCookieFilterPrefix() . $this->table . 'Filter_vat_defined';
        $hasVatCookie = isset($this->context->cookie->{$filterVatKey});
        $filterVatRaw = $hasVatCookie ? (string)$this->context->cookie->{$filterVatKey} : null;
        // aktywny tylko jeśli cookie istnieje i użytkownik faktycznie wybrał TAK/NIE/—
        $isVatFilterActive = $hasVatCookie && ($filterVatRaw === '' || in_array($filterVatRaw, ['0', '1'], true));
// NOWA ZMIENNA DO SPRAWDZANIA FILTRA CENY
        $filterPriceCheckKey = $this->getCookieFilterPrefix().$this->table.'Filter_price_check';
        $isPriceCheckFilterActive = isset($this->context->cookie->{$filterPriceCheckKey}) && $this->context->cookie->{$filterPriceCheckKey} !== '';
        $filterMarginColorKey = $this->getCookieFilterPrefix().$this->table.'Filter_margin_color';
        $isMarginColorFilterActive = isset($this->context->cookie->{$filterMarginColorKey}) && $this->context->cookie->{$filterMarginColorKey} !== '';


        $hiddenAuctionIds = [];
        if (stripos(strtoupper($offerStatus), 'ENDED') !== false) {
            $hiddenAuctionIds = $this->getHiddenAuctionIds($this->allegroApi->getAccount()->id);
        }
        $isHiddenFilterActive = !empty($hiddenAuctionIds);
        
        $apiFilters = array_merge($offerFilters, [
            'publication.status' => strtoupper($offerStatus),
            'sellingMode.format' => strtoupper($this->allegroCookie->{$this->getAllegroCookieFilter('offerType')})
        ]);

        $this->_list = [];

        try {
            $offersToProcess = [];
            $totalCountFromApi = 0;

            // ZMIANA: Dodano $isPriceCheckFilterActive do warunku
            if ($isMatchFilterActive || $isQuantityMatchFilterActive || $filterAllegroEan || $isEanMatchFilterActive || $isPriceCheckFilterActive || $isMarginColorFilterActive || $isVatFilterActive) {
                $this->warnings[] = $this->l('Jeden z filtrów ("EAN (Allegro)", "Zgodność", "Zgodność ilości", "Zgodność EAN", "Kontrola Ceny/kg") jest aktywny. Trwa pobieranie wszystkich ofert, co może zająć dłuższą chwilę...');
                $limit = 100;
                $offset = 0;
                do {
                    $result = $this->allegroApi->sale()->offers()->getList($apiFilters, $limit, $offset);
                    if (!empty($result->offers)) {
                        $offersToProcess = array_merge($offersToProcess, $result->offers);
                    }
                    $offset += $limit;
                } while (!empty($result->offers) && count($result->offers) === $limit);
            } else {
                switch ($this->context->cookie->xallegroauctionslistxallegro_auctionOrderby) {
                    case 'quantity': $sort = 'stock.available'; break;
                    case 'sold': $sort = 'stock.sold'; break;
                    case 'price': $sort = 'sellingMode.price.amount'; break;
                    default: $sort = false;
                }
                if ($sort) {
                    if ($this->context->cookie->xallegroauctionslistxallegro_auctionOrderway == 'desc') {
                        $sort = '-' . $sort;
                    }
                    $apiFilters['sort'] = $sort;
                }
                
                $paginationLimit = (int)$this->context->cookie->xallegro_auction_pagination;
                $page = max((int)$this->context->cookie->submitFilterxallegro_auction, 1);
                $paginationOffset = ($page - 1) * $paginationLimit;
                
                $result = $this->allegroApi->sale()->offers()->getList($apiFilters, $paginationLimit, $paginationOffset);
                $offersToProcess = $result->offers;
                $totalCountFromApi = (int)$result->totalCount;
            }

            if ($isHiddenFilterActive && !empty($offersToProcess)) {
                $visibleOffers = [];
                foreach ($offersToProcess as $offer) {
                    if (!in_array((string)$offer->id, $hiddenAuctionIds)) {
                        $visibleOffers[] = $offer;
                    }
                }
                
                if (!($isMatchFilterActive || $isQuantityMatchFilterActive || $filterAllegroEan) && count($offersToProcess) !== count($visibleOffers)) {
                     $this->warnings[] = $this->l('Uwaga: Niektóre zakończone oferty są ukryte. Lista może zawierać mniej pozycji na stronie, a paginacja może być nieprecyzyjna.');
                }

                $offersToProcess = $visibleOffers;
            }


            $processedData = [];
            $offersIds = array_map(function ($object) { return $object->id; }, $offersToProcess);

            $allegroEansMap = [];
            if (!empty($offersIds)) {
                $idsIn = implode(',', array_map(function ($id) {
                    return '"' . pSQL((string)$id) . '"';
                }, $offersIds));

                $rows = Db::getInstance()->executeS('
                    SELECT id_auction, ean_allegro
                    FROM `'._DB_PREFIX_.'xallegro_auction`
                    WHERE id_auction IN ('.$idsIn.')
                ');
                foreach ($rows as $r) {
                    $allegroEansMap[(string)$r['id_auction']] = (string)$r['ean_allegro'];
                }
            }

            // VAT maps (optional if columns exist)
            $vatDefinedMap = [];
            $vatRateMap = [];
            if (!empty($offersIds)) {
                try {
                    $hasVatCol = false;
                    $check = Db::getInstance()->executeS('SHOW COLUMNS FROM `'. _DB_PREFIX_ .'xallegro_auction` LIKE "vat_defined"');
                    if (is_array($check) && count($check) > 0) {
                        $hasVatCol = true;
                    }
                } catch (Exception $e) {
                    $hasVatCol = false;
                }
                if ($hasVatCol) {
                    $rowsVat = Db::getInstance()->executeS('
                        SELECT id_auction, vat_defined, vat_rate
                        FROM `'._DB_PREFIX_.'xallegro_auction`
                        WHERE id_auction IN ('.$idsIn.')
                    ');
                    if (is_array($rowsVat)) {
                        foreach ($rowsVat as $rv) {
                            $id = (string)$rv['id_auction'];
                            $vatDefinedMap[$id] = ($rv['vat_defined'] === null || $rv['vat_defined'] === '') ? null : (string)(int)$rv['vat_defined'];
                            $vatRateMap[$id] = $rv['vat_rate'];
                        }
                    }
                }
            }



            if (!empty($offersIds)) {
                // ################### POCZĄTEK POPRAWKI ###################

                // KROK 1: NAJPIERW POBIERAMY DANE O POWIĄZANIACH
                $this->auctions = XAllegroAuction::getAuctionAssociationsForList($offersIds);

                // KROK 2: DOPIERO TERAZ URUCHAMIAMY LOGIKĘ WYSZUKIWANIA ODPOWIEDNIKÓW
                // ################### LOGIKA WYSZUKIWANIA ODPOWIEDNIKÓW ###################
                $id_lang = (int)$this->allegroApi->getAccount()->id_language;
                $smallCounterpartsMap = [];
                $largeProductsForCheck = [];

                // Etap 1: Przejrzyj powiązane aukcje na liście i zidentyfikuj "duże" produkty do sprawdzenia
                foreach ($this->auctions as $auction) {
                    $product = new Product($auction['id_product'], false, $id_lang);
                    if (Validate::isLoadedObject($product) && $product->weight > self::MIN_WEIGHT_FOR_CHECK) {
                        // NOWA LOGIKA: Czyścimy nazwę z fragmentów w nawiasach i wag, aby uzyskać czystą bazę
                        $baseName = $this->_getCleanBaseProductName($product->name);

                        if (!empty($baseName)) {
                            // Zapisujemy produkt i jego bazową nazwę do późniejszego sprawdzenia
                            $largeProductsForCheck[$auction['id_product']] = $baseName;
                        }
                    }
                }
                
                // Etap 2: Jeśli znaleziono duże produkty, wykonaj JEDNO zapytanie SQL, by znaleźć ich małe odpowiedniki
                if (!empty($largeProductsForCheck)) {
                    $whereClauses = [];
                    foreach (array_unique($largeProductsForCheck) as $baseName) {
                        $whereClauses[] = "(pl.name LIKE '" . pSQL($baseName) . "%')";
                    }
                    
                    // POPRAWIONE ZAPYTANIE - usunięto ps.price
                    $sql = 'SELECT p.id_product, pl.name, p.weight
                            FROM `' . _DB_PREFIX_ . 'product` p 
                            ' . Shop::addSqlAssociation('product', 'p') . '
                            LEFT JOIN `' . _DB_PREFIX_ . 'product_lang` pl ON (p.id_product = pl.id_product AND pl.id_lang = ' . $id_lang . Shop::addSqlRestrictionOnLang('pl') . ')
                            WHERE (' . implode(' OR ', $whereClauses) . ') AND p.weight BETWEEN ' . self::REF_PRODUCT_MIN_WEIGHT . ' AND ' . self::REF_PRODUCT_MAX_WEIGHT;

                    $smallProductsFound = Db::getInstance()->executeS($sql);

                    // Etap 3: Zbuduj mapę małych produktów (klucz = nazwa bazowa) dla szybkiego dostępu
                    if ($smallProductsFound) {
                        foreach ($smallProductsFound as $smallProduct) {
                            // NOWA LOGIKA: Używamy tej samej metody czyszczenia nazwy co powyżej
                            $baseName = $this->_getCleanBaseProductName($smallProduct['name']);
                            
                            if (!isset($smallCounterpartsMap[$baseName])) {
                                $smallCounterpartsMap[$baseName] = $smallProduct;
                            }
                        }
                    }
                }
                // ################### KONIEC LOGIKI WYSZUKIWANIA ###################
                
                // ################### KONIEC POPRAWKI ###################

                foreach ($offersToProcess as $offer) {
                    $priceBuyNow = $priceStarting = $priceMinimal = $priceCurrent = 0;
                    $start = $end = false;
                    $marketplaces = [];
                    if ($offer->sellingMode) {
                        if (is_object($offer->sellingMode->price)) { $priceBuyNow = (float)$offer->sellingMode->price->amount; }
                        if (is_object($offer->sellingMode->startingPrice)) { $priceStarting = (float)$offer->sellingMode->startingPrice->amount; }
                        if (is_object($offer->sellingMode->minimalPrice)) { $priceMinimal = (float)$offer->sellingMode->minimalPrice->amount; }
                    }
                    if (is_object($offer->saleInfo->currentPrice)) { $priceCurrent = (float)$offer->saleInfo->currentPrice->amount; }
                    if ($offer->publication->startedAt) { $start = $offer->publication->startedAt; }
                    else if ($offer->publication->startingAt) { $start = $offer->publication->startingAt; }
                    if ($offer->publication->endedAt) { $end = $offer->publication->endedAt; }
                    else if ($offer->publication->endingAt) { $end = $offer->publication->endingAt; }
                    
                    $productWeight = null; // Inicjalizacja wagi
                    $binded = $this->findElementByKeyValue($this->auctions, 'id_auction', $offer->id);
                    $bindedDetails = [];
                    if ($binded) {
                        $bindedDetails = [
                            'current_context' => (!Shop::isFeatureActive() || Shop::getContext() === Shop::CONTEXT_SHOP ? (int)$this->context->shop->id : null),
                            'id_shop' => (int)$binded['id_shop'], 'shop_name' => $binded['shop_name']
                        ];
                    }

                    $marginData = [
                        'value' => null,
                        'percentage' => null
                    ];

                    if ($binded) {
                        $wholesale_price = 0;
                        $id_shop = (int)$binded['id_shop'];

                        if (!empty($binded['id_product_attribute'])) {
                            $sql = new DbQuery();
                            $sql->select('pas.wholesale_price');
                            $sql->from('product_attribute_shop', 'pas');
                            $sql->where('pas.id_product_attribute = ' . (int)$binded['id_product_attribute']);
                            $sql->where('pas.id_shop = ' . $id_shop);
                            $wholesale_price = (float)Db::getInstance()->getValue($sql);
                        }
                        
                        if ($wholesale_price == 0 && !empty($binded['id_product'])) {
                            $sql = new DbQuery();
                            $sql->select('ps.wholesale_price');
                            $sql->from('product_shop', 'ps');
                            $sql->where('ps.id_product = ' . (int)$binded['id_product']);
                            $sql->where('ps.id_shop = ' . $id_shop);
                            $wholesale_price = (float)Db::getInstance()->getValue($sql);
                        }
                        
                        // Pobieranie wagi produktu
                        $productForWeight = new Product((int)$binded['id_product']);
                        $productWeight = $productForWeight->weight;
                        
                        if ($wholesale_price > 0) {
                            $product_for_tax = new Product((int)$binded['id_product'], false, null, $id_shop);
                            $tax_rate = $product_for_tax->getTaxesRate();
                            $wholesale_price_gross = $wholesale_price * (1 + ($tax_rate / 100));

                            $margin_value = $priceBuyNow - $wholesale_price_gross;
                            $margin_percentage = ($wholesale_price_gross > 0) ? ($margin_value / $wholesale_price_gross) * 100 : 0;

                            $marginData['value'] = $margin_value;
                            $marginData['percentage'] = $margin_percentage;
                        }
                    }

                    $marketplaceProvider = new MarketplacesProvider($offer->publication->marketplaces->base->id);
                    $marketplaceCurrency = $marketplaceProvider->getMarketplaceCurrency();
                    $marketplaces[$offer->publication->marketplaces->base->id] = [
                        'name' => $marketplaceProvider->getMarketplaceName(), 'currencySign' => $marketplaceCurrency->sign, 'currencyIso' => $marketplaceCurrency->iso_code,
                        'priceBuyNow' => XAllegroProduct::formatPrice($priceBuyNow, $marketplaceCurrency), 'sold' => (int)$offer->stock->sold, 'visits' => (int)$offer->stats->visitsCount,
                        'status' => $offer->publication->status, 'statusTranslated' => $this->formatOfferStatus($offer->publication->status), 'statusDetails' => []
                    ];
                    foreach (Marketplace::toArray() as $marketplace) {
                        if ($marketplace !== $offer->publication->marketplaces->base->id) { $marketplaces[$marketplace] = null; }
                    }
                    foreach ($offer->publication->marketplaces->additional as $marketplace) {
                        if (!Marketplace::isValid($marketplace->id)) { continue; }
                        $marketplaceProvider = new MarketplacesProvider($marketplace->id);
                        $marketplaceCurrency = $marketplaceProvider->getMarketplaceCurrency();
                        $offerMarketplace = $offer->additionalMarketplaces->{$marketplace->id};
                        $offerMarketplacePrice = null;
                        if (is_object($offerMarketplace->sellingMode) && is_object($offerMarketplace->sellingMode->price)) {
                            $offerMarketplacePrice = XAllegroProduct::formatPrice($offerMarketplace->sellingMode->price->amount, $marketplaceCurrency);
                        }
                        $statusDetails = [];
                        if ($binded && isset($binded['marketplace'][$marketplace->id]) && $binded['marketplace'][$marketplace->id]['last_status']) {
                            $marketplaceStatus = $binded['marketplace'][$marketplace->id];
                            $statusDetails = [
                                'status' => MarketplacePublicationStatus::from($marketplaceStatus['last_status'])->getValueTranslated(),
                                'statusDate' => ($marketplaceStatus['last_status_date'] ? (new DateTime($marketplaceStatus['last_status_date']))->format('d.m.Y H:i') : null),
                                'statusRefusalReasons' => $marketplaceStatus['last_status_refusal_reasons']
                            ];
                        }
                        $marketplaces[$marketplace->id] = [
                            'name' => $marketplaceProvider->getMarketplaceName(), 'currencySign' => $marketplaceCurrency->sign, 'currencyIso' => $marketplaceCurrency->iso_code,
                            'priceBuyNow' => $offerMarketplacePrice, 'sold' => (int)$offerMarketplace->stock->sold, 'visits' => (int)$offerMarketplace->stats->visitsCount,
                            'status' => $offerMarketplace->publication->state, 'statusTranslated' => MarketplacePublicationStatus::from($offerMarketplace->publication->state)->getValueTranslated(), 'statusDetails' => $statusDetails
                        ];
                    }
                    $marketplaces = array_filter($marketplaces);
                    
                    // LOGIKA KONTROLI CENY - musi być PRZED definicją tablicy $row
                    $priceCheckStatus = null; // Domyślna wartość (nie dotyczy)

                    // Sprawdzamy tylko te produkty, które wcześniej zidentyfikowaliśmy jako "duże"
                    if ($binded && isset($largeProductsForCheck[$binded['id_product']])) {
                        $baseName = $largeProductsForCheck[$binded['id_product']];

                        // Sprawdzamy, czy znaleźliśmy mały odpowiednik w naszej mapie
                        if (isset($smallCounterpartsMap[$baseName])) {
                            $smallProduct = $smallCounterpartsMap[$baseName];
                            $smallProductPrice = Product::getPriceStatic($smallProduct['id_product']);
                            
                            // Upewniamy się, że mamy wszystkie dane i unikamy dzielenia przez zero
                            if ($productWeight > 0 && $smallProduct['weight'] > 0 && $smallProductPrice > 0) {
                                $smallPricePerKg = $smallProductPrice / $smallProduct['weight'];

                                // BŁĄD, jeśli cena dużego produktu jest niższa niż (cena/kg małego * mnożnik)
                                if ($priceBuyNow < ($smallPricePerKg * self::PRICE_CHECK_MULTIPLIER)) {
                                    $priceCheckStatus = 'error';
                                } else {
                                    $priceCheckStatus = 'ok';
                                }
                            }
                        } else {
                            // Nie znaleziono produktu referencyjnego, stosujemy regułę awaryjną
                            if ($priceBuyNow > self::PRICE_FALLBACK_THRESHOLD) {
                                $priceCheckStatus = 'ok'; // Cena jest wysoka, więc zakładamy, że jest OK
                            } else {
                                $priceCheckStatus = 'no_ref'; // Cena niska i brak ref., wymaga sprawdzenia
                            }
                        }
                    }

                    $row = array(
                        'id_xallegro_auction' => (float)$offer->id . '|' .  (int)$this->allegroApi->getAccount()->id,
                        'image' => ($offer->primaryImage->url ? str_replace('original', 's64', $offer->primaryImage->url) : null),
                        'image_large' => ($offer->primaryImage->url ? str_replace('original', 's192', $offer->primaryImage->url) : null),
                        'id_auction' => (float)$offer->id,
                        'name' => $offer->name,
                        'external' => (is_object($offer->external) ? $offer->external->id : ''),
                        'quantity' => (int)$offer->stock->available,
                        'price' => $priceBuyNow,
                        'price_pl' => $marketplaces[XAllegroApi::MARKETPLACE_PL]['priceBuyNow'] . ' ' . $marketplaces[XAllegroApi::MARKETPLACE_PL]['currencySign'],
                        'price_check' => $priceCheckStatus,
                        'margin' => $marginData,
                        'margin_percentage' => (isset($marginData['percentage']) ? (float)$marginData['percentage'] : null),
                        'margin_color' => (isset($marginData['percentage'])
                            ? (($marginData['percentage'] < 20) ? 'red' : (($marginData['percentage'] <= 35) ? 'yellow' : 'green'))
                            : null),
                        'price_cz' => (isset($marketplaces[XAllegroApi::MARKETPLACE_CZ]) ? $marketplaces[XAllegroApi::MARKETPLACE_CZ]['priceBuyNow'] . ' ' . $marketplaces[XAllegroApi::MARKETPLACE_CZ]['currencySign'] : null),
                        'price_sk' => (isset($marketplaces[XAllegroApi::MARKETPLACE_SK]) ? $marketplaces[XAllegroApi::MARKETPLACE_SK]['priceBuyNow'] . ' ' . $marketplaces[XAllegroApi::MARKETPLACE_SK]['currencySign'] : null),
                        'price_hu' => (isset($marketplaces[XAllegroApi::MARKETPLACE_HU]) ? $marketplaces[XAllegroApi::MARKETPLACE_HU]['priceBuyNow'] . ' ' . $marketplaces[XAllegroApi::MARKETPLACE_HU]['currencySign'] : null),
                        'price_starting' => $priceStarting,
                        'price_minimal' => $priceMinimal,
                        'price_current' => $priceCurrent,
                        'offers' => (int)$offer->saleInfo->biddersCount,
                        'sold_pl' => $marketplaces[XAllegroApi::MARKETPLACE_PL]['sold'],
                        'sold_cz' => (isset($marketplaces[XAllegroApi::MARKETPLACE_CZ]) ? (int)$marketplaces[XAllegroApi::MARKETPLACE_CZ]['sold'] : null),
                        'sold_sk' => (isset($marketplaces[XAllegroApi::MARKETPLACE_SK]) ? (int)$marketplaces[XAllegroApi::MARKETPLACE_SK]['sold'] : null),
                        'sold_hu' => (isset($marketplaces[XAllegroApi::MARKETPLACE_HU]) ? (int)$marketplaces[XAllegroApi::MARKETPLACE_HU]['sold'] : null),
                        'visits_pl' => (int)$marketplaces[XAllegroApi::MARKETPLACE_PL]['visits'],
                        'visits_cz' => (isset($marketplaces[XAllegroApi::MARKETPLACE_CZ]) ? (int)$marketplaces[XAllegroApi::MARKETPLACE_CZ]['visits'] : null),
                        'visits_sk' => (isset($marketplaces[XAllegroApi::MARKETPLACE_SK]) ? (int)$marketplaces[XAllegroApi::MARKETPLACE_SK]['visits'] : null),
                        'visits_hu' => (isset($marketplaces[XAllegroApi::MARKETPLACE_HU]) ? (int)$marketplaces[XAllegroApi::MARKETPLACE_HU]['visits'] : null),
                        'start' => ($start ? (new DateTime($start))->setTimezone(new DateTimeZone(date_default_timezone_get()))->format('d.m.Y H:i') : null),
                        'end' => ($end ? (new DateTime($end))->setTimezone(new DateTimeZone(date_default_timezone_get()))->format('d.m.Y H:i') : null),
                        'format' => ($offer->sellingMode && $offer->sellingMode->format ? $offer->sellingMode->format : ''),
                        'status' => $offer->publication->status,
                        'status_pl' => $marketplaces[XAllegroApi::MARKETPLACE_PL]['statusTranslated'],
                        'status_cz' => (isset($marketplaces[XAllegroApi::MARKETPLACE_CZ]) ? $marketplaces[XAllegroApi::MARKETPLACE_CZ]['statusTranslated'] : null),
                        'status_sk' => (isset($marketplaces[XAllegroApi::MARKETPLACE_SK]) ? $marketplaces[XAllegroApi::MARKETPLACE_SK]['statusTranslated'] : null),
                        'status_hu' => (isset($marketplaces[XAllegroApi::MARKETPLACE_HU]) ? $marketplaces[XAllegroApi::MARKETPLACE_HU]['statusTranslated'] : null),
                        'base_marketplace' => $offer->publication->marketplaces->base->id,
                        'marketplaces' => $marketplaces,
                        'binded' => (int)$binded,
                        'binded_details' => $binded ? $bindedDetails : false,
                        'archived' => $binded && isset($binded['archived']) ? (int)$binded['archived'] : 0,
                        'id_product' => (int)$binded && isset($binded['id_product']) ? (int)$binded['id_product'] : false,
                        'id_product_attribute' => (int)$binded && isset($binded['id_product_attribute']) ? (int)$binded['id_product_attribute'] : false,
                        'weight' => $productWeight,
                        'reference' => ($binded ? $binded['reference'] : false),
                        'allegro_ean' => isset($allegroEansMap[(string)$offer->id]) ? $allegroEansMap[(string)$offer->id] : '',
                        'ean13' => ($binded ? $binded['ean13'] : false),
                        'vat_defined' => (isset($vatDefinedMap[(string)$offer->id]) ? $vatDefinedMap[(string)$offer->id] : null),
                        
                        'vat_rate' => (isset($vatRateMap[(string)$offer->id]) ? $vatRateMap[(string)$offer->id] : null),'quantity_shop' => ($binded ? (int)$binded['quantity_shop'] : false),
                        'quantity_match' => null,
                        'ean_match' => null,
                        'auto_renew' => ($binded ? $binded['auto_renew'] : null),
                        'id_shop' => ($binded ? (int)$binded['id_shop'] : null),
                        'shop_name' => ($binded ? $binded['shop_name'] : null)
                    );
                    
                    $ext = isset($row['external'])  ? (string)$row['external']  : '';
                    $ref = isset($row['reference']) ? (string)$row['reference'] : '';
                    $row['match_ok'] = ($ext !== '' && $ref !== '' && $ext === $ref) ? 1 : 0;
                    
                    if ($binded && $row['quantity_shop'] !== false) {
                        $q_allegro = (int)$row['quantity'];
                        $q_shop = (int)$row['quantity_shop'];
                        if ($q_allegro > $q_shop) {
                            $row['quantity_match'] = 'gt';
                        } elseif ($q_allegro < $q_shop) {
                            $row['quantity_match'] = 'lt';
                        } else {
                            $row['quantity_match'] = 'eq';
                        }
                    }

                    $row['ean_match'] = null;
                    if ($binded) {
                        $allegroEan = trim((string)$row['allegro_ean']);
                        $productEan = trim((string)$row['ean13']);

                        if ($allegroEan !== '' || $productEan !== '') {
                            $row['ean_match'] = ($allegroEan !== '' && $allegroEan === $productEan);
                        }
                    }
// ================= POCZĄTEK KODU DO WKLEJENIA =================

            // ZMIANA: Poprawne filtrowanie "w locie" dla zgodności SKU i EAN
            if ($isMatchFilterActive && (string)$row['match_ok'] !== $filterMatchRaw) {
                continue; // Pomiń ten wiersz, jeśli nie pasuje do filtra zgodności SKU
            }

            if ($isEanMatchFilterActive) {
                // Konwertujemy true/false/null na '1'/'0' do porównania z filtrem
                $eanMatchValue = $row['ean_match'] === null ? null : (string)(int)$row['ean_match'];
                if ($eanMatchValue === null || $eanMatchValue !== $filterEanMatchRaw) {
                    continue; // Pomiń ten wiersz, jeśli nie pasuje do filtra zgodności EAN
                }
            }

            
            // Uzupełnij VAT z obiektu oferty, jeśli brak w bazie
            // Uzupełnij VAT z obiektu oferty, jeśli brak w bazie
            if (!isset($row['vat_defined']) || $row['vat_defined'] === null || $row['vat_defined'] === '') {
                $vatDefinedLocal = null;
                $vatRateLocal = null;
                $preferred = 'PL';
                if (isset($offer->taxSettings) && isset($offer->taxSettings->rates) && is_array($offer->taxSettings->rates)) {
                    $anyRate = null;
                    foreach ($offer->taxSettings->rates as $rateObj) {
                        $rateStr = (is_object($rateObj) && isset($rateObj->rate)) ? trim((string)$rateObj->rate) : '';
                        $country = (is_object($rateObj) && isset($rateObj->countryCode)) ? strtoupper(trim((string)$rateObj->countryCode)) : '';
                        if ($rateStr !== '' && $country === $preferred) {
                            $vatDefinedLocal = '1'; $vatRateLocal = $rateStr; break;
                        }
                        if ($rateStr !== '' && $anyRate === null) {
                            $anyRate = $rateStr;
                        }
                    }

                    // FILTR VAT: pokaż tylko zgodnie z wyborem w nagłówku
                    if (isset($filterVatRaw)) {
                        $valVat = isset($row['vat_defined']) ? $row['vat_defined'] : null;
                        if ($filterVatRaw === '') {
                            // '—' => brak danych: NULL lub pusty string
                            if (!($valVat === null || $valVat === '')) {
                                continue;
                            }
                        } elseif ((string)$valVat !== (string)$filterVatRaw) {
                            continue;
                        }
                    }
                    if ($vatDefinedLocal === null && $anyRate !== null) {
                        $vatDefinedLocal = '1'; $vatRateLocal = $anyRate;
                    }
                    if ($vatDefinedLocal === null) { $vatDefinedLocal = '0'; }
                } elseif (isset($offer->tax) && isset($offer->tax->percentage)) {
                    $vatDefinedLocal = (trim((string)$offer->tax->percentage) !== '') ? '1' : '0';
                    $vatRateLocal = (string)$offer->tax->percentage;
                }
                if ($vatDefinedLocal !== null) {
                    $row['vat_defined'] = $vatDefinedLocal;
                    if ($vatRateLocal !== null) { $row['vat_rate'] = $vatRateLocal; }
                }
            }
    

            // Uzupełnij VAT z obiektu oferty albo dociągnij z Allegro, jeśli brak
            // Uzupełnij VAT z obiektu oferty, jeśli brak w bazie
            if (!isset($row['vat_defined']) || $row['vat_defined'] === null || $row['vat_defined'] === '') {
                $vatDefinedLocal = null;
                $vatRateLocal = null;
                $preferred = 'PL';
                if (isset($offer->taxSettings) && isset($offer->taxSettings->rates) && is_array($offer->taxSettings->rates)) {
                    $anyRate = null;
                    foreach ($offer->taxSettings->rates as $rateObj) {
                        $rateStr = (is_object($rateObj) && isset($rateObj->rate)) ? trim((string)$rateObj->rate) : '';
                        $country = (is_object($rateObj) && isset($rateObj->countryCode)) ? strtoupper(trim((string)$rateObj->countryCode)) : '';
                        if ($rateStr !== '' && $country === $preferred) {
                            $vatDefinedLocal = '1'; $vatRateLocal = $rateStr; break;
                        }
                        if ($rateStr !== '' && $anyRate === null) {
                            $anyRate = $rateStr;
                        }
                    }
                    if ($vatDefinedLocal === null && $anyRate !== null) {
                        $vatDefinedLocal = '1'; $vatRateLocal = $anyRate;
                    }
                    if ($vatDefinedLocal === null) { $vatDefinedLocal = '0'; }
                } elseif (isset($offer->tax) && isset($offer->tax->percentage)) {
                    $vatDefinedLocal = (trim((string)$offer->tax->percentage) !== '') ? '1' : '0';
                    $vatRateLocal = (string)$offer->tax->percentage;
                }
                if ($vatDefinedLocal !== null) {
                    $row['vat_defined'] = $vatDefinedLocal;
                    if ($vatRateLocal !== null) { $row['vat_rate'] = $vatRateLocal; }
                }
            }
    

// ================= KONIEC KODU DO WKLEJENIA =================

                    // FILTR VAT: pokaż tylko zgodnie z wyborem w nagłówku
                    if (isset($filterVatRaw)) {
                        $valVat = isset($row['vat_defined']) ? $row['vat_defined'] : null;
                        if ($filterVatRaw === '') {
                            // '—' => brak danych: NULL lub pusty string
                            if (!($valVat === null || $valVat === '')) {
                                continue;
                            }
                        } elseif ((string)$valVat !== (string)$filterVatRaw) {
                            continue;
                        }
                    }
                    $processedData[] = $row;
                }
            }
            $this->_list = $processedData;

            if (isset($this->context->cookie->{$this->getCookieFilterPrefix().$this->table.'Filter_binded'})) { $this->_list = array_filter($this->_list, function ($listItem) { return (int)$listItem['binded'] == (int)$this->context->cookie->{$this->getCookieFilterPrefix().$this->table.'Filter_binded'}; }); }
            if (isset($this->context->cookie->{$this->getCookieFilterPrefix().$this->table.'Filter_archived'})) { $this->_list = array_filter($this->_list, function ($listItem) { return (int)$listItem['archived'] == (int)$this->context->cookie->{$this->getCookieFilterPrefix().$this->table.'Filter_archived'}; }); }
            if (isset($this->context->cookie->{$this->getCookieFilterPrefix().$this->table.'Filter_auto_renew'})) { $this->_list = array_filter($this->_list, function ($listItem) { $filter = $this->context->cookie->{$this->getCookieFilterPrefix().$this->table.'Filter_auto_renew'}; if ($filter == 'default') { return $listItem['auto_renew'] === null; } else { return is_numeric($listItem['auto_renew']) && (int)$listItem['auto_renew'] === (int)$filter; } }); }
            if (isset($this->context->cookie->{$this->getCookieFilterPrefix().$this->table.'Filter_id_shop'})) { $this->_list = array_filter($this->_list, function ($listItem) { return (int)$listItem['id_shop'] == (int)$this->context->cookie->{$this->getCookieFilterPrefix().$this->table.'Filter_id_shop'}; }); }
            
            // NOWY BLOK FILTROWANIA DLA KONTROLI CENY
            if (isset($this->context->cookie->{$this->getCookieFilterPrefix().$this->table.'Filter_price_check'}) && $this->context->cookie->{$this->getCookieFilterPrefix().$this->table.'Filter_price_check'} != '') {
                $this->_list = array_filter($this->_list, function ($listItem) {
                    return $listItem['price_check'] == $this->context->cookie->{$this->getCookieFilterPrefix().$this->table.'Filter_price_check'};
                });
            }
            
                        // BLOK FILTROWANIA DLA NARZUTU BRUTTO (kolory)
            if (isset($this->context->cookie->{$this->getCookieFilterPrefix().$this->table.'Filter_margin_color'})
                && $this->context->cookie->{$this->getCookieFilterPrefix().$this->table.'Filter_margin_color'} !== '') {
                $filterMarginColor = (string)$this->context->cookie->{$this->getCookieFilterPrefix().$this->table.'Filter_margin_color'};

                $this->_list = array_values(array_filter($this->_list, function ($listItem) use ($filterMarginColor) {
                    if (!isset($listItem['margin_percentage'])) {
                        return false;
                    }
                    $p = (float)$listItem['margin_percentage'];
                    $color = ($p < 20) ? 'red' : (($p <= 35) ? 'yellow' : 'green');
                    return $color === $filterMarginColor;
                }));
            }

if ($isQuantityMatchFilterActive) {
                $filterVal = (string)$this->context->cookie->{$filterQuantityMatchKey};
                $this->_list = array_values(array_filter($this->_list, function ($item) use ($filterVal) {
                    if (!isset($item['quantity_match'])) {
                        return false;
                    }
                    $itemValue = (string)$item['quantity_match'];

                    if ($filterVal === 'eq') {
                        return $itemValue === 'eq';
                    } elseif ($filterVal === 'neq') {
                        return in_array($itemValue, ['lt', 'gt']);
                    } elseif ($filterVal === 'gt') {
                        return $itemValue === 'gt';
                    } elseif ($filterVal === 'lt') {
                        return $itemValue === 'lt';
                    }
                    return false;
                }));
            }

            if ($filterReference) { $this->_list = array_filter($this->_list, function ($listItem) use ($filterReference) { return strpos($listItem['reference'], $filterReference) !== false; }); }
            if ($filterEan13) { $this->_list = array_filter($this->_list, function ($listItem) use ($filterEan13) { return false !== strpos($listItem['ean13'], $filterEan13); }); }
            if ($filterAllegroEan) { $this->_list = array_filter($this->_list, function ($listItem) use ($filterAllegroEan) { return false !== strpos($listItem['allegro_ean'], $filterAllegroEan); }); }
            
            // ZMIANA: Uproszczona i bardziej niezawodna logika filtrowania po stronie PHP
            
            
            $ob = $this->context->cookie->xallegroauctionslistxallegro_auctionOrderby ?? null;
            $ow = strtolower($this->context->cookie->xallegroauctionslistxallegro_auctionOrderway ?? 'asc');

            // ZMIANA: Dodano 'price_check' do tablicy do sortowania
            if ($ob === 'margin') {
                $mult = ($ow === 'desc') ? -1 : 1;
                if (is_array($this->_list)) {
                    usort($this->_list, function($a, $b) use ($mult) {
                        $va = isset($a['margin_percentage']) ? (float)$a['margin_percentage'] : -INF;
                        $vb = isset($b['margin_percentage']) ? (float)$b['margin_percentage'] : -INF;
                        if ($va == $vb) {
                            return 0;
                        }
                        return ($va < $vb ? -1 : 1) * $mult;
                    });
                }
            } else if (in_array($ob, ['external','reference', 'allegro_ean', 'ean13', 'price_check', 'vat_defined'], true) && is_array($this->_list)) {
                $mult = ($ow === 'desc') ? -1 : 1;
                usort($this->_list, function($a, $b) use ($ob, $mult) {
                    $va = isset($a[$ob]) ? (string)$a[$ob] : '';
                    $vb = isset($b[$ob]) ? (string)$b[$ob] : '';
                    return $mult * strnatcasecmp($va, $vb);
                });
            }

            // ZMIANA: Dodano $isPriceCheckFilterActive do warunku
            if ($isMatchFilterActive || $isQuantityMatchFilterActive || $filterAllegroEan || $isEanMatchFilterActive || $isPriceCheckFilterActive || $isMarginColorFilterActive || $isVatFilterActive) {
                $this->_listTotal = count($this->_list);
                // Usunięcie paginacji po stronie PHP dla aktywnych filtrów
            } else {
                $this->_listTotal = $totalCountFromApi;
            }

        } catch (Exception $ex) {
            $this->errors[] = (string)$ex;
        }
    }

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

    private function formatOfferStatus($status)
    {
        switch ($status) {
            case PublicationStatus::INACTIVE:
                return $this->l('szkic');

            case PublicationStatus::ACTIVATING:
                return $this->l('zaplanowana');

            case PublicationStatus::ACTIVE:
                return $this->l('aktywna');

            case PublicationStatus::ENDED:
                return $this->l('zakończona');
        }

        return null;
    }

    /************************************************************************************************
     * ZASTĄP CAŁĄ METODĘ getFieldsList
     * Zmiany: Ustawiono 'orderby' => true dla kolumn 'allegro_ean' i 'ean13'.
     ************************************************************************************************/
    private function getFieldsList($profile = null)
    {
        $shopList = [];
        foreach (Shop::getShops() as $shop) {
            $shopList[$shop['id_shop']] = $shop['name'];
        }

        $fieldsList = [
            'image' => [
                'title' => '', 'width' => 'auto', 'align' => 'center', 'search' => false, 'orderby' => false,
                'settings' => [ 'title' => $this->l('Zdjęcie'), 'default' => true ]
            ],
            'id_auction' => [
                'title' => $this->l('ID'), 'width' => 'auto', 'class' => 'fixed-width-md', 'search' => true, 'orderby' => false,
                'settings' => [ 'readonly' => true, 'default' => true ]
            ],
            'name' => [
                'title' => $this->l('Tytuł oferty'), 'class' => (version_compare(_PS_VERSION_, '1.7.8.0', '<') ? ' column-name' : ''),
                'width' => 'auto', 'search' => true, 'orderby' => false,
                'settings' => [ 'readonly' => true, 'default' => true ]
            ],
// START: DODANA KOLUMNA WAGI
            'weight' => [
                'title' => $this->l('Waga (kg)'),          // Tytuł kolumny
                'align' => 'right',                   // Wyrównanie do prawej
                'class' => 'fixed-width-sm',          // Szerokość kolumny
                'search' => false,                    // Wyłączenie wyszukiwania po wadze
                'orderby' => false,                   // Wyłączenie sortowania po wadze
                'callback' => 'printWeight',          // Funkcja formatująca wygląd (stworzymy ją później)
                'settings' => [ 'default' => true ]   // Domyślnie widoczna, z możliwością ukrycia
            ],
            // KONIEC: DODANA KOLUMNA WAGI
            'match_ok' => [
                'title'   => $this->l('Zgodność (Sig = Indeks)'),
                'align'   => 'center',
                'search'  => true,
                'orderby' => false,
                'hint'   => $this->l('Tak – Sygnatura i Indeks są identyczne; Nie – różne lub puste.'),
                'callback' => 'printMatchOk',
                'type' => 'bool',
                'settings'=> ['default' => true],
            ],
            
            'vat_defined' => [
    'title'      => $this->l('VAT (Allegro)'),
    'align'      => 'center',
    'type'       => 'select',
    'list'       => ['' => '—', '1' => $this->l('TAK'), '0' => $this->l('NIE')],
    'filter_key' => 'vat_defined',
    'search'     => true,
    'orderby'    => true,   // ← było false
    'callback'   => 'printVatDefined',
    'settings'   => ['default' => true],
],

'external' => [
                'title' => $this->l('Sygnatura'), 'class' => 'fixed-width-md', 'search' => true, 'orderby' => true,
                'settings' => [ 'default' => true ]
            ],
            'quantity' => [
                'title' => $this->l('Ilość'), 'class' => 'fixed-width-xs', 'align' => 'center', 'search' => false, 'orderby' => false,
                'settings' => [ 'desc' => $this->l('tylko Kup teraz'), 'default' => true ]
            ],
            'quantity_shop' => [
                'title' => $this->l('Ilość w sklepie'), 'class' => 'fixed-width-xs', 'align' => 'center', 'search' => false, 'orderby' => false,
                'settings' => [ 'desc' => $this->l('tylko Kup teraz'), 'default' => true ]
            ],
            'quantity_match' => [
                'title' => $this->l('Zgodność ilości'),
                'hint' => $this->l('Porównuje ilość na Allegro z ilością w sklepie. Dotyczy tylko powiązanych ofert.'),
                'class' => 'fixed-width-sm',
                'align' => 'center',
                'search' => true,
                'orderby' => false,
                'type' => 'select',
                'filter_key' => 'quantity_match',
                'list' => [
                    'eq' => $this->l('Tak (zgodne)'),
                    'neq' => $this->l('Nie (różne)'),
                    'gt' => $this->l('Tylko Allegro > Sklep'),
                    'lt' => $this->l('Tylko Sklep > Allegro')
                ],
                'callback' => 'printQuantityMatch',
                'settings' => ['default' => true, 'desc' => $this->l('tylko Kup teraz')]
            ],
            'shop_name' => [
                'title' => $this->l('Sklep'), 'class' => 'fixed-width-md', 'type' => 'select', 'list' => $shopList,
                'filter_key' => 'id_shop', 'orderby' => false,
                'settings' => [ 'default' => Shop::isFeatureActive(), 'desc' => $this->l('tylko w opcji multistore') ]
            ],
            'reference' => [
                'title' => $this->l('Indeks'), 'class' => 'fixed-width-md', 'search' => true, 'orderby' => true,
                'settings' => [ 'default' => true ]
            ],
            'allegro_ean' => [
                'title'    => $this->l('EAN (Allegro)'),
                'class'    => 'fixed-width-md',
                'search'   => true,
                'orderby'  => true, // ZMIANA: Włączenie sortowania
                'callback' => 'printAllegroEan',
                'settings' => ['default' => true],
            ],
            'ean13' => [
                'title' => $this->l('Ean'), 'class' => 'fixed-width-md', 'search' => true, 'orderby' => true, // ZMIANA: Włączenie sortowania
                'settings' => [ 'default' => true ]
            ],
            'ean_match' => [
                'title' => $this->l('Zgodność EAN'),
                'hint' => $this->l('Porównuje EAN Allegro z EAN produktu w sklepie. Dotyczy tylko powiązanych ofert.'),
                'align' => 'center',
                'class' => 'fixed-width-sm',
                'search' => true,
                'orderby' => false,
                'type' => 'bool',
                'callback' => 'printEanMatch',
                'filter_key' => 'ean_match',
                'settings' => ['default' => true]
            ],
            'price' => [
                'title' => $this->l('Cena'), 'class' => 'text-right' . (version_compare(_PS_VERSION_, '1.7.8.0', '<') ? ' column-price' : ''),
                'align' => 'right', 'search' => false, 'orderby' => false,
                'settings' => [ 'default' => true ]
            ],
            'price_pl' => [
                'title' => $this->l('Cena (PL)'), 'marketplace' => XAllegroApi::MARKETPLACE_PL,
                'class' => 'text-right' . (version_compare(_PS_VERSION_, '1.7.8.0', '<') ? ' column-price_pl' : ''),
                'align' => 'right', 'search' => false, 'orderby' => false
            ],
            // START: DODANA KOLUMNA KONTROLI CENY
            'price_check' => [
                'title' => $this->l('Kontrola Ceny/kg'),
                'align' => 'center',
                'class' => 'fixed-width-sm',
                'search' => true, // Włączamy filtrowanie
                'orderby' => true, // Włączamy sortowanie
                'type' => 'select', // Typ filtra to lista rozwijana
                'filter_key' => 'price_check', // Klucz filtra
                'list' => [ // Opcje do wyboru w filtrze
                    'ok' => $this->l('OK'),
                    'error' => $this->l('Błąd'),
                    'no_ref' => $this->l('Brak ref.')
                ],
                'callback' => 'printPriceCheck',
                'hint' => $this->l('Sprawdza cenę produktów > '.self::MIN_WEIGHT_FOR_CHECK.'kg. BŁĄD, gdy cena całkowita jest niższa niż (cena/kg produktu ref. * '.self::PRICE_CHECK_MULTIPLIER.'). Jeśli brak produktu ref., status jest OK, gdy cena > '.self::PRICE_FALLBACK_THRESHOLD.' zł.'),
                'settings' => ['default' => true]
            ],
            // KONIEC: DODANA KOLUMNA KONTROLI CENY
            'margin' => [
                'title' => $this->l('Narzut brutto'),
                'align' => 'right',
                'search' => true,
                'orderby' => true,
                'type' => 'select',
                'filter_key' => 'margin_color',
                'list' => [
                    'red' => $this->l('Czerwony'),
                    'yellow' => $this->l('Żółty'),
                    'green' => $this->l('Zielony'),
                ],
                'callback' => 'printMargin',
                'settings' => ['default' => true]
            ],
            'price_cz' => [
                'title' => $this->l('Cena (CZ)'), 'marketplace' => XAllegroApi::MARKETPLACE_CZ,
                'class' => 'text-right' . (version_compare(_PS_VERSION_, '1.7.8.0', '<') ? ' column-price_cz' : ''),
                'align' => 'right', 'search' => false, 'orderby' => false
            ],
            'price_sk' => [
                'title' => $this->l('Cena (SK)'), 'marketplace' => XAllegroApi::MARKETPLACE_SK,
                'class' => 'text-right' . (version_compare(_PS_VERSION_, '1.7.8.0', '<') ? ' column-price_sk' : ''),
                'align' => 'right', 'search' => false, 'orderby' => false
            ],
            'price_hu' => [
                'title' => $this->l('Cena (HU)'), 'marketplace' => XAllegroApi::MARKETPLACE_HU,
                'class' => 'text-right' . (version_compare(_PS_VERSION_, '1.7.8.0', '<') ? ' column-price_hu' : ''),
                'align' => 'right', 'search' => false, 'orderby' => false
            ],
            'offers' => [
                'title' => $this->l('Ofert'), 'class' => 'fixed-width-xs', 'align' => 'center', 'search' => false, 'orderby' => false,
                'settings' => [ 'desc' => $this->l('tylko licytacje'), 'default' => true ]
            ],
            'sold' => [
                'title' => $this->l('Sprzedano'), 'hint' => $this->l('Ostatnie 30 dni'), 'class' => 'fixed-width-xs', 'align' => 'center',
                'search' => false, 'orderby' => true,
                'settings' => [ 'desc' => $this->l('tylko Kup teraz'), 'default' => true ]
            ],
            'sold_pl' => [
                'title' => $this->l('Sprzedano (PL)'), 'hint' => $this->l('Ostatnie 30 dni'), 'marketplace' => XAllegroApi::MARKETPLACE_PL,
                'class' => 'fixed-width-xs', 'align' => 'center', 'search' => false, 'orderby' => false,
                'settings' => [ 'desc' => $this->l('tylko Kup teraz') ]
            ],
            'sold_cz' => [
                'title' => $this->l('Sprzedano (CZ)'), 'hint' => $this->l('Ostatnie 30 dni'), 'marketplace' => XAllegroApi::MARKETPLACE_CZ,
                'class' => 'fixed-width-xs', 'align' => 'center', 'search' => false, 'orderby' => false,
                'settings' => [ 'desc' => $this->l('tylko Kup teraz') ]
            ],
            'sold_sk' => [
                'title' => $this->l('Sprzedano (SK)'), 'hint' => $this->l('Ostatnie 30 dni'), 'marketplace' => XAllegroApi::MARKETPLACE_SK,
                'class' => 'fixed-width-xs', 'align' => 'center', 'search' => false, 'orderby' => false,
                'settings' => [ 'desc' => $this->l('tylko Kup teraz') ]
            ],
            'sold_hu' => [
                'title' => $this->l('Sprzedano (HU)'), 'hint' => $this->l('Ostatnie 30 dni'), 'marketplace' => XAllegroApi::MARKETPLACE_HU,
                'class' => 'fixed-width-xs', 'align' => 'center', 'search' => false, 'orderby' => false,
                'settings' => [ 'desc' => $this->l('tylko Kup teraz') ]
            ],
            'visits' => [
                'title' => $this->l('Wizyt'), 'hint' => $this->l('Ostatnie 30 dni'), 'class' => 'fixed-width-xs', 'align' => 'center',
                'search' => false, 'orderby' => false
            ],
            'visits_pl' => [
                'title' => $this->l('Wizyt (PL)'), 'hint' => $this->l('Ostatnie 30 dni'), 'marketplace' => XAllegroApi::MARKETPLACE_PL,
                'class' => 'fixed-width-xs', 'align' => 'center', 'search' => false, 'orderby' => false
            ],
            'visits_cz' => [
                'title' => $this->l('Wizyt (CZ)'), 'hint' => $this->l('Ostatnie 30 dni'), 'marketplace' => XAllegroApi::MARKETPLACE_CZ,
                'class' => 'fixed-width-xs', 'align' => 'center', 'search' => false, 'orderby' => false
            ],
            'visits_sk' => [
                'title' => $this->l('Wizyt (SK)'), 'hint' => $this->l('Ostatnie 30 dni'), 'marketplace' => XAllegroApi::MARKETPLACE_SK,
                'class' => 'fixed-width-xs', 'align' => 'center', 'search' => false, 'orderby' => false
            ],
            'visits_hu' => [
                'title' => $this->l('Wizyt (HU)'), 'hint' => $this->l('Ostatnie 30 dni'), 'marketplace' => XAllegroApi::MARKETPLACE_HU,
                'class' => 'fixed-width-xs', 'align' => 'center', 'search' => false, 'orderby' => false
            ],
            'start' => [
                'title' => $this->l('Data rozpoczęcia'), 'class' => 'fixed-width-md', 'search' => false, 'orderby' => false,
                'settings' => [ 'default' => true ]
            ],
            'end' => [
                'title' => $this->l('Data zakończenia'), 'class' => 'fixed-width-md', 'search' => false, 'orderby' => false,
                'settings' => [ 'default' => true ]
            ],
            'status' => [
                'title' => $this->l('Status'), 'class' => (version_compare(_PS_VERSION_, '1.7.8.0', '<') ? ' column-status' : ''),
                'search' => false, 'orderby' => false, 'settings' => [ 'default' => true ]
            ],
            'status_pl' => [
                'title' => $this->l('Status (PL)'), 'marketplace' => XAllegroApi::MARKETPLACE_PL,
                'class' => (version_compare(_PS_VERSION_, '1.7.8.0', '<') ? ' column-status_pl' : ''), 'search' => false, 'orderby' => false
            ],
            'status_cz' => [
                'title' => $this->l('Status (CZ)'), 'marketplace' => XAllegroApi::MARKETPLACE_CZ,
                'class' => (version_compare(_PS_VERSION_, '1.7.8.0', '<') ? ' column-status_cz' : ''), 'search' => false, 'orderby' => false
            ],
            'status_sk' => [
                'title' => $this->l('Status (SK)'), 'marketplace' => XAllegroApi::MARKETPLACE_SK,
                'class' => (version_compare(_PS_VERSION_, '1.7.8.0', '<') ? ' column-status_sk' : ''), 'search' => false, 'orderby' => false
            ],
            'status_hu' => [
                'title' => $this->l('Status (HU)'), 'marketplace' => XAllegroApi::MARKETPLACE_HU,
                'class' => (version_compare(_PS_VERSION_, '1.7.8.0', '<') ? ' column-status_hu' : ''), 'search' => false, 'orderby' => false
            ],
            'marketplace' => [
                'title' => $this->l('Rynek'), 'class' => (version_compare(_PS_VERSION_, '1.7.8.0', '<') ? ' column-marketplace' : ''),
                'search' => false, 'orderby' => false, 'settings' => [ 'desc' => $this->l('tylko Kup teraz'), 'default' => true ]
            ],
            'binded' => [
                'title' => $this->l('Powiązana'), 'hint' => $this->l('Powiązana z produktem'), 'align' => 'center',
                'class' => 'fixed-width-xs' . (version_compare(_PS_VERSION_, '1.7.8.0', '<') ? ' column-binded' : ''),
                'type' => 'bool', 'search' => true, 'orderby' => false, 'settings' => [ 'default' => true ]
            ],
            'archived' => [
                'title' => $this->l('Zarchiwizowana'), 'hint' => $this->l('Powiązanie zarchiwizowane w bazie danych'), 'align' => 'center',
                'class' => 'fixed-width-xs', 'type' => 'bool', 'icon' => [ '0' => ['class' => 'icon-minus'], '1' => ['class' => 'icon-archive'] ],
                'search' => true, 'orderby' => false, 'settings' => [ 'default' => true ]
            ],
            'auto_renew' => [
                'title' => $this->l('Wznawianie'), 'hint' => $this->l('Opcja auto wznawiania'),
                'class' => 'fixed-width-md x-auction-list-auto_renew', 'search' => true, 'orderby' => false,
                'filter_key' => 'auto_renew', 'type' => 'select',
                'list' => [ 'default' => $this->l('domyślnie'), '1' => $this->l('tak'), '0' => $this->l('nie'), '-1' => $this->l('błąd wznawiania') ],
                'settings' => [ 'default' => true, 'desc' => $this->l('tylko Kup teraz') ]
            ]
        ];

        if (!$profile) {
            return $fieldsList;
        }

        if ($this->allegroCookie->{$this->getAllegroCookieFilter('offerType')} === 'buy_now') {
            unset($fieldsList['offers']);
        }
        else if ($this->allegroCookie->{$this->getAllegroCookieFilter('offerType')} === 'auction') {
            unset(
                $fieldsList['quantity'], $fieldsList['quantity_shop'], $fieldsList['quantity_match'], $fieldsList['price_cz'], $fieldsList['price_sk'],
                $fieldsList['price_hu'], $fieldsList['visits_cz'], $fieldsList['visits_sk'], $fieldsList['visits_hu'],
                $fieldsList['sold'], $fieldsList['sold_cz'], $fieldsList['sold_pl'], $fieldsList['sold_sk'],
                $fieldsList['sold_hu'], $fieldsList['marketplace'], $fieldsList['status_cz'], $fieldsList['status_sk'],
                $fieldsList['status_hu'], $fieldsList['auto_renew']
            );
        }

        if (!Shop::isFeatureActive()) {
            unset($fieldsList['shop_name']);
        }

        $auctionFieldsListSettings = json_decode(XAllegroConfiguration::get('AUCTION_FIELDS_LIST_SETTINGS'), true);
        $fieldsListProfile = [];

        if (isset($auctionFieldsListSettings[$profile])) {
            foreach ($fieldsList as $fieldId => $field) {
                if (!isset($auctionFieldsListSettings[$profile][$fieldId]) || (int)$auctionFieldsListSettings[$profile][$fieldId]) {
                    $fieldsListProfile[$fieldId] = $field;
                }
            }
        }
        else {
            foreach ($fieldsList as $fieldId => $field) {
                if (isset($field['settings']['default']) && $field['settings']['default']) {
                    $fieldsListProfile[$fieldId] = $field;
                }
            }
        }

        return $fieldsListProfile;
    }

    public function ajaxProcessGetAuctionFormModal()
    {
        if ($this->tabAccess['edit'] !== '1') {
            die(json_encode([
                'success' => false,
                'message' => $this->l('Nie masz uprawnień do edycji w tym miejscu.')
            ]));
        }

        $formAction = Tools::getValue('formAction');
        $auctions = [];

        if ($formAction == 'finish') {
            foreach (Tools::getValue('auctions', []) as $item) {
                $auctions[$item['id']] = [
                    'id_auction' => $item['id'],
                    'title' => $item['title'],
                    'href' => XAllegroApi::generateOfferUrl($item['id'], $this->allegroApi->getAccount()->sandbox)
                ];
            }
        } elseif ($formAction == 'update') {
            foreach (Tools::getValue('auctions', []) as $item) {
                $auctions[$item['id']] = [
                    'id_auction' => $item['id'],
                    'title' => $item['title'],
                    'href' => XAllegroApi::generateOfferUrl($item['id'], $this->allegroApi->getAccount()->sandbox)
                ];
            }
        } else {
            $auctionsPOST = [];

            foreach (Tools::getValue('auctions', []) as $item) {
                $auctionsPOST[$item['id']] = $item['title'];
            }

            switch ($formAction) {
                case 'redo': $closed = 1; break;
                default: $closed = false;
            }

            $auctions = XAllegroAuction::getAuctionsByAllegroId(array_keys($auctionsPOST), $closed);

            if (empty($auctions)) {
                die(json_encode([
                    'success' => false,
                    'message' => $this->l('Nie znaleziono żadnej z wybranych ofert.')
                ]));
            }

            foreach ($auctions as &$auction) {
                $auction['title'] = $auctionsPOST[$auction['id_auction']];
                $auction['href'] = XAllegroApi::generateOfferUrl($auction['id_auction'], $this->allegroApi->getAccount()->sandbox);

                if ($formAction == 'redo') {
                    $productOOS = XAllegroProduct::setOOS(StockAvailable::outOfStock($auction['id_product']));
                    $productQuantity = StockAvailable::getQuantityAvailableByProduct($auction['id_product'], $auction['id_product_attribute'], $auction['id_shop']);
                    $productDisabledByQuantity = XAllegroProduct::setDisabledByQuantity($productQuantity, $productOOS, $this->allegroApi->getAccount()->id);
                    $productDisabledByActive = XAllegroProduct::setDisabledByActive((int)$auction['shop_active']);

                    if (!XAllegroConfiguration::get('QUANITY_ALLEGRO_ALWAYS_MAX') && $auction['quantity'] < $productQuantity) {
                        $auctionQuantity = XAllegroProduct::calculateQuantity($auction['quantity'], $productOOS);
                    } else {
                        $auctionQuantity = XAllegroProduct::calculateQuantity($productQuantity, $productOOS);
                    }

                    $auction['redoData'] = [
                        'status' => XAllegroAuction::getAuctionsStatus($auction['id_product'], $auction['id_product_attribute'], $auction['id_xallegro_account'], $auction['id_shop']),
                        'productOOS' => $productOOS,
                        'productQuantity' => $productQuantity,
                        'auctionQuantityMax' => XAllegroApiTools::calculateMaxQuantity($productQuantity),
                        'auctionQuantity' => $auctionQuantity,
                        'auctionDisabled' => (int)($productDisabledByQuantity || $productDisabledByActive)
                    ];
                }
            }
        }

        $controllerAjaxUrl = $this->context->link->getAdminLink('AdminXAllegroAuctionsList') . '&ajax=1';

        $tpl = $this->context->smarty->createTemplate($this->module->getLocalPath() . 'views/templates/admin/' . $this->tpl_folder . 'helpers/list/auction-form-modal.tpl');
        
        $tpl->assign([
            'allegroAccountId' => (int)$this->allegroApi->getAccount()->id,
            'formAction' => $formAction,
            'auctions' => $auctions,
            'availableUpdateEntities' => ($formAction == 'update' ? (new EntityUpdaterFinder($this->allegroApi))->getUpdatersForView() : null),
            'token' => Tools::getAdminTokenLite('AdminXAllegroAuctionsList'),
            'controllerAjaxUrl' => $controllerAjaxUrl,
        ]);

        die(json_encode([
            'success' => true,
            'html' => $tpl->fetch()
        ]));
    }

    public function ajaxProcessGetProductList()
    {
        $query = Tools::getValue('q', false);

        if (!$query || strlen($query) < 1) {
            die();
        }

        if ($pos = strpos($query, ' (ref:')) {
            $query = substr($query, 0, $pos);
        }

        $items = Db::getInstance()->executeS('
            SELECT p.`id_product`, p.`reference`, pl.`name`
            FROM `'._DB_PREFIX_.'product` p
            '.Shop::addSqlAssociation('product', 'p').'
            LEFT JOIN `'._DB_PREFIX_.'product_lang` pl
                ON (pl.`id_product` = p.`id_product` AND pl.`id_lang` = ' . (int)$this->allegroApi->getAccount()->id_language . Shop::addSqlRestrictionOnLang('pl') . ')
            WHERE (pl.`name` LIKE "%' . pSQL($query).  '%" 
                OR p.`reference` LIKE "%' . pSQL($query) . '%"' .
            (is_numeric($query) ? ' OR p.`id_product` = ' . (int)$query : '') . ')' . '
            GROUP BY p.`id_product`
        ');

        foreach ($items AS $item) {
            $item['reference'] = str_replace('|', '', $item['reference']);
            $item['name'] = str_replace('|', '', $item['name']);

            echo 'id: ' . $item['id_product'] . ' - '.trim($item['name']) . (!empty($item['reference']) ? ' (ref: ' . $item['reference'] . ')' : '') . '|' . (int)($item['id_product']) . "\n";
        }

        die();
    }

    public function ajaxProcessGetAttributes()
    {
        $product = new Product((int)Tools::getValue('id_product'));
        die(json_encode($product->getAttributesResume((int)$this->allegroApi->getAccount()->id_language)));
    }

    public function ajaxProcessAuctionUpdate()
    {
        // BB: intercept custom entities before default Updater
        $entity = Tools::getValue('entity');
        if ($entity === 'bbFetchVatAndSaveToDb' || $entity === 'bbDebugOfferRaw' || $entity === 'bbDebugOfferRawJson') {
            $auctionProcessedIndex = (int)Tools::getValue('auctionIndex');
            
        $auctions = Tools::getValue('auctions', []);
        $offerId = null;
        if (is_array($auctions) && isset($auctions[$auctionProcessedIndex])) {
            $offerId = (string)$auctions[$auctionProcessedIndex];
        }
        // dodatkowe heurystyki – różne nazwy pól używane w X13
        if (!$offerId) { $offerId = (string)Tools::getValue('auctionId'); }
        if (!$offerId) { $offerId = (string)Tools::getValue('id_auction'); }
        if (!$offerId) { $offerId = (string)Tools::getValue('xAllegroAuctionId'); }
        if (!$offerId) { $offerId = (string)Tools::getValue('id'); }
        if (!$offerId) { $offerId = (string)Tools::getValue('offer_id'); }

        if (!$offerId) {
            // Zwróć informacyjnie jakie klucze dotarły, żeby łatwo było zdiagnozować
            $debugKeys = implode(', ', array_keys($_POST));
            die(json_encode([
                'success' => false,
                'continue' => true,
                'message' => 'Brak ID oferty (POST keys: ' . $debugKeys . ')',
                'processed' => ++$auctionProcessedIndex
            ]));
        }


            if ($entity === 'bbFetchVatAndSaveToDb') {
                $msg = '';
                $ok = false;
                try {
                    $result = $this->allegroApi->sale()->offers()->getList(['offer.id' => $offerId]);
                    if (isset($result->offers[0])) {
                        $o = $result->offers[0];
                        $preferred = 'PL';
                        $rates = [];
                        if (isset($o->taxSettings) && isset($o->taxSettings->rates) && is_array($o->taxSettings->rates)) {
                            foreach ($o->taxSettings->rates as $rateObj) {
                                $rates[] = [
                                    'country' => (is_object($rateObj) && isset($rateObj->countryCode)) ? strtoupper((string)$rateObj->countryCode) : '',
                                    'rate' => (is_object($rateObj) && isset($rateObj->rate)) ? (string)$rateObj->rate : ''
                                ];
                            }
                        }
                        $chosen = null;
                        foreach ($rates as $r) { if ($r['rate'] !== '' && $r['country'] === $preferred) { $chosen = $r; break; } }
                        if ($chosen === null && !empty($rates)) { $chosen = $rates[0]; }
                        if ($chosen === null && isset($o->tax) && isset($o->tax->percentage) && trim((string)$o->tax->percentage) !== '') {
                            $chosen = ['country' => '', 'rate' => (string)$o->tax->percentage];
                        }

                        if ($chosen !== null) {
                            Db::getInstance()->update('xallegro_auction', [
                                'vat_defined' => 1,
                                'vat_rate' => (is_numeric($chosen['rate']) ? pSQL(number_format((float)$chosen['rate'], 2, '.', '')) : pSQL((string)$chosen['rate'])),
                            ], "id_auction = '" . pSQL($offerId) . "'");
                            $msg = 'VAT zaktualizowano: ' . $offerId . ' => ' . $chosen['rate'] . ( $chosen['country'] ? ' (' . $chosen['country'] . ')' : '' );
                            $ok = true;
                        } else {
                            Db::getInstance()->update('xallegro_auction', [
                                'vat_defined' => 0,
                                'vat_rate' => null,
                            ], "id_auction = '" . pSQL($offerId) . "'");
                            $msg = 'Brak stawek VAT w ofercie ' . $offerId;
                        }
                    } else {
                        $msg = 'Oferta nieznaleziona przez API: ' . $offerId;
                    }
                } catch (Exception $e) {
                    $msg = 'Błąd: ' . (string)$e;
                }

                die(json_encode([
                    'success' => true,
                    'continue' => true,
                    'message' => $msg,
                    'processed' => ++$auctionProcessedIndex
                ]));
            }

            if ($entity === 'bbDebugOfferRaw' || $entity === 'bbDebugOfferRawJson') {
                try {
                    $result = $this->allegroApi->sale()->offers()->getList(['offer.id' => $offerId]);
                    $raw = isset($result->offers[0]) ? json_decode(json_encode($result->offers[0]), true) : null;
                    die(json_encode([
                        'success' => true,
                        'continue' => true,
                        'message' => 'RAW fetched',
                        'raw' => $raw,
                        'processed' => ++$auctionProcessedIndex
                    ]));
                } catch (Exception $e) {
                    die(json_encode([
                        'success' => false,
                        'continue' => true,
                        'message' => 'Błąd: ' . (string)$e,
                        'processed' => ++$auctionProcessedIndex
                    ]));
                }
            }
        }

        $entity = Tools::getValue('entity');

        if (in_array($entity, ['bb_link_sku', 'bb_link_eanprefix'])) {
            die(json_encode([
                'success' => true,
                'continue' => false,
                'message' => '',
                'processed' => (int)Tools::getValue('auctionIndex')
            ]));
        }

        $auctionProcessedIndex = (int)Tools::getValue('auctionIndex');

        try {
            $updater = new Updater($entity, $this->allegroApi);
        }
        catch (Exception $e) {
            die(json_encode([
                'success' => false,
                'continue' => true,
                'message' => (string)$e,
                'processed' => $auctionProcessedIndex
            ]));
        }

        $result = $updater->handle();

        if (!$result['success']) {
            die(json_encode([
                'success' => false,
                'continue' => true,
                'asWarning' => (isset($result['as_warning']) && $result['as_warning']),
                'message' => $result['message'],
                'messageOnFinish' => $updater->getMessageOnFinish(),
                'processed' => ++$auctionProcessedIndex
            ]));
        }
        
        die(json_encode([
            'success' => true,
            'continue' => true,
            'message' => $result['message'],
            'messageOnFinish' => $updater->getMessageOnFinish(),
            'processed' => ++$auctionProcessedIndex
        ]));
    
            // X13 modal often sends current offer id as 'auction'
            if (!$offerId) {
                $offerId = (string)Tools::getValue('auction');
            // Obsługa formatu z separatorem (np. "1778...|123", "1778...,123")
            if ($offerId) {
                $auctionRaw = $offerId;
                if (strpos($offerId, '|') !== false) {
                    $offerId = explode('|', $offerId)[0];
                } elseif (strpos($offerId, ',') !== false) {
                    $offerId = explode(',', $offerId)[0];
                }
                // zostaw tylko cyfry
                $offerId = preg_replace('/[^0-9]/', '', $offerId);
                if ($offerId === '') { $offerId = $auctionRaw; } // jeśli same cyfry usunęły wszystko, wróć do raw (może alfanumeryczne)
            }
}
            // Fallbacks – różne nazwy mogą wystąpić w zależności od wersji
            if (!$offerId) { $offerId = (string)Tools::getValue('id_auction'); }
            if (!$offerId) { $offerId = (string)Tools::getValue('xAllegroAuctionId'); }
            if (!$offerId) { $offerId = (string)Tools::getValue('offer_id'); }
            if (!$offerId) { $offerId = (string)Tools::getValue('id'); }
}
    
/************************************************************************************************
     * UWAGA: Zastąp całą funkcję ajaxProcessBbLink poniższym kodem.
     * Zmiany obejmują szczegółowe raportowanie błędów.
     ************************************************************************************************/
    public function ajaxProcessBbLink()
    {
        if ($this->tabAccess['edit'] !== '1') {
            die(json_encode(['success' => false, 'message' => $this->l('Brak uprawnień.')]));
        }

        // --- Zbieranie parametrów ---
        $mode = Tools::getValue('mode');
        $auctionIds = Tools::getValue('auctions', []);
        $verifyEan = (bool)Tools::getValue('verify_ean', false);
        $overwriteLinks = (bool)Tools::getValue('overwrite_links', false);
        $prefixesRaw = Tools::getValue('prefixes', '');
        $prefixes = !empty($prefixesRaw) ? array_map('trim', explode(',', $prefixesRaw)) : [];

        if (!in_array($mode, ['sku', 'eanprefix']) || empty($auctionIds)) {
            die(json_encode(['success' => false, 'message' => $this->l('Nieprawidłowe parametry.')]));
        }

        // --- Inicjalizacja ---
        // ZMIANA: Rozbudowana struktura do zbierania szczegółowych statystyk
        $stats = [
            'ok' => [],
            'skip' => [],
            'fail' => [],
            'fail_details' => []
        ];
        $id_shop = (int)$this->context->shop->id;
        $id_xallegro_account = (int)$this->allegroApi->getAccount()->id;
        $moduleDir = dirname(dirname(__DIR__));
        $dumpDir = $moduleDir . '/EAN_UPDATE/';
        $verificationFilePath = $dumpDir . 'linking_verification_' . $this->context->employee->id . '_' . time() . '.json';
        
        try {
            // --- FAZA 1: Zbieranie danych i tworzenie pliku weryfikacyjnego ---

            if (!is_dir($dumpDir) && !mkdir($dumpDir, 0755, true)) {
                throw new Exception('Nie można utworzyć katalogu do zapisu plików: ' . $dumpDir);
            }

            $localEansMap = [];
            if (!empty($auctionIds)) {
                $idsIn = implode(',', array_map(function($id) { return '"' . pSQL((string)$id) . '"'; }, $auctionIds));
                $eanResults = Db::getInstance()->executeS('SELECT id_auction, ean_allegro FROM `'._DB_PREFIX_.'xallegro_auction` WHERE id_auction IN ('.$idsIn.')');
                foreach ($eanResults as $row) {
                    if (!empty($row['ean_allegro'])) {
                        $localEansMap[(string)$row['id_auction']] = trim($row['ean_allegro']);
                    }
                }
            }

            $provider = new OfferProvider($this->allegroApi, true);
            $verificationDataForFile = [];
            foreach ($auctionIds as $auctionId) {
                $offer = $provider->getOfferProductDetails($auctionId);
                $offerSku = (isset($offer->external) && isset($offer->external->id)) ? trim($offer->external->id) : null;
                $localEan = isset($localEansMap[$auctionId]) ? $localEansMap[$auctionId] : null;

                $verificationDataForFile[$auctionId] = [
                    'sku' => $offerSku,
                    'ean_allegro' => $localEan,
                    'selling_mode' => strtoupper($offer->sellingMode->format),
                    'start' => (in_array($offer->publication->status, [PublicationStatus::INACTIVE, PublicationStatus::ACTIVATING]) ? 1 : 0),
                    'closed' => (in_array($offer->publication->status, [PublicationStatus::INACTIVE, PublicationStatus::ENDED]) ? 1 : 0),
                ];
            }

            if (file_put_contents($verificationFilePath, json_encode($verificationDataForFile, JSON_PRETTY_PRINT)) === false) {
                throw new Exception('Nie można zapisać tymczasowego pliku weryfikacyjnego.');
            }

            // --- FAZA 2: Weryfikacja i łączenie na podstawie danych z pliku ---
            
            $dataFromFile = json_decode(file_get_contents($verificationFilePath), true);
            $existingBindings = XAllegroAuction::getAuctionAssociationsForList(array_keys($dataFromFile));
            $boundAuctionIds = array_map(function($b) { return (string)$b['id_auction']; }, $existingBindings);

            foreach ($dataFromFile as $auctionId => $data) {
                if (!$overwriteLinks && in_array((string)$auctionId, $boundAuctionIds)) {
                    $stats['skip'][] = $auctionId;
                    continue;
                }

                $sku = $data['sku'];
                $ean_allegro = $data['ean_allegro'];
                $id_product = 0;
                $id_product_attribute = 0;
                $row = null;

                if ($mode === 'sku') {
                    if (!$sku) {
                        $stats['fail'][] = $auctionId;
                        $stats['fail_details'][] = ['id' => $auctionId, 'sku' => 'Brak', 'reason' => $this->l('W ofercie Allegro brak numeru Sygnatury (SKU).')];
                        continue;
                    }
                    
                    $condEan = '';
                    if ($verifyEan) {
                        if (empty($ean_allegro)) {
                            $stats['fail'][] = $auctionId;
                            $stats['fail_details'][] = ['id' => $auctionId, 'sku' => $sku, 'reason' => $this->l('Weryfikacja EAN jest włączona, ale dla tej oferty brak numeru EAN.')];
                            continue;
                        }
                        $condEan = ' AND pa.ean13 = "'.pSQL($ean_allegro).'"';
                    }
                    
                    // Poprawiona, solidna logika wyszukiwania produktu
                    $rows_combinations = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('SELECT pa.id_product, pa.id_product_attribute FROM `'._DB_PREFIX_.'product_attribute` pa WHERE pa.reference = "'.pSQL($sku).'"'.$condEan.' LIMIT 2');
                    
                    $condEanProduct = ($verifyEan && $ean_allegro) ? ' AND p.ean13 = "'.pSQL($ean_allegro).'"' : '';
                    $rows_products = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('SELECT p.id_product, 0 AS id_product_attribute FROM `'._DB_PREFIX_.'product` p WHERE p.reference = "'.pSQL($sku).'"'.$condEanProduct.' LIMIT 2');
                    
                    $candidates = array_merge($rows_combinations ?: [], $rows_products ?: []);
                    
                    $row = (count($candidates) === 1) ? $candidates[0] : null;

                    // ZMIANA: Jeśli $row jest null, dodaj szczegółowy błąd
                    if (!$row) {
                        $stats['fail'][] = $auctionId;
                        $reason = count($candidates) > 1 ? $this->l('Znaleziono wiele pasujących produktów (niejednoznaczność).') : $this->l('Nie znaleziono żadnego pasującego produktu w bazie sklepu.');
                        $stats['fail_details'][] = ['id' => $auctionId, 'sku' => $sku, 'reason' => $reason];
                    }
                }
                elseif ($mode === 'eanprefix') {
                    // Logika dla eanprefix - dla uproszczenia bez szczegółowego raportowania, można rozbudować w razie potrzeby
                }

                if ($row && !empty($row['id_product'])) {
                    $id_product = (int)$row['id_product'];
                    $id_product_attribute = (int)$row['id_product_attribute'];
                }

                if ($id_product > 0) {
                    $collection = new PrestaShopCollection(XAllegroAuction::class);
                    $collection->where('id_auction', '=', (string)$auctionId);
                    $collection->where('id_xallegro_account', '=', $id_xallegro_account);
                    $binding = $collection->getFirst();

                    if (!$binding) {
                        $binding = new XAllegroAuction();
                        $binding->id_auction = (string)$auctionId;
                        $binding->id_xallegro_account = $id_xallegro_account;
                    }
                    
                    $binding->id_product = $id_product;
                    $binding->id_product_attribute = $id_product_attribute;
                    $binding->id_shop = (int)$id_shop;
                    $binding->id_shop_group = (int)$this->context->shop->id_shop_group;
                    $binding->selling_mode = $data['selling_mode'];
                    $binding->start = $data['start'];
                    $binding->closed = $data['closed'];
                    $binding->ean_allegro = $data['ean_allegro'];
                    $binding->save();
                    
                    $stats['ok'][] = $auctionId;
                }
                // (Obsługa błędu przeniesiona wyżej, do logiki wyszukiwania)
            }
        } catch (Exception $e) {
            die(json_encode(['success' => false, 'message' => $e->getMessage()]));
        } finally {
             // Usunięcie pliku jest tymczasowo wykomentowane w celach diagnostycznych
             /*
             if (file_exists($verificationFilePath)) {
                 unlink($verificationFilePath);
             }
             */
        }
    
        // ZMIANA: Zwróć rozbudowane statystyki, w tym liczby i szczegóły błędów
        die(json_encode([
            'success' => true,
            'ok' => count($stats['ok']),
            'skip' => count($stats['skip']),
            'fail' => count($stats['fail']),
            'fail_details' => $stats['fail_details']
        ]));
    }
// =================================================================================
// KONIEC: BLOK ODPOWIEDZIALNY ZA MASOWE ŁĄCZENIE (SKU / EAN)
// =================================================================================

/************************************************************************************************
     * NOWA METODA do obsługi pojedynczej oferty w trybie sekwencyjnym z paskiem postępu.
     ************************************************************************************************/
    public function ajaxProcessBbLinkSingle()
    {
        if ($this->tabAccess['edit'] !== '1') {
            die(json_encode(['success' => false, 'message' => $this->l('Brak uprawnień.')]));
        }

        // --- Zbieranie parametrów ---
        $auctionId = Tools::getValue('auctionId');
        $mode = Tools::getValue('mode');
        $verifyEan = (bool)Tools::getValue('verify_ean', false);
        $overwriteLinks = (bool)Tools::getValue('overwrite_links', false);
        $prefixesRaw = Tools::getValue('prefixes', '');
        $prefixes = !empty($prefixesRaw) ? array_map('trim', explode(',', $prefixesRaw)) : [];

        if (empty($auctionId) || !in_array($mode, ['sku', 'eanprefix'])) {
            die(json_encode(['success' => false, 'message' => $this->l('Nieprawidłowe parametry.')]));
        }

        // --- Inicjalizacja ---
        $id_shop = (int)$this->context->shop->id;
        $id_xallegro_account = (int)$this->allegroApi->getAccount()->id;

        try {
            if (!$overwriteLinks) {
                $binding = XAllegroAuction::getAuctionByAllegroId($auctionId);
                if ($binding) {
                    die(json_encode(['success' => true, 'status' => 'skip']));
                }
            }

            $provider = new OfferProvider($this->allegroApi, true);
            $offer = $provider->getOfferProductDetails($auctionId);

            $sku = (isset($offer->external) && isset($offer->external->id)) ? trim($offer->external->id) : null;
if ($verifyEan && empty($ean_allegro)) {
                // Jeśli API nie zwróciło EAN (np. dla oferty zakończonej),
                // spróbuj pobrać go z naszej bazy, gdzie mógł być zapisany wcześniej.
                $ean_allegro = Db::getInstance()->getValue('
                    SELECT `ean_allegro`
                    FROM `'._DB_PREFIX_.'xallegro_auction`
                    WHERE `id_auction` = "'.pSQL($auctionId).'"'
                );
                // Upewnij się, że wynik jest stringiem i usuń białe znaki
                $ean_allegro = trim((string)$ean_allegro);
            }            
            $id_product = 0;
            $id_product_attribute = 0;
            $row = null;

            if ($mode === 'sku') {
                if (!$sku) {
                    die(json_encode([
                        'success' => false,
                        'message' => $this->l('Brak SKU w ofercie.'),
                        'status' => 'fail',
                        'details' => ['id' => $auctionId, 'sku' => 'Brak', 'reason' => $this->l('W ofercie Allegro brak numeru Sygnatury (SKU).')]
                    ]));
                }
                if ($verifyEan && empty($ean_allegro)) {
                    die(json_encode([
                        'success' => false,
                        'message' => $this->l('Brak EAN do weryfikacji.'),
                        'status' => 'fail',
                        'details' => ['id' => $auctionId, 'sku' => $sku, 'reason' => $this->l('Weryfikacja EAN jest włączona, ale dla tej oferty brak numeru EAN.')]
                    ]));
                }
                
                $condEan = ' AND pa.ean13 = "'.pSQL($ean_allegro).'"';
                $condEanProduct = ' AND p.ean13 = "'.pSQL($ean_allegro).'"';

                $rows_combinations = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('SELECT pa.id_product, pa.id_product_attribute FROM `'._DB_PREFIX_.'product_attribute` pa WHERE pa.reference = "'.pSQL($sku).'"'.($verifyEan ? $condEan : '').' LIMIT 2');
                $rows_products = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('SELECT p.id_product, 0 AS id_product_attribute FROM `'._DB_PREFIX_.'product` p WHERE p.reference = "'.pSQL($sku).'"'.($verifyEan ? $condEanProduct : '').' LIMIT 2');
                $candidates = array_merge($rows_combinations ?: [], $rows_products ?: []);
                $row = (count($candidates) === 1) ? $candidates[0] : null;
            }

            if ($row && !empty($row['id_product'])) {
                $id_product = (int)$row['id_product'];
                $id_product_attribute = (int)$row['id_product_attribute'];

                $collection = new PrestaShopCollection(XAllegroAuction::class);
                $collection->where('id_auction', '=', (string)$auctionId);
                $collection->where('id_xallegro_account', '=', $id_xallegro_account);
                $binding = $collection->getFirst();

                if (!$binding) {
                    $binding = new XAllegroAuction();
                    $binding->id_auction = (string)$auctionId;
                    $binding->id_xallegro_account = $id_xallegro_account;
                }
                
                $binding->id_product = $id_product;
                $binding->id_product_attribute = $id_product_attribute;
                $binding->id_shop = $id_shop;
                $binding->id_shop_group = (int)$this->context->shop->id_shop_group;
                $binding->selling_mode = strtoupper($offer->sellingMode->format);
                $binding->start = (in_array($offer->publication->status, [PublicationStatus::INACTIVE, PublicationStatus::ACTIVATING]) ? 1 : 0);
                $binding->closed = (in_array($offer->publication->status, [PublicationStatus::INACTIVE, PublicationStatus::ENDED]) ? 1 : 0);
                $binding->ean_allegro = $ean_allegro;
                $binding->save();

                die(json_encode(['success' => true, 'status' => 'ok']));
            } else {
                $reason = count($candidates) > 1 ? $this->l('Znaleziono wiele pasujących produktów (niejednoznaczność).') : $this->l('Nie znaleziono żadnego pasującego produktu w bazie sklepu.');
                die(json_encode([
                    'success' => false,
                    'message' => $reason,
                    'status' => 'fail',
                    'details' => ['id' => $auctionId, 'sku' => $sku, 'reason' => $reason]
                ]));
            }

        } catch (Exception $e) {
             die(json_encode([
                'success' => false,
                'message' => $e->getMessage(),
                'status' => 'fail',
                'details' => ['id' => $auctionId, 'sku' => 'N/A', 'reason' => $e->getMessage()]
            ]));
        }
    }


    public function ajaxProcessAuctionFinish()
    {
        $auctionId = Tools::getValue('auction');
        $auction = XAllegroAuction::getAuctionByAllegroId($auctionId);

        $auctionProcessedIndex = (int)Tools::getValue('auctionIndex');
        $auctionHref = $this->generateAuctionHref($auctionId);
        
        $resource = $this->allegroApi->sale()->productOffers();

        try {
            $offerUpdate = new OfferUpdate($auctionId);
            $offerUpdate->publication = new StdClass();
            $offerUpdate->publication->status = PublicationStatus::ENDED;

            $resource->update($offerUpdate);

            $this->log
                ->account((int)$this->allegroApi->getAccount()->id)
                ->offer($offerUpdate->id)
                ->info(LogType::OFFER_PUBLICATION_STATUS_ENDED());

            if ($resource->getCode() == 202) {
                $replayData = new StdClass();
                $replayData->operationId = basename($resource->getHeaders()->location);

                die(json_encode([
                    'success' => true,
                    'continue' => true,
                    'message' => sprintf('Oferta %s: <em>Trwa zamykanie...</em>', $auctionHref),
                    'asPlaceholder' => true,
                    'processed' => $auctionProcessedIndex,
                    'replayAction' => 'auctionFinishReplay',
                    'replayData' => $replayData
                ]));
            }
        }
        catch (Exception $exception) {
            die(json_encode([
                'success' => false,
                'continue' => true,
                'message' => sprintf('Błąd zamykania oferty %s: <em>%s.</em>', $auctionHref, $exception),
                'processed' => ++$auctionProcessedIndex
            ]));
        }

        if ($auction) {
            XAllegroAuction::closeAuction($auctionId, new DateTime());
            XAllegroAuction::updateAuctionAutoRenew($auctionId, 0);
        }

        die(json_encode([
            'success' => true,
            'continue' => true,
            'message' => sprintf('Oferta %s: <em>Poprawnie zamknięta.</em>', $auctionHref),
            'messageOnFinish' => 'Zamknięto wybrane oferty, zamknij aby kontynuować.',
            'processed' => ++$auctionProcessedIndex
        ]));
    }


    public function ajaxProcessAuctionFinishReplay()
    {
        sleep(2);
        $auctionId = Tools::getValue('auction');
        $auctionProcessedIndex = (int)Tools::getValue('auctionIndex');
        $auctionHref = $this->generateAuctionHref($auctionId);
        $operationId = Tools::getValue('replayData')['operationId'];
        $resource = $this->allegroApi->sale()->productOffers();
        try {
            $resource->updateOperationCheck($auctionId, $operationId);
            $this->log->account((int)$this->allegroApi->getAccount()->id)->offer($auctionId)->info(LogType::OFFER_PROCESS_OPERATION_CHECK(), ['operationId' => $operationId]);
            if ($resource->getCode() == 202) {
                $replayData = new StdClass();
                $replayData->operationId = basename($resource->getHeaders()->location);
                die(json_encode([
                    'success' => true, 'continue' => true, 'message' => '', 'processed' => $auctionProcessedIndex,
                    'replayAction' => 'auctionFinishReplay', 'replayData' => $replayData
                ]));
            } else if ($resource->getCode() == 303) {
                $auction = XAllegroAuction::getAuctionByAllegroId($auctionId);
                if($auction) {
                    XAllegroAuction::closeAuction($auctionId, new DateTime());
                    XAllegroAuction::updateAuctionAutoRenew($auctionId, 0);
                }
                // POPRAWKA: Użycie poprawnego komunikatu bez niedostępnej zmiennej.
                die(json_encode([
                    'success' => true, 'continue' => true, 'message' => sprintf('Oferta %s: <em>Poprawnie zamknięta.</em>', $auctionHref),
                    'messageOnFinish' => 'Zamknięto wybrane oferty, zamknij aby kontynuować.', 'processed' => ++$auctionProcessedIndex
                ]));
            }
        } catch (Exception $exception) {
            die(json_encode([
                'success' => false, 'continue' => true, 'message' => sprintf('Błąd zamykania oferty %s: <em>%s.</em>', $auctionHref, $exception),
                'processed' => ++$auctionProcessedIndex
            ]));
        }
    }

    public function ajaxProcessAuctionRedo()
    {
        $error = false;
        $auctionId = Tools::getValue('auction');
        $auction = XAllegroAuction::getAuctionByAllegroId($auctionId);
        $auctionQuantity = (int)Tools::getValue('auctionQuantity');
        $auctionProcessedIndex = (int)Tools::getValue('auctionIndex');
        $auctionHref = $this->generateAuctionHref($auctionId);
        if (!$auction) { $error = $this->l('Nie znaleziono powiązania w bazie danych.'); }
        else if ($auctionQuantity <= 0 || $auctionQuantity > XAllegroApi::QUANTITY_MAX) { $error = $this->l('Podano błędną ilość.'); }
        else if (XAllegroConfiguration::get('QUANITY_CHECK')) {
            $productOOS = XAllegroProduct::setOOS(StockAvailable::outOfStock((int)$auction->id_product));
            $productQuantity = StockAvailable::getQuantityAvailableByProduct((int)$auction->id_product, (int)$auction->id_product_attribute, (int)$auction->id_shop);
            if (XAllegroProduct::setDisabledByQuantity((int)$productQuantity, $productOOS, (int)$this->allegroApi->getAccount()->id)) {
                $error = $this->l('Brak odpowiedniej ilości produktu w sklepie.');
            }
        } else if (XAllegroProduct::setDisabledByActive(XAllegroHelper::getActiveByProductId((int)$auction->id_product, (int)$auction->id_shop))) {
            $error = $this->l('Produkt jest nieaktywny w sklepie.');
        }
        if ($error !== false) {
            die(json_encode(['success' => false, 'continue' => true, 'message' => sprintf('Błąd wznowienia oferty %s: <em>%s.</em>', $auctionHref, $error), 'processed' => ++$auctionProcessedIndex]));
        }
        $resource = $this->allegroApi->sale()->productOffers();
        try {
            $offerUpdate = new OfferUpdate($auction->id_auction);
            $offerUpdate->stock = new Stock();
            $offerUpdate->stock->available = $auctionQuantity;
            $offerUpdate->publication = new Publication();
            $offerUpdate->publication->status = PublicationStatus::ACTIVE;
            $resource->update($offerUpdate);
            $this->log->account((int)$this->allegroApi->getAccount()->id)->offer($offerUpdate->id)->info(LogType::OFFER_PUBLICATION_STATUS_ACTIVE(), ['quantity' => $auctionQuantity]);
            if ($resource->getCode() == 202) {
                $replayData = new StdClass();
                $replayData->operationId = basename($resource->getHeaders()->location);
                XAllegroAuction::startAuction($auctionId);
                die(json_encode([
                    'success' => true, 'continue' => true, 'message' => sprintf('Oferta %s: <em>Trwa wznawianie...</em>', $auctionHref),
                    'asPlaceholder' => true, 'processed' => $auctionProcessedIndex, 'replayAction' => 'auctionRedoReplay', 'replayData' => $replayData
                ]));
            }
        } catch (Exception $exception) {
            die(json_encode(['success' => false, 'continue' => true, 'message' => sprintf('Błąd wznowienia oferty %s: <em>%s.</em>', $auctionHref, $exception), 'processed' => ++$auctionProcessedIndex]));
        }
        XAllegroAuction::updateAuctionQuantity($auctionQuantity, $auctionId);
        XAllegroAuction::activeAuction($auctionId);
        XAllegroAuction::updateAuctionAutoRenew($auctionId, Tools::getValue('auctionAutoRenew', null));
        die(json_encode([
            'success' => true, 'continue' => true, 'message' => sprintf('Oferta %s: <em>Wznowiona z ilością: %d.</em>', $auctionHref, $auctionQuantity),
            'messageOnFinish' => 'Wznowiono wybrane oferty, zamknij aby kontynuować.', 'processed' => ++$auctionProcessedIndex
        ]));
    }

    public function ajaxProcessAuctionRedoReplay()
    {
        sleep(2);
        $auctionId = Tools::getValue('auction');
        $auctionQuantity = (int)Tools::getValue('auctionQuantity');
        $auctionProcessedIndex = (int)Tools::getValue('auctionIndex');
        $auctionHref = $this->generateAuctionHref($auctionId);
        $operationId = Tools::getValue('replayData')['operationId'];
        $resource = $this->allegroApi->sale()->productOffers();
        try {
            $resource->updateOperationCheck($auctionId, $operationId);
            $this->log->account((int)$this->allegroApi->getAccount()->id)->offer($auctionId)->info(LogType::OFFER_PROCESS_OPERATION_CHECK(), ['operationId' => $operationId]);
            if ($resource->getCode() == 202) {
                $replayData = new StdClass();
                $replayData->operationId = basename($resource->getHeaders()->location);
                die(json_encode([
                    'success' => true, 'continue' => true, 'message' => '', 'processed' => $auctionProcessedIndex,
                    'replayAction' => 'auctionRedoReplay', 'replayData' => $replayData
                ]));
            } else if ($resource->getCode() == 303) {
                $auction = XAllegroAuction::getAuctionByAllegroId($auctionId);
                if($auction) {
                    XAllegroAuction::closeAuction($auctionId, new DateTime());
                    XAllegroAuction::updateAuctionAutoRenew($auctionId, 0);
                }
                die(json_encode([
                    'success' => true, 'continue' => true, 'message' => sprintf('Oferta %s: <em>Wznowiona z ilością: %d.</em>', $auctionHref, $auctionQuantity),
                    'messageOnFinish' => 'Wznowiono wybrane oferty, zamknij aby kontynuować.', 'processed' => ++$auctionProcessedIndex
                ]));
            }
        } catch (Exception $exception) {
            die(json_encode([
                'success' => false, 'continue' => true, 'message' => sprintf('Błąd wznowienia oferty %s: <em>%s.</em>', $auctionHref, $exception),
                'processed' => ++$auctionProcessedIndex
            ]));
        }
    }

    public function ajaxProcessChangeAutoRenew()
    {
        $offerId = Tools::getValue('offerId');
        $autoRenew = Tools::getValue('autoRenew', null);
        $success = true;
        if (is_array($offerId)) {
            foreach ($offerId as $id) {
                $success &= XAllegroAuction::updateAuctionAutoRenew($id, $autoRenew);
            }
        } else {
            $success = XAllegroAuction::updateAuctionAutoRenew($offerId, $autoRenew);
        }
        die(json_encode(['success' => $success]));
    }

    public function ajaxProcessSaveAuctionListSettings()
    {
        $listSettings = ['default' => Tools::getValue('fields')];
        XAllegroConfiguration::updateValue('AUCTION_FIELDS_LIST_SETTINGS', json_encode($listSettings));
        $this->processResetFilters();
        die(json_encode(['url' => $this->context->link->getAdminLink('AdminXAllegroAuctionsList') . '&offerType=' . Tools::getValue('offerType')]));
    }

    private function generateAuctionHref($auctionId)
    {
        return sprintf('<a href="%s" target="_blank" rel="nofollow"><b>%s</b></a>',
            XAllegroApi::generateOfferUrl($auctionId, $this->allegroApi->getAccount()->sandbox),
            $auctionId
        );
    }

    private function arrayInsertAfter(array $array, $key, array $new)
    {
        $keys = array_keys($array);
        $index = array_search($key, $keys);
        $pos = false === $index ? count($array) : $index + 1;
        return array_slice($array, 0, $pos, true) + $new + array_slice($array, $pos, count($array) - 1, true);
    }

    private function getHiddenAuctionIds($accountId)
    {
        if (!$accountId) {
            return [];
        }

        try {
            $sql = new DbQuery();
            $sql->select('id_auction');
            $sql->from('xallegro_auction_hidden'); 
            $sql->where('id_xallegro_account = ' . (int)$accountId);

            $results = Db::getInstance()->executeS($sql);
        } catch (Exception $e) {
            error_log('Allegro module error (getHiddenAuctionIds): ' . $e->getMessage());

            if (stripos($e->getMessage(), 'exist') !== false || stripos($e->getMessage(), 'base table or view not found') !== false) {
                 $this->errors[] = $this->l('Tabela bazy danych dla ukrytych aukcji (xallegro_auction_hidden) prawdopodobnie nie istnieje. Utwórz ją, aby funkcja ukrywania działała poprawnie.');
            }

            return [];
        }

        $ids = [];
        if ($results) {
            foreach ($results as $row) {
                $ids[] = (string)$row['id_auction'];
            }
        }
        return $ids;
    }

    private function hideAuctions(array $auctionIds, $accountId)
    {
        if (empty($auctionIds) || !$accountId) {
            return false;
        }

        $data = [];
        $now = date('Y-m-d H:i:s');
        foreach ($auctionIds as $id) {
            if (empty($id)) continue;

            $data[] = [
                'id_auction' => pSQL($id),
                'id_xallegro_account' => (int)$accountId,
                'date_add' => $now,
            ];
        }

        if (empty($data)) {
            return false;
        }

        try {
            return Db::getInstance()->insert('xallegro_auction_hidden', $data, false, true, Db::INSERT_IGNORE);
        } catch (Exception $e) {
            error_log('Allegro module error (hideAuctions): ' . $e->getMessage());
            return false;
        }
    }

    private function renderStatisticsPlaceholder()
    {
        $templatePath = $this->module->getLocalPath() . 'views/templates/admin/' . $this->tpl_folder . 'helpers/list/stats_placeholder.tpl';
        
        if (!file_exists($templatePath)) {
            return '<div class="alert alert-warning">Błąd modułu: Brak pliku szablonu statystyk (stats_placeholder.tpl).</div>';
        }

        $tpl = $this->context->smarty->createTemplate($templatePath);

        $ajaxParams = [
            'action' => 'GetBindingStatistics', 
            'id_xallegro_account' => (int)$this->allegroApi->getAccount()->id,
            'offerType' => $this->allegroCookie->{$this->getAllegroCookieFilter('offerType')},
            'offerStatus' => $this->allegroCookie->{$this->getAllegroCookieFilter('offerStatus')},
            'offerMarketplace' => $this->allegroCookie->{$this->getAllegroCookieFilter('offerMarketplace')},
        ];

        $tpl->assign([
            'stats_ajax_url' => $this->context->link->getAdminLink('AdminXAllegroAuctionsList'),
            'stats_ajax_params_json' => json_encode($ajaxParams), 
            'token' => Tools::getAdminTokenLite('AdminXAllegroAuctionsList'),
            'loading_message' => $this->l('Obliczanie statystyk powiązań... Może to chwilę potrwać, szczególnie przy dużej liczbie ofert.')
        ]);

        return $tpl->fetch();
    }

    public function ajaxProcessGetBindingStatistics()
    {
        @set_time_limit(300);

        if (!$this->allegroApi || (int)$this->allegroApi->getAccount()->id != (int)Tools::getValue('id_xallegro_account')) {
            die(json_encode(['success' => false, 'message' => $this->l('Błąd autoryzacji lub niezgodność konta. Odśwież stronę.')]));
        }

        $offerType = Tools::getValue('offerType', 'buy_now');
        $offerStatusRaw = Tools::getValue('offerStatus', 'active');
        $offerMarketplace = Tools::getValue('offerMarketplace', 'all');

        $apiFilters = [
            'sellingMode.format' => strtoupper($offerType),
        ];

        if ($offerStatusRaw === 'all') {
             $apiFilters['publication.status'] = 'INACTIVE,ACTIVE,ACTIVATING,ENDED';
        } else {
             $apiFilters['publication.status'] = strtoupper($offerStatusRaw);
        }

        if ($offerMarketplace !== 'all') {
            $apiFilters['publication.marketplace'] = $offerMarketplace;
        }

        $hiddenAuctionIds = [];
        if (stripos(strtoupper($offerStatus), 'ENDED') !== false) {
            $hiddenAuctionIds = $this->getHiddenAuctionIds((int)$this->allegroApi->getAccount()->id);
        }

        $countUnlinked = 0;
        $countMismatched = 0;
        $totalOffers = 0;
        $maxOffersToProcess = 55000; 

        try {
            $limit = 100;
            $offset = 0;
            
            do {
                $result = $this->allegroApi->sale()->offers()->getList($apiFilters, $limit, $offset);
                $offersBatchFromApi = $result->offers;
                
                $visibleOffersBatch = [];
                foreach ($offersBatchFromApi as $offer) {
                    if (!empty($hiddenAuctionIds) && in_array((string)$offer->id, $hiddenAuctionIds)) {
                        continue;
                    }
                    $visibleOffersBatch[] = $offer;
                }

                $batchCount = count($visibleOffersBatch);
                $totalOffers += $batchCount;

                if ($batchCount > 0) {
                    $batchIds = [];
                    $apiOffersData = [];

                    foreach ($visibleOffersBatch as $offer) {
                        $offerIdStr = (string)$offer->id;
                        $batchIds[] = $offerIdStr;
                        $apiOffersData[$offerIdStr] = [
                            'external_id' => (is_object($offer->external) ? $offer->external->id : null)
                        ];
                    }

                    $bindings = XAllegroAuction::getAuctionAssociationsForList($batchIds);

                    $boundIds = [];
                    foreach ($bindings as $binding) {
                        $offerId = (string)$binding['id_auction'];
                        $boundIds[] = $offerId;

                        $apiExternalId = isset($apiOffersData[$offerId]['external_id']) ? (string)$apiOffersData[$offerId]['external_id'] : '';
                        $dbReference = isset($binding['reference']) ? (string)$binding['reference'] : '';
                        $isMatchOk = ($apiExternalId !== '' && $dbReference !== '' && $apiExternalId === $dbReference);
                        
                        if (!$isMatchOk) {
                             $countMismatched++;
                        }
                    }

                    $unlinkedCountInBatch = $batchCount - count($bindings);
                    $countUnlinked += $unlinkedCountInBatch;
                }

                $offset += $limit;

                if (empty($result->offers) || count($result->offers) < $limit) {
                    break;
                }

                if ($totalOffers >= $maxOffersToProcess) {
                    throw new Exception(sprintf($this->l('Przekroczono limit bezpieczeństwa %d ofert. Statystyki są niepełne. Użyj węższych filtrów.'), $maxOffersToProcess));
                }

            } while (true);

            die(json_encode([
                'success' => true,
                'html' => sprintf(
                    '<div class="alert alert-info" style="background-color: #d9edf7; border-color: #bce8f1; color: #31708f;">
                         <p><strong>%s</strong> (%s: %s, %s: %s)</p>
                         <ul style="list-style-type: none; padding: 0; margin-top: 10px;">
                             <li><i class="icon-list"></i> %s: <strong>%d</strong></li>
                             <li style="color: %s; margin-top: 5px;"><i class="icon-unlink"></i> %s: <strong>%d</strong></li>
                             <li style="color: %s; margin-top: 5px;"><i class="icon-warning-sign"></i> %s: <strong>%d</strong></li>
                         </ul>
                     </div>',
                    $this->l('Podsumowanie powiązań'),
                    $this->l('Konto'),
                    $this->allegroApi->getAccount()->username,
                    $this->l('Status'),
                    $offerStatusRaw,
                    $totalOffers,
                    ($countUnlinked > 0 ? '#d9534f' : '#5cb85c'),
                    $this->l('Niepowiązane z produktem PrestaShop'),
                    $countUnlinked,
                    ($countMismatched > 0 ? '#f0ad4e' : '#5cb85c'),
                    $this->l('Powiązane, ale niezgodna Sygnatura/Indeks'),
                    $countMismatched
                )
            ]));

        } catch (Exception $ex) {
            die(json_encode([
                'success' => false,
                'message' => $this->l('Wystąpił błąd podczas obliczania statystyk.') . '<br><em>' . htmlspecialchars($ex->getMessage()) . '</em>'
            ]));
        }
    }

    /**
     * Nowa metoda AJAX do pobierania EAN z Allegro, zapisywania do pliku i aktualizacji bazy danych.
     *
     * @return void
     */
    public function ajaxProcessBbGetEanAndSaveToFile()
    {
        header('Content-Type: application/json; charset=utf-8');

        if ($this->tabAccess['edit'] !== '1') {
            die(json_encode(['success' => false, 'message' => 'Brak uprawnień.']));
        }

        $auctionIds = \Tools::getValue('auctions', []);
        if (!is_array($auctionIds) || empty($auctionIds)) {
            die(json_encode(['success' => false, 'message' => 'Brak ID ofert do przetworzenia.']));
        }

        $provider = new \x13allegro\Api\DataProvider\OfferProvider($this->allegroApi, true);
        $moduleDir = dirname(dirname(__DIR__));
        $dumpDir = $moduleDir . '/EAN_UPDATE/';

        if (!is_dir($dumpDir)) {
            if (!mkdir($dumpDir, 0755, true)) {
                die(json_encode(['success' => false, 'message' => 'Nie można utworzyć katalogu do zapisu plików: ' . $dumpDir]));
            }
        }

        $map = [];
        $errors = [];

        foreach ($auctionIds as $auctionId) {
            $ean = '';
            $dumpFile = $dumpDir . 'ean-debug-' . pSQL($auctionId) . '.json';
            
            try {
                // Krok 1: Pobierz dane z API i zapisz do pliku
                $offer = $provider->getOfferProductDetails($auctionId);
                $offerJson = json_encode($offer, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                if ($offerJson === false) {
                     throw new Exception('Błąd kodowania danych JSON.');
                }
                if (file_put_contents($dumpFile, $offerJson) === false) {
                    throw new Exception('Błąd zapisu pliku: ' . $dumpFile);
                }

                // Krok 2: Wczytaj dane z pliku i sparsuj
                $fileContent = file_get_contents($dumpFile);
                $data = json_decode($fileContent, true);

                if ($data === null) {
                    throw new Exception('Błąd parsowania pliku JSON: ' . $dumpFile);
                }

                // Krok 3: Wyszukaj EAN w pliku, skupiając się na parametrach
                if (!empty($data['productSet'])) {
                    foreach ($data['productSet'] as $set) {
                        if (isset($set['product']['parameters']) && is_array($set['product']['parameters'])) {
                            foreach ($set['product']['parameters'] as $param) {
                                // Szukamy po ID lub nazwie, tak jak w Twoim pliku
                                if ($param['id'] === '225693' && isset($param['values'][0])) {
                                    $ean = trim($param['values'][0]);
                                    break 2;
                                }
                            }
                        }
                    }
                }

                // Krok 4: Zapisz EAN do bazy danych (logika "znajdź i zaktualizuj" lub "utwórz nowy")
                $id_auction_sql = pSQL((string)$auctionId);
                $id_xallegro_account = (int)$this->allegroApi->getAccount()->id;

                // Sprawdzamy, czy rekord dla tej oferty już istnieje w bazie
                $exists = Db::getInstance()->getValue('
                    SELECT 1 FROM `'._DB_PREFIX_.'xallegro_auction`
                    WHERE id_auction = "'.$id_auction_sql.'"
                    AND id_xallegro_account = '.$id_xallegro_account
                );

                if ($exists) {
                    // Jeśli rekord istnieje, aktualizujemy go o nowy EAN
                    Db::getInstance()->update(
                        'xallegro_auction',
                        ['ean_allegro' => pSQL($ean)],
                        'id_auction = "'.$id_auction_sql.'" AND id_xallegro_account = '.$id_xallegro_account
                    );
                } else {
                    // Jeśli rekord nie istnieje, tworzymy nowy, minimalny wpis
                    Db::getInstance()->insert(
                        'xallegro_auction',
                        [
                            'id_auction' => $id_auction_sql,
                            'id_xallegro_account' => $id_xallegro_account,
                            'ean_allegro' => pSQL($ean),
                            'id_shop' => (int)$this->context->shop->id,
                            'id_shop_group' => (int)$this->context->shop->id_shop_group,
                        ]
                    );
                }

                $map[(string)$auctionId] = $ean;

            } catch (\Exception $e) {
                $errors[] = 'ID ' . $auctionId . ': ' . $e->getMessage();
            }
        }

        die(json_encode([
            'success' => empty($errors),
            'map'     => $map,
            'errors'  => $errors,
            'message' => empty($errors) ? 'Pobrano i zapisano EAN z plików JSON.' : 'Wystąpiły błędy podczas pobierania EAN.'
        ]));
    }

    
    public function ajaxProcessBbFetchVatAndSaveToDb()
{
    $ids = Tools::getValue('auctions', []);
    $debug = (bool)Tools::getValue('debug', 0);

    if (!is_array($ids) || empty($ids)) {
        die(json_encode(['success' => false, 'message' => $this->l('Nie wybrano ofert.')]));
    }

    $updated = 0;
    $errors = [];
    $details = [];

    // Pełne dane oferty (jak w Twoim zrzucie JSON)
    $provider = new \x13allegro\Api\DataProvider\OfferProvider($this->allegroApi, true);

    foreach ($ids as $offerId) {
        $offerId = (string)$offerId;
        $rec = [
            'id' => $offerId,
            'status' => 'no_vat',
            'chosen_rate' => null,
            'chosen_country' => null,
            'source' => null,
            'rates' => [],
            'error' => null
        ];

        try {
            $offer = $provider->getOfferProductDetails($offerId);

            // Zbierz stawki z taxSettings.rates
            $rates = [];
            if (isset($offer->taxSettings) && isset($offer->taxSettings->rates) && is_array($offer->taxSettings->rates)) {
                foreach ($offer->taxSettings->rates as $rateObj) {
                    $rateStr = (is_object($rateObj) && isset($rateObj->rate)) ? trim((string)$rateObj->rate) : '';
                    $country = (is_object($rateObj) && isset($rateObj->countryCode)) ? strtoupper(trim((string)$rateObj->countryCode)) : '';
                    if ($rateStr !== '') {
                        $rates[] = ['country' => $country, 'rate' => $rateStr];
                    }
                }
            }
            $rec['rates'] = $rates;

            // Wybór stawki: preferuj PL -> pierwsza niepusta -> tax.percentage (fallback)
            $preferred = 'PL';
            $chosen = null;
            foreach ($rates as $r) {
                if ($r['country'] === $preferred) { $chosen = $r; break; }
            }
            if ($chosen === null && !empty($rates)) { $chosen = $rates[0]; }
            if ($chosen === null && isset($offer->tax) && isset($offer->tax->percentage) && trim((string)$offer->tax->percentage) !== '') {
                $chosen = ['country' => '', 'rate' => (string)$offer->tax->percentage];
            }

            if ($chosen !== null) {
                Db::getInstance()->update('xallegro_auction', [
                    'vat_defined' => 1,
                    'vat_rate' => (is_numeric($chosen['rate']) ? pSQL(number_format((float)$chosen['rate'], 2, '.', '')) : pSQL((string)$chosen['rate'])),
                ], "id_auction = '" . pSQL($offerId) . "'");

                $rec['status'] = 'updated';
                $rec['chosen_rate'] = $chosen['rate'];
                $rec['chosen_country'] = $chosen['country'];
                $rec['source'] = 'OfferProvider';
                $updated++;
            } else {
                Db::getInstance()->update('xallegro_auction', [
                    'vat_defined' => 0,
                    'vat_rate' => null,
                ], "id_auction = '" . pSQL($offerId) . "'");
                $rec['status'] = 'no_vat';
            }
        } catch (Exception $e) {
            $rec['status'] = 'error';
            $rec['error'] = (string)$e;
        }

        $details[] = $rec;
    }

    $resp = ['success' => true, 'updated' => $updated, 'errors' => $errors];
    if ($debug) { $resp['details'] = $details; }
    die(json_encode($resp));
}

public function printWeight($value, $row)
    {
        if (!isset($row['binded']) || !$row['binded'] || !is_numeric($value)) {
            return '—';
        }
        return number_format((float)$value, 2, ',', ' ');
    }

public function printPriceCheck($value, $row)
    {
        if ($value === 'ok') {
            return '<span class="badge" style="background-color:#5cb85c;">' . $this->l('OK') . '</span>';
        } elseif ($value === 'error') {
            return '<span class="badge" style="background-color:#d9534f;">' . $this->l('BŁĄD CENY') . '</span>';
        } elseif ($value === 'no_ref') {
            return '<span class="badge" style="background-color:#5bc0de;">' . $this->l('Brak ref.') . '</span>';
        }
        return '—';
    }
private function _getCleanBaseProductName($name)
    {
        $cleaned = (string)$name;
        $cleaned = preg_replace('/\s*\([^)]*\)\s*/u', ' ', $cleaned);
        $parts = preg_split('/\s*[–—-]\s*/u', $cleaned);
        if (is_array($parts) && count($parts) > 0) { $cleaned = $parts[0]; }
        $cleaned = preg_replace('/\b\d+(?:[.,]\d+)?\s*(?:g|kg|ml|l|litr(?:y|ów)?|szt|tabs?)\b/iu', ' ', $cleaned);
        $cleaned = preg_replace('/\b\d+\b/u', ' ', $cleaned);
        $cleaned = preg_replace('/\s+/u', ' ', $cleaned);
        return trim($cleaned);
    }
}
