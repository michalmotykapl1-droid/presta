<?php

require_once (dirname(__FILE__) . '/../../x13allegro.php');

final class AdminXAllegroPerformController extends XAllegroController
{
    protected $allegroAutoLogin = true;
    protected $allegroAccountSwitch = true;

    public function __construct()
    {
        $this->table = 'product';
        $this->className = 'Product';
        $this->identifier = 'id_product';
        $this->lang = true;
        $this->explicitSelect = true;
        $this->list_no_link = true;
        $this->_defaultOrderBy = 'id_product';
        $this->_defaultOrderWay = 'ASC';

        parent::__construct();

        $this->tabAccess = Profile::getProfileAccess($this->context->employee->id_profile, Tab::getIdFromClassName('AdminXAllegroPerform'));

        $this->fields_list = array(
            'id_product' => array(
                'title' => $this->l('ID'),
                'align' => 'center',
                'class' => 'fixed-width-xs',
                'type' => 'int'
            ),
            'image' => array(
                'title' => $this->l('Zdjęcie'),
                'align' => 'center',
                'image' => 'p',
                'class' => 'fixed-width-sm',
                'orderby' => false,
                'filter' => false,
                'search' => false
            ),
            'name' => array(
                'title' => $this->l('Nazwa'),
                'type' => 'auction_info',
                'filter_key' => 'b!name',
                'class' => 'fixed-width-xxl'
            ),
            'reference' => array(
                'title' => $this->l('Index'),
                'filter_key' => 'a!reference',
                'align' => 'left',
                'class' => 'fixed-width-md'
            )
        );

        if (Shop::isFeatureActive() && Shop::getContext() != Shop::CONTEXT_SHOP) {
            $this->fields_list = array_merge($this->fields_list, array(
                'shopname' => array(
                    'title' => $this->l('Domyślny sklep'),
                    'filter_key' => 'shop!name',
                    'class' => 'fixed-width-lg'
                )
            ));
        } else {
            $this->fields_list = array_merge($this->fields_list, array(
                'name_category' => array(
                    'title' => $this->l('Kategoria'),
                    'filter_key' => 'cl!name',
                    'class' => 'fixed-width-lg'
                )
            ));
        }

        if (Configuration::get('PS_STOCK_MANAGEMENT')) {
            $this->fields_list = array_merge($this->fields_list, array(
                'sav_quantity' => array(
                    'title' => $this->l('Ilość'),
                    'type' => 'int',
                    'align' => 'text-right',
                    'class' => 'fixed-width-sm',
                    'filter_key' => 'sav!quantity',
                    'badge_danger' => true
                )
            ));
        }

        $this->fields_list = array_merge($this->fields_list, array(
            'price_final' => array(
                'title' => $this->l('Cena ost.'),
                'type' => 'price',
                'align' => 'text-right',
                'class' => 'fixed-width-sm',
                'havingFilter' => true,
                'search' => false
            ),
            'active' => array(
                'title' => $this->l('Akt.'),
                'active' => 'status',
                'filter_key' => 'sa!active',
                'align' => 'text-center',
                'type' => 'bool',
                'class' => 'fixed-width-xs',
                'search' => false
            ),
            'allegro_status' => array(
                'title' => $this->l('Wyst.'),
                'icon' => array(
                    // ukrywamy ikone gdy produkt nie jest wystawiony
                    //'0' => array('class' => 'icon-remove', 'src' => 'disabled.gif', 'alt' => ''),
                    '1' => array('class' => 'icon-check', 'src' => 'enabled.gif', 'alt' => $this->l('Wystawiony')),
                    '2' => array('class' => 'icon-time', 'src' => 'time.gif', 'alt' => $this->l('Zaplanowany'))
                ),
                'align' => 'text-center',
                'class' => 'fixed-width-xs',
                'orderby' => false,
                'filter' => false,
                'search' => false
            )
        ));

        $this->assignXFilters();

        $this->tpl_folder = 'x_allegro_perform/';
        $this->bulk_actions['xAllegro'] = array('icon' => 'icon-gavel bulkPerformAuctions', 'text' => $this->l('Wystaw zaznaczone'));
    }

