<?php

require_once (dirname(__FILE__) . '/../../x13import.php');

class AdminXImportExcludesController extends XImportController
{
    public function __construct()
    {
        $this->table = 'ximport_product';
        $this->identifier = 'id_ximport_product';
        $this->className = 'XImportProduct';
        $this->list_no_link = true;

        parent::__construct();

        $this->fields_options = array(
            'general' => array(
                'title' =>	$this->l('Wykluczenia import produktów'),
                'image' => '../img/t/AdminPreferences.gif',
                'fields' =>	array(
                    'EXCLUDES_BY_REFERENCE' => array(
                        'title' => $this->l('Wyklucz po kodzie referencyjnym'),
                        'desc' => $this->l('Kody referencyjne podawaj w nowej linii'),
                        'type' => 'textarea',
                        'cols' => 10,
                        'rows' => 8,
                        'auto_value' => false,
                        'value' => $this->formatExcludesString('EXCLUDES_BY_REFERENCE')
                    ),
                    'EXCLUDES_BY_EAN' => array(
                        'title' => $this->l('Wyklucz po kodzie EAN'),
                        'desc' => $this->l('Kody EAN podawaj w nowej linii'),
                        'type' => 'textarea',
                        'cols' => 10,
                        'rows' => 8,
                        'auto_value' => false,
                        'value' => $this->formatExcludesString('EXCLUDES_BY_EAN')
                    )
                ),
                'submit' => array(
                    'title' => $this->l('Zapisz')
                )
            )
        );

        $this->fields_list = array(
            'id_product' => array(
                'title' => $this->l('ID'),
                'align' => 'center',
                'class' => 'fixed-width-xs',
                'width' => 50,
                'filter_key' => 'a!id_product',
                'orderby' => true
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
                'title' => $this->l('Nazwa produktu'),
                'filter_key' => 'pl!name',
                'orderby' => true
            ),
            'reference' => array(
                'title' => $this->l('Nr ref.'),
                'class' => 'fixed-width-md',
                'filter_key' => 'p!reference',
                'orderby' => true
            ),
            'category_name' => array(
                'title' => $this->l('Kategoria'),
                'class' => 'fixed-width-lg',
                'filter_key' => 'cl!name',
                'orderby' => true
            ),
            'supplier_name' => array(
                'title' => $this->l('Dostawca'),
                'class' => 'fixed-width-lg',
                'filter_key' => 's!name',
                'orderby' => true
            ),
            'price_netto' => array(
                'title' => $this->l('Cena netto'),
                'class' => 'fixed-width-md',
                'search' => false,
                'orderby' => false
            ),
            'quantity' => array(
                'title' => $this->l('Ilość'),
                'type' => 'int',
                'class' => 'fixed-width-sm',
                'align' => 'center',
                'width' => 80,
                'filter_key' => 'sav!quantity',
                'search' => true,
                'orderby' => true
            ),
            /*'active_shop' => array(
                'title' => $this->l('Akt.'),
                'type' => 'bool',
                'class' => 'fixed-width-xs',
                'align' => 'center',
                'filter_key' => 'p!active',
                'icon' => array(
                    '0' => array('class' => 'icon-remove', 'src' => 'disabled.gif', 'alt' => $this->l('Wyłączony w sklepie')),
                    '1' => array('class' => 'icon-check', 'src' => 'enabled.gif', 'alt' => $this->l('Włączony w sklepie'))
                ),
                'search'  => true,
                'orderby' => true
            ),*/
            'exclude' => array(
                'title' => $this->l('Wyklucz aktualizację'),
                'type' => 'bool',
                'active' => 'exclude',
                'class' => 'fixed-width-sm center word-break x-exclude',
                'align' => 'center',
                'width' => 80,
                'search'  => true,
                'orderby' => false
            ),
            'exclude_price' => array(
                'title' => $this->l('Wyklucz aktualizację ceny'),
                'type' => 'bool',
                'active' => 'exclude_price',
                'class' => 'fixed-width-md center word-break x-exclude-price',
                'align' => 'center',
                'width' => 100,
                'search'  => true,
                'orderby' => false
            ),
            'exclude_quantity' => array(
                'title' => $this->l('Wyklucz aktualizację ilości'),
                'type' => 'bool',
                'active' => 'exclude_quantity',
                'class' => 'fixed-width-md center word-break x-exclude-quantity',
                'align' => 'center',
                'width' => 100,
                'search'  => true,
                'orderby' => false
            )
        );

        $this->tpl_folder = 'x_import_excludes/';
    }