    public function initProcess()
    {
        if (Tools::getIsset('sync_closed')) {
            $count = (int)Tools::getValue('sync_closed');
            $this->confirmations[] = $this->l('Zakończono synchronizację statusów ofert.') . '<br>' .
                '<b>' .($count
                    ? $this->l('Zaktualizowane statusy') . ': ' . (int)Tools::getValue('sync_closed')
                    : $this->l('Wszystkie oferty są aktualne!')
                ) . '</b>';

        }

        parent::initProcess();
    }

    public function renderList()
    {
        $this->addRowAction('xAllegro');
        $this->addRowAction('xEdit');

        return parent::renderList();
    }

    public function getList($id_lang, $orderBy = null, $orderWay = null, $start = 0, $limit = null, $id_lang_shop = null)
    {
        $id_shop = Shop::isFeatureActive() && Shop::getContext() == Shop::CONTEXT_SHOP ? (int)$this->context->shop->id : 'a.id_shop_default';

        if (version_compare(_PS_VERSION_, '1.6.1.0', '<'))
        {
            $select_image = 'MAX(image_shop.`id_image`) AS id_image';
            $join_image = '
                LEFT JOIN `'._DB_PREFIX_.'image` i ON (i.`id_product` = a.`id_product`)
                LEFT JOIN `'._DB_PREFIX_.'image_shop` image_shop ON (image_shop.`id_image` = i.`id_image` AND image_shop.`cover` = 1 AND image_shop.id_shop = ' . $id_shop . ')';
        }
        else {
            $select_image = 'image_shop.`id_image` AS `id_image`';
            $join_image = '
                LEFT JOIN `'._DB_PREFIX_.'image_shop` image_shop ON (image_shop.`id_product` = a.`id_product` AND image_shop.`cover` = 1 AND image_shop.id_shop = ' . $id_shop . ')
                LEFT JOIN `'._DB_PREFIX_.'image` i ON (i.`id_image` = image_shop.`id_image`)';
        }

        $this->_select .= 'shop.`name` AS `shopname`, a.`id_shop_default`, ' . $select_image . ',
            cl.`name` AS `name_category`, 0 AS `price_final`, taxRule.`price_tmp`, sav.`quantity` AS `sav_quantity`,
            IF(sav.`quantity`<=0, 1, 0) AS `badge_danger`, 0 AS `allegro_status`';

        $this->_join .= ' JOIN `'._DB_PREFIX_.'product_shop` sa ON (a.`id_product` = sa.`id_product` AND sa.id_shop = ' . $id_shop . ')
            LEFT JOIN (
                SELECT sa.`id_product`, sa.`price` * IF(t.`rate`, ((100 + (t.`rate`))/100), 1) as price_tmp
                FROM `' . _DB_PREFIX_ . 'product_shop` sa 
                LEFT JOIN `' . _DB_PREFIX_ . 'tax_rule` tr 
                    ON (sa.`id_tax_rules_group` = tr.`id_tax_rules_group` 
                        AND tr.`id_country` = ' . (int)Configuration::get('PS_COUNTRY_DEFAULT') . '
                        AND tr.`id_state` = 0)
                LEFT JOIN `' . _DB_PREFIX_ . 'tax` t 
                    ON (t.`id_tax` = tr.`id_tax`)
            ) as taxRule
                ON (taxRule.`id_product` = sa.`id_product`)
            LEFT JOIN `'._DB_PREFIX_.'category_lang` cl ON (sa.`id_category_default` = cl.`id_category` AND b.`id_lang` = cl.`id_lang` AND cl.id_shop = ' . $id_shop . ')
            LEFT JOIN `'._DB_PREFIX_.'shop` shop ON (shop.id_shop = ' . $id_shop . ')' .
            $join_image .'
            LEFT JOIN `'._DB_PREFIX_.'stock_available` sav ON (sav.`id_product` = a.`id_product` AND sav.`id_product_attribute` = 0' . StockAvailable::addSqlShopRestriction(null, null, 'sav') . ')';

        if ($this->allegroCookie->{$this->getAllegroCookieFilter('Category')}) {
            $filteredCategories = json_decode($this->allegroCookie->{$this->getAllegroCookieFilter('Category')}, true);
            if (is_array($filteredCategories)) {
                $this->_select .= ', cp.`position`';
                $this->_join .= ' INNER JOIN `'._DB_PREFIX_.'category_product` cp ON (cp.`id_product` = a.`id_product` AND cp.`id_category` IN ('.implode(',', json_decode($this->allegroCookie->{$this->getAllegroCookieFilter('Category')}, true)).')) ';
            }
        }

        if ($this->allegroCookie->{$this->getAllegroCookieFilter('Manufacturer')}) {
            $this->_join .= ' INNER JOIN `'._DB_PREFIX_.'manufacturer` pm ON (a.`id_manufacturer` = pm.`id_manufacturer` AND pm.`active` = 1 AND pm.`id_manufacturer` IN(' . $this->allegroCookie->{$this->getAllegroCookieFilter('Manufacturer')} . '))';
        }

        if ($this->allegroCookie->{$this->getAllegroCookieFilter('Supplier')}) {
            $this->_join .= ' INNER JOIN `'._DB_PREFIX_.'supplier` ps ON (a.`id_supplier` = ps.`id_supplier` AND ps.`active` = 1 AND ps.`id_supplier` IN(' . $this->allegroCookie->{$this->getAllegroCookieFilter('Supplier')} . '))';
        }

        if ($this->allegroCookie->{$this->getAllegroCookieFilter('PriceFrom')} > 0) {
            $this->_where .= ' AND taxRule.`price_tmp` >= "' . pSQL((float)$this->allegroCookie->{$this->getAllegroCookieFilter('PriceFrom')}) . '"';
        }
        if ($this->allegroCookie->{$this->getAllegroCookieFilter('PriceTo')} > 0) {
            $this->_where .= ' AND taxRule.`price_tmp` <= "' . pSQL((float)$this->allegroCookie->{$this->getAllegroCookieFilter('PriceTo')}) . '"';
        }

        if ($this->allegroCookie->{$this->getAllegroCookieFilter('QtyFrom')} > 0) {
            $this->_where .= ' AND sav.`quantity` >= ' . (int)$this->allegroCookie->{$this->getAllegroCookieFilter('QtyFrom')};
        }
        if ($this->allegroCookie->{$this->getAllegroCookieFilter('QtyTo')} > 0) {
            $this->_where .= ' AND sav.`quantity` <= ' . (int)$this->allegroCookie->{$this->getAllegroCookieFilter('QtyTo')};
        }

        if ($this->allegroCookie->{$this->getAllegroCookieFilter('Active')}) {
            $this->_where .= ' AND sa.`active` = ' . ((int)$this->allegroCookie->{$this->getAllegroCookieFilter('Active')} - 1);
        }

        $showZ = (int)$this->allegroCookie->{$this->getAllegroCookieFilter('ShowZbiorcze')};
        $showS = (int)$this->allegroCookie->{$this->getAllegroCookieFilter('ShowSurowiec')};

        if ($showZ || $showS) {
            $parts = [];
            if ($showZ) {
                $parts[] = 'b.`name` LIKE "%zbiorcze%"';
            }
            if ($showS) {
                $parts[] = 'b.`name` LIKE "%surowiec%"';
            }
            $this->_where .= ' AND (' . implode(' OR ', $parts) . ')';
        } else {
            if ($this->allegroCookie->{$this->getAllegroCookieFilter('HideZbiorcze')}) {
                $this->_where .= ' AND b.`name` NOT LIKE "%zbiorcze%"';
            }
            if ($this->allegroCookie->{$this->getAllegroCookieFilter('HideSurowiec')}) {
                $this->_where .= ' AND b.`name` NOT LIKE "%surowiec%"';
            }
        }

        
        $hideAmag = (int)$this->allegroCookie->{$this->getAllegroCookieFilter('HideAmag')};
        $showAmag = (int)$this->allegroCookie->{$this->getAllegroCookieFilter('ShowAmag')};

        if ($showAmag) {
            // Pokaż tylko produkty z indeksem zaczynającym się od A_MAG
            $this->_where .= ' AND a.`reference` LIKE "A_MAG%"';
        } elseif ($hideAmag) {
            // Ukryj produkty z indeksem zaczynającym się od A_MAG
            $this->_where .= ' AND (a.`reference` IS NULL OR a.`reference` NOT LIKE "A_MAG%")';
        }

$this->_where .= ' AND (a.`reference` IS NULL OR a.`reference` NOT LIKE "x13allegro-empty-product")';

        if ($this->allegroCookie->{$this->getAllegroCookieFilter('Performed')})
        {
            $this->_select .= ', COUNT(xaa.`id_xallegro_auction`) AS allegro_status_count, COUNT(DISTINCT xaa.`id_product_attribute`) AS allegro_status_combinations_count, 
                (SELECT COUNT(pa.`id_product_attribute`)
                    FROM `'._DB_PREFIX_.'product_attribute` pa
                    WHERE a.`id_product` = pa.`id_product`
                ) AS combinations,';

            $this->_join .= 'LEFT JOIN `'._DB_PREFIX_.'xallegro_auction` xaa
                ON (a.`id_product` = xaa.`id_product`
                    AND xaa.id_shop = ' . $id_shop . '
                    AND (xaa.`closed` = 0 OR xaa.`start` = 1)
                    AND xaa.`id_xallegro_account` = ' . $this->allegroApi->getAccount()->id . ')';

            if ($this->allegroCookie->{$this->getAllegroCookieFilter('Performed')} == 1) {
                $this->_having .= 'allegro_status_count > 0';
            }
            else if ($this->allegroCookie->{$this->getAllegroCookieFilter('Performed')} == 2) {
                $this->_having .= 'allegro_status_count > 0 AND (combinations = 0 OR allegro_status_combinations_count = combinations)';
            }
            else if ($this->allegroCookie->{$this->getAllegroCookieFilter('Performed')} == 3) {
                $this->_having .= 'allegro_status_count = 0';
            }
            else if ($this->allegroCookie->{$this->getAllegroCookieFilter('Performed')} == 4) {
                $this->_having .= 'allegro_status_count = 0 OR (allegro_status_count > 0 AND allegro_status_combinations_count < combinations)';
            }
        }

        $this->_group = 'GROUP BY a.`id_product`';

        $orderByPriceFinal = (empty($orderBy) ? (Tools::getValue($this->table . 'Orderby') ? Tools::getValue($this->table . 'Orderby') : 'id_' . $this->table) : $orderBy);
        $orderWayPriceFinal = (empty($orderWay) ? (Tools::getValue($this->table . 'Orderway') ? Tools::getValue($this->table . 'Orderway') : 'ASC') : $orderWay);

        parent::getList($this->allegroApi->getAccount()->id_language, $orderBy, $orderWay, $start, $limit, $this->context->shop->id);

        $auctions_info = XAllegroAuction::getAuctionsStatusDetail($this->allegroApi->getAccount()->id, $this->context->shop->id);

        /* complete product data */
        $nb = is_array($this->_list) ? count($this->_list) : 0;
        if ($nb) {
            $context = $this->context->cloneContext();
            $context->shop = clone($context->shop);

            for ($i = 0; $i < $nb; $i++) {
                if (Context::getContext()->shop->getContext() != Shop::CONTEXT_SHOP) {
                    $context->shop = new Shop((int)$this->_list[$i]['id_shop_default']);
                }

                // convert price to final Currency
                $this->_list[$i]['id_currency'] = $this->allegroApi->getCurrency()->id;
                $this->_list[$i]['price_final'] =
                    XAllegroProduct::convertPrice(
                        XAllegroProduct::getProductStaticPrice(
                            $this->_list[$i]['id_product'],
                            0,
                            (new XAllegroConfigurationAccount($this->allegroApi->getAccount()->id))->get('AUCTION_PRICE_CUSTOMER_GROUP', true),
                            $this->context
                        ),
                        $this->context->currency,
                        $this->allegroApi->getCurrency()
                    );

                // get information about active offers
                if (isset($auctions_info[$this->_list[$i]['id_product']])) {
                    $this->_list[$i]['auction_info'] = array(
                        'qty' => $auctions_info[$this->_list[$i]['id_product']]['qty'] - $auctions_info[$this->_list[$i]['id_product']]['planned_qty'],
                        'auctions_nb' => $auctions_info[$this->_list[$i]['id_product']]['auctions_nb'] - $auctions_info[$this->_list[$i]['id_product']]['planned_nb'],
                        'planned_qty' => $auctions_info[$this->_list[$i]['id_product']]['planned_qty'],
                        'planned_nb' => $auctions_info[$this->_list[$i]['id_product']]['planned_nb'],
                        'combinations_nb' => $auctions_info[$this->_list[$i]['id_product']]['combinations_nb'],
                        'combinations_total' => $auctions_info[$this->_list[$i]['id_product']]['combinations_total']
                    );

                    $this->_list[$i]['class'] = 'exposed';
                }

                $this->_list[$i]['allegro_status'] = 0;

                if (isset($this->_list[$i]['auction_info'])) {
                    if ($this->_list[$i]['auction_info']['planned_nb'] > 0) {
                        $this->_list[$i]['allegro_status'] = 2;
                    }
                    else if ($this->_list[$i]['auction_info']['auctions_nb'] > 0) {
                        $this->_list[$i]['allegro_status'] = 1;
                    }
                }
            }

            if ($orderByPriceFinal == 'price_final') {
                if (strtolower($orderWayPriceFinal) == 'desc') {
                    uasort($this->_list, 'cmpPriceDesc');
                } else {
                    uasort($this->_list, 'cmpPriceAsc');
                }
            }
        }
    }