    public function initToolbar()
    {
        parent::initToolbar();

        unset($this->toolbar_btn['new']);

        // hack toolbar
        $this->toolbar_btn['back'] = array(
            'href' => '',
            'desc' => ''
        );
    }

    public function initProcess()
    {
        if (Tools::getIsset('submitBulkenableExcludeProductsximport_product')
            || Tools::getIsset('submitBulkdisableExcludeProductsximport_product')
            || Tools::getIsset('submitBulkenableExcludeProductsPricesximport_product')
            || Tools::getIsset('submitBulkdisableExcludeProductsPricesximport_product')
            || Tools::getIsset('submitBulkenableExcludeProductsQuantitiesximport_product')
            || Tools::getIsset('submitBulkdisableExcludeProductsQuantitiesximport_product')
        ) {
            if (!Tools::getIsset('ximport_productBox')) {
                $this->errors[] = $this->l('Nie wybrano żadnego produktu.');
            }
            else {
                if (Tools::getIsset('submitBulkenableExcludeProductsximport_product')
                    || Tools::getIsset('submitBulkdisableExcludeProductsximport_product')
                ) {
                    $method = 'setExclude';
                    $type = (Tools::getIsset('submitBulkenableExcludeProductsximport_product') ? XImportProduct::EXCLUDE_ENABLE : XImportProduct::EXCLUDE_DISABLE);
                }
                else if (Tools::getIsset('submitBulkenableExcludeProductsPricesximport_product')
                    || Tools::getIsset('submitBulkdisableExcludeProductsPricesximport_product')
                ) {
                    $method = 'setExcludePrice';
                    $type = (Tools::getIsset('submitBulkenableExcludeProductsPricesximport_product') ? XImportProduct::EXCLUDE_ENABLE : XImportProduct::EXCLUDE_DISABLE);
                }
                else if (Tools::getIsset('submitBulkenableExcludeProductsQuantitiesximport_product')
                    || Tools::getIsset('submitBulkdisableExcludeProductsQuantitiesximport_product')
                ) {
                    $method = 'setExcludeQuantity';
                    $type = (Tools::getIsset('submitBulkenableExcludeProductsQuantitiesximport_product') ? XImportProduct::EXCLUDE_ENABLE : XImportProduct::EXCLUDE_DISABLE);
                }

                if (isset($method) && isset($type)) {
                    foreach (Tools::getValue('ximport_productBox') as $ids_box) {
                        XImportProduct::$method((int)$ids_box, $type);
                    }

                    // redirect to avoid conflicts
                    Tools::redirectAdmin($this->context->link->getAdminLink('AdminXImportExcludes') . '&conf=4');
                }
            }
        }

        parent::initProcess();
    }

    public function renderList()
    {
        $this->addRowAction('xSpan');

        $this->bulk_actions = array(
            'enableExcludeProducts' => array(
                'text' => $this->l('Wł. wykluczenie aktualizacji')
            ),
            'enableExcludeProductsPrices' => array(
                'text' => $this->l('Wł. wykluczenie aktualizacji ceny')
            ),
            'enableExcludeProductsQuantities' => array(
                'text' => $this->l('Wł. wykluczenie aktualizacji ilości')
            ),
            'divider1' => array(
                'text' => 'divider'
            ),
            'disableExcludeProducts' => array(
                'text' => $this->l('Wył. wykluczenie aktualizacji')
            ),
            'disableExcludeProductsPrices' => array(
                'text' => $this->l('Wył. wykluczenie aktualizacji ceny')
            ),
            'disableExcludeProductsQuantities' => array(
                'text' => $this->l('Wył. wykluczenie aktualizacji ilości')
            )
        );

        return parent::renderList();
    }