    private function assignXFilters()
    {
        if (Tools::getValue('reset_xFilter')) {
            unset(
                $this->allegroCookie->{$this->getAllegroCookieFilter('Category')},
                $this->allegroCookie->{$this->getAllegroCookieFilter('PriceFrom')},
                $this->allegroCookie->{$this->getAllegroCookieFilter('PriceTo')},
                $this->allegroCookie->{$this->getAllegroCookieFilter('QtyFrom')},
                $this->allegroCookie->{$this->getAllegroCookieFilter('QtyTo')},
                $this->allegroCookie->{$this->getAllegroCookieFilter('Manufacturer')},
                $this->allegroCookie->{$this->getAllegroCookieFilter('Supplier')},
                $this->allegroCookie->{$this->getAllegroCookieFilter('Performed')},
                $this->allegroCookie->{$this->getAllegroCookieFilter('Active')},
                $this->allegroCookie->{$this->getAllegroCookieFilter('HideZbiorcze')},
                $this->allegroCookie->{$this->getAllegroCookieFilter('HideSurowiec')},
                $this->allegroCookie->{$this->getAllegroCookieFilter('ShowZbiorcze')},
                $this->allegroCookie->{$this->getAllegroCookieFilter('ShowSurowiec')},
                $this->allegroCookie->{$this->getAllegroCookieFilter('HideAmag')},
                $this->allegroCookie->{$this->getAllegroCookieFilter('ShowAmag')}
            );
        }
        
else if (Tools::isSubmit('submit_xFilter'))
        {
            if (is_numeric(Tools::getValue('productFilter_cl!name')))
            {
                $category = new Category((int)Tools::getValue('productFilter_cl!name'));

                if (Validate::isLoadedObject($category) && $category->inShop($this->context->shop)) {
                    $_POST['productFilter_cl!name'] = $category->name[$this->allegroApi->getAccount()->id_language];
                }
            }
            else {
                if (Tools::getValue('xFilterCategory')) {
                    $xFilterCategory = Tools::getValue('xFilterCategory');
                } else if ($this->allegroCookie->{$this->getAllegroCookieFilter('Category')}) {
                    $xFilterCategory = json_decode($this->allegroCookie->{$this->getAllegroCookieFilter('Category')}, true);
                } else {
                    $xFilterCategory = false;
                }

                // sprawdzamy czy wybrane kategorie wystepuja w aktywnym sklepie
                if ($xFilterCategory) {
                    $xFilterCategory = $this->existsInShop($xFilterCategory, $this->context->shop->id);
                }
            }

            $this->allegroCookie->{$this->getAllegroCookieFilter('Category')} = (isset($xFilterCategory) && is_array($xFilterCategory) ? json_encode($xFilterCategory) : false);
            $this->allegroCookie->{$this->getAllegroCookieFilter('PriceFrom')} = (float)Tools::getValue('xFilterPriceFrom');
            $this->allegroCookie->{$this->getAllegroCookieFilter('PriceTo')} = (float)Tools::getValue('xFilterPriceTo');
            $this->allegroCookie->{$this->getAllegroCookieFilter('QtyFrom')} = (int)Tools::getValue('xFilterQtyFrom');
            $this->allegroCookie->{$this->getAllegroCookieFilter('QtyTo')} = (int)Tools::getValue('xFilterQtyTo');

            // multi-filters
            $this->allegroCookie->{$this->getAllegroCookieFilter('Manufacturer')} = trim(implode(',', Tools::getValue('xFilterManufacturer', [])), " ,");
            $this->allegroCookie->{$this->getAllegroCookieFilter('Supplier')} = trim(implode(',', Tools::getValue('xFilterSupplier', [])), " ,");

                        $this->allegroCookie->{$this->getAllegroCookieFilter('Active')}       = (int)Tools::getValue('xFilterActive');
            $this->allegroCookie->{$this->getAllegroCookieFilter('Performed')}    = (int)Tools::getValue('xFilterPerformed');
            $this->allegroCookie->{$this->getAllegroCookieFilter('HideZbiorcze')} = (int)Tools::getValue('xFilterHideZbiorcze');
            $this->allegroCookie->{$this->getAllegroCookieFilter('HideSurowiec')} = (int)Tools::getValue('xFilterHideSurowiec');
            $this->allegroCookie->{$this->getAllegroCookieFilter('ShowZbiorcze')} = (int)Tools::getValue('xFilterShowZbiorcze');
            $this->allegroCookie->{$this->getAllegroCookieFilter('ShowSurowiec')} = (int)Tools::getValue('xFilterShowSurowiec');
            $this->allegroCookie->{$this->getAllegroCookieFilter('HideAmag')}     = (int)Tools::getValue('xFilterHideAmag');
            $this->allegroCookie->{$this->getAllegroCookieFilter('ShowAmag')}     = (int)Tools::getValue('xFilterShowAmag');
        }

    }

    public function renderAllegroLink($id, $label, $url)
    {
        $allegro_status = (int) XAllegroAuction::getAuctionsStatus($id, null, $this->allegroApi->getAccount()->id, $this->context->shop->id);

        if (0 == $allegro_status) {
            $color = '';
            $icon = 'AdminXAllegroMainDefault.png';
            $txt = $label;
        } else {
            $color = 'color: #FF5A00;';
            $icon = 'AdminXAllegroMain.png';
            $txt = $this->l('Wystawiony').' ('.$allegro_status.')';
        }

        $tpl = $this->context->smarty->createTemplate(
            $this->module->getLocalPath().'views/templates/admin/'.$this->tpl_folder.'helpers/list/action_xallegro.tpl'
        );

        $tpl->assign([
            'href' => $url,
            'button_txt' => $txt,
            'title' => $this->l('Wystaw produkt na Allegro'),
            'id' => $id,
            'color' => $color,
            'icon' => $icon,
            'auction' => $allegro_status,
        ]);

        return $tpl->fetch();
    }

    public function displayXAllegroLink($token = null, $id, $name = null)
    { 
        $label = $this->l('Wystaw');
        $url = $this->context->link->getAdminLink('AdminXAllegroMain').'&id_product='.$id;

        return $this->renderAllegroLink($id, $label, $url);
    }