    public function getList($id_lang, $order_by = null, $order_way = null, $start = 0, $limit = null, $id_lang_shop = false)
    {
        $id_shop = (Shop::isFeatureActive() ? (int)$this->context->shop->id : 'p.id_shop_default');
        $id_lang = $this->context->language->id;
        $id_lang_shop = $this->context->language->id;

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

        $this->_select = 'pl.`name`, p.`reference`, cl.`name` as `category_name`, sav.`quantity`, p.`active` as `active_shop`, 
            s.`name` as `supplier_name`, ' . $select_image;

        $this->_join .= '
            JOIN `'._DB_PREFIX_.'product` p ON (a.`id_product` = p.`id_product`)
            JOIN `'._DB_PREFIX_.'product_shop` ps ON (a.`id_product` = ps.`id_product`)
            LEFT JOIN `'._DB_PREFIX_.'product_lang` pl ON (a.`id_product` = pl.`id_product` AND pl.`id_lang` = ' . (int)$id_lang . ')
            LEFT JOIN `'._DB_PREFIX_.'category_lang` cl ON (p.`id_category_default` = cl.`id_category` AND pl.`id_lang` = cl.`id_lang`)
            LEFT JOIN `'._DB_PREFIX_.'supplier` s ON (p.`id_supplier` = s.`id_supplier`)
            LEFT JOIN `'._DB_PREFIX_.'stock_available` sav ON (sav.`id_product` = a.`id_product` AND sav.`id_product_attribute` = 0' . StockAvailable::addSqlShopRestriction(null, null, 'sav') . ')'.
            $join_image;

        $this->_group .= 'GROUP BY p.`id_product`';

        parent::getList($id_lang, $order_by, $order_way, $start, $limit, $id_lang_shop);

        foreach ($this->_list as &$product)
        {
            // override box & input ID
            $product['id'] = $product['id_ximport_product'] = $product['id_product'];
            $product['price_netto'] = Tools::displayPrice($this->formatPrice((int)$product['id_product'], (int)$product['id_product_attribute'], false, false));
        }
    }

    protected function processUpdateOptions()
    {
        $this->beforeUpdateOptions();

        foreach (array_keys($this->fields_options['general']['fields']) as $field) {
            $value = explode(';', str_replace(["\n", "\r", "\n\r", "\r\n", ","], ';', Tools::getValue($field, '')));
            $value = array_values(array_filter(array_map('trim', $value)));

            XImportConfiguration::updateValue($field, json_encode($value));
        }

        Tools::redirectAdmin($this->context->link->getAdminLink('AdminXImportExcludes') . '&conf=6');
    }

    public function displayXSpanLink($token = null, $id, $name = null)
    {
        $tpl = $this->context->smarty->createTemplate($this->module->getLocalPath() . 'views/templates/admin/' . $this->tpl_folder . 'helpers/list/action_xspan.tpl');
        return $tpl->fetch();
    }

    public function ajaxProcessExcludeProducts()
    {
        XImportProduct::setExclude((int)Tools::getValue('id'));

        die(json_encode(array(
            'confirmations' => 'Zmiany zostały pomyślnie zapisane.'
        )));
    }

    public function ajaxProcessExcludeProductsPrices()
    {
        XImportProduct::setExcludePrice((int)Tools::getValue('id'));

        die(json_encode(array(
            'confirmations' => 'Zmiany zostały pomyślnie zapisane.'
        )));
    }

    public function ajaxProcessExcludeProductsQuantities()
    {
        XImportProduct::setExcludeQuantity((int)Tools::getValue('id'));

        die(json_encode(array(
            'confirmations' => 'Zmiany zostały pomyślnie zapisane.'
        )));
    }

    /**
     * @param int $id_product
     * @param int $id_product_attribute
     * @param bool $use_tax
     * @param bool $use_reduction
     * @return float
     */
    private function formatPrice($id_product, $id_product_attribute, $use_tax, $use_reduction = true)
    {
        $specific_price_output = null;

        return Product::getPriceStatic(
            $id_product,
            $use_tax,
            $id_product_attribute,
            2,                      // decimals
            null,                   // divisor
            false,                  // only reduction
            $use_reduction,
            1,                      // quantity
            false,                  // force associated tax - deprecated
            null,                   // customer id
            null,                   // cart id
            null,                   // address id
            $specific_price_output,
            true,                   // eco tax
            $use_reduction
        );
    }

    /**
     * @param string $field
     * @return string
     */
    private function formatExcludesString($field)
    {
        if ($value = XImportConfiguration::get($field)) {
            $value = json_decode($value, true);

            if (is_array($value)) {
                return implode("\r", $value);
            }
        }

        return  '';
    }
}