    public function displayXEditLink($token = null, $id, $name = null)
    {
        if (version_compare(_PS_VERSION_, '1.7.0.0', '>=')) {
            $href = $this->context->link->getAdminLink('AdminProducts', true, array('id_product' => $id));
        }
        else {
            $href = $this->context->link->getAdminLink('AdminProducts') . '&updateproduct&id_product=' . $id;
        }

        $tpl = $this->context->smarty->createTemplate($this->module->getLocalPath() . 'views/templates/admin/' . $this->tpl_folder . 'helpers/list/action_xedit.tpl');
        $tpl->assign(array(
            'href' => $href,
            'action' => $this->l('Edytuj'),
            'id' => $id
        ));

        return $tpl->fetch();
    }

    public function processBulkXAllegro()
    {
        if (is_array($this->boxes) && !empty($this->boxes)) {
            $this->redirect_after = $this->context->link->getAdminLink('AdminXAllegroMain')
                . '&id_product=' . implode(',', array_map('intval', $this->boxes));
        }
    }

    public function initContent($token = null)
    {
        if (Shop::isFeatureActive() && Shop::getContext() !== Shop::CONTEXT_SHOP) {
            $this->errors[] = $this->l('Wybierz konkretny kontekst sklepu aby wystawić nową ofertę');
            return;
        }

        if (!defined('X13_ALLEGRO_DISABLE_CATEGORY_TREE')) {
            if (!$this->allegroCookie->{$this->getAllegroCookieFilter('Category')}) {
                $selected_categories = [(int) Configuration::get('PS_ROOT_CATEGORY')];
            } else {
                $selected_categories = json_decode($this->allegroCookie->{$this->getAllegroCookieFilter('Category')}, true);
                if (!is_array($selected_categories)) {
                    $selected_categories = [(int) Configuration::get('PS_ROOT_CATEGORY')];
                }
            }

            $tree = new HelperTreeCategories('xFilterCategories', $this->l('Kategorie'));
            $category_tree = $tree->setInputName('xFilterCategory')
                ->setUseCheckBox(true)
                ->setRootCategory(Category::getRootCategory()->id)
                ->setSelectedCategories($selected_categories)
                ->render();
        }
        else {
            $category_tree = false;
        }

        $this->tpl_list_vars['category_tree'] = $category_tree;
        $this->tpl_list_vars['manufacturers'] = Manufacturer::getManufacturers();
        $this->tpl_list_vars['suppliers'] = Supplier::getSuppliers();
        $this->tpl_list_vars['xFilterPriceFrom'] = (float)$this->allegroCookie->{$this->getAllegroCookieFilter('PriceFrom')};
        $this->tpl_list_vars['xFilterPriceTo'] = (float)$this->allegroCookie->{$this->getAllegroCookieFilter('PriceTo')};
        $this->tpl_list_vars['xFilterQtyFrom'] = (int)$this->allegroCookie->{$this->getAllegroCookieFilter('QtyFrom')};
        $this->tpl_list_vars['xFilterQtyTo'] = (int)$this->allegroCookie->{$this->getAllegroCookieFilter('QtyTo')};

        // multi-filters
        $this->tpl_list_vars['xFilterManufacturer'] = explode(',', $this->allegroCookie->{$this->getAllegroCookieFilter('Manufacturer')});
        $this->tpl_list_vars['xFilterSupplier']     = explode(',', $this->allegroCookie->{$this->getAllegroCookieFilter('Supplier')});

        $this->tpl_list_vars['xFilterActive'] = (int)$this->allegroCookie->{$this->getAllegroCookieFilter('Active')};
        $this->tpl_list_vars['xFilterPerformed'] = (int)$this->allegroCookie->{$this->getAllegroCookieFilter('Performed')};
        $this->tpl_list_vars['xFilterHideZbiorcze'] = (int)$this->allegroCookie->{$this->getAllegroCookieFilter('HideZbiorcze')};
        $this->tpl_list_vars['xFilterHideSurowiec'] = (int)$this->allegroCookie->{$this->getAllegroCookieFilter('HideSurowiec')};
        $this->tpl_list_vars['xFilterShowZbiorcze'] = (int)$this->allegroCookie->{$this->getAllegroCookieFilter('ShowZbiorcze')};
        $this->tpl_list_vars['xFilterShowSurowiec'] = (int)$this->allegroCookie->{$this->getAllegroCookieFilter('ShowSurowiec')};
        $this->tpl_list_vars['xFilterHideAmag']     = (int)$this->allegroCookie->{$this->getAllegroCookieFilter('HideAmag')};
        $this->tpl_list_vars['xFilterShowAmag']     = (int)$this->allegroCookie->{$this->getAllegroCookieFilter('ShowAmag')};

        parent::initContent();
    }

    /**
     * Zwraca tylko te kategorie ktore przypisane sa do podanego id sklepu
     *
     * @param array $categories
     * @param int $id_shop
     * @return array
     * @throws PrestaShopDatabaseException
     */
    private function existsInShop(array $categories, $id_shop)
    {
        if (empty($categories)) {
            return array();
        }

        $result =  Db::getInstance()->executeS('
            SELECT `id_category`
            FROM `'._DB_PREFIX_.'category_shop`
            WHERE `id_category` IN (' . implode(',', $categories) . ')
                AND `id_shop` = ' . (int) $id_shop
        );

        $cats = array();
        if (!$result) {
            return $cats;
        }

        foreach ($result as $row) {
            $cats[] = $row['id_category'];
        }

        return $cats;
    }
}
