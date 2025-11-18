<?php

require_once (dirname(__FILE__) . '/../../x13import.php');

class AdminXImportPricesController extends XImportController
{
    private $wholesales = array();

    public function __construct()
    {
        $this->table = 'ximport_price';
        $this->identifier = 'id_ximport_price';
        $this->className = 'XImportPrice';

        parent::__construct();

        $this->tpl_folder = 'x_import_prices/';

        foreach (XImportWholesalers::getWholesalers() as $wholesale) {
            $this->wholesales[] = array(
                'name' => $wholesale['name']
            );
        }

        $wholesales = array();
        foreach ($this->wholesales as $wholesale) {
            $wholesales[$wholesale['name']] = $wholesale['name'];
        }

        $this->fields_options = array(
            'general' => array(
                'title' =>	$this->l('Ustawienia zakresów cenowych'),
                'image' => '../img/t/AdminPreferences.gif',
                'fields' =>	array(
                    'MARKUP_SUM' => array(
                        'title' => $this->l('Sumuj narzut z zakresów cenowych'),
                        'type' => 'bool',
                        'desc' => $this->l('Przy włączonej opcji sumuje narzut ze wszystkich pasujących zakresów cenowych.') . '<br>' .
                            $this->l('Wyłączenie tej opcji spowoduje wybranie narzutu z najwęższego pasującego zakresu cenowego.')
                    )
                ),
                'submit' => array(
                    'title' => $this->l('Zapisz')
                )
            )
        );

        $this->fields_list = array(
            'wholesaler' => array(
                'title' => $this->l('Hurtownia'),
                'type' => 'select',
                'list' => $wholesales,
                'filter_key' => 'wholesaler'
            ),
            'range_from' => array(
                'title' => $this->l('Cena od'),
                'class' => 'fixed-width-md'
            ),
            'range_to' => array(
                'title' => $this->l('Cena do'),
                'class' => 'fixed-width-md'
            ),
            'markup' => array(
                'title' => $this->l('Wartość'),
                'class' => 'fixed-width-md'
            ),
            'active' => array(
                'title' => $this->l('Aktywny'),
                'width' => 70,
                'align' => 'center',
                'active' => 'status',
                'type' => 'bool',
                'class' => 'fixed-width-sm'
            ),
            'override_category' => array(
                'title' => $this->l('Nadpisuje narzut kategorii'),
                'width' => 100,
                'align' => 'center',
                'type' => 'bool',
                'icon' => array(
                    '0' => array('class' => 'icon-remove', 'src' => 'disabled.gif', 'alt' => $this->l('Nie')),
                    '1' => array('class' => 'icon-check', 'src' => 'enabled.gif', 'alt' => $this->l('Tak'))
                ),
                'class' => 'fixed-width-sm'
            )
        );

        $this->bulk_actions = array(
            'delete' => array(
                'text' => $this->l('Usuń zaznaczone'),
                'confirm' => $this->l('Czy chcesz usunąć zaznaczone zakresy?'),
                'icon' => 'icon-trash'
            )
        );
    }

    public function viewAccess($disable = false)
    {
        if (empty($this->wholesales)) {
            $this->errors[] = $this->l('Brak dodanych hurtowni');
        }

        if (!parent::viewAccess($disable) || empty($this->wholesales)) {
            return false;
        }

        return true;
    }

    public function initPageHeaderToolbar()
    {
        if (empty($this->display)) {
            $this->page_header_toolbar_btn['new_price_markup'] = array(
                'href' => $this->context->link->getAdminLink('AdminXImportPrices') . '&add' . $this->table,
                'desc' => $this->l('Nowy narzut cenowy'),
                'icon' => 'process-icon-new'
            );
        }

        parent::initPageHeaderToolbar();
    }

    public function renderList()
    {
        $this->addRowAction('edit');
        $this->addRowAction('delete');

        return parent::renderList();
    }

    public function renderForm()
    {
        $this->fields_form = array(
            'legend' => array(
                'title' => $this->l('Zastosuj narzut dla wybranej hurtowni'),
            ),
            'input' => array(
                array(
                    'type' => 'select',
                    'label' => $this->l('Hurtownia'),
                    'name' => 'wholesaler',
                    'class' => 'fixed-width-xxl',
                    'required' => true,
                    'options' => array(
                        'query' => $this->wholesales,
                        'id' => 'name',
                        'name' => 'name'
                    )
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Cena od'),
                    'suffix' => $this->context->currency->sign . ' ' . $this->l('brutto'),
                    'name' => 'range_from',
                    'size' => 12,
                    'class' => 'fixed-width-md x-cast x-cast-float',
                    'required' => true,
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Cena do'),
                    'suffix' => $this->context->currency->sign . ' ' . $this->l('brutto'),
                    'name' => 'range_to',
                    'size' => 12,
                    'class' => 'fixed-width-md x-cast x-cast-float',
                    'required' => true,
                ),
                array(
                    'type' => 'select',
                    'label' => $this->l('Rodzaj narzutu'),
                    'name' => 'type',
                    'class' => 'fixed-width-xxl',
                    'required' => true,
                    'options' => array(
                        'query' => array(
                            array('id' => XImportPrice::MARKUP_VALUE, 'name' => $this->l('Kwota')),
                            array('id' => XImportPrice::MARKUP_PERCENT, 'name' => $this->l('Procent'))
                        ),
                        'id' => 'id',
                        'name' => 'name'
                    )
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Wartość'),
                    'desc' => $this->l('Wartość narzutu zostanie zastosowana od ceny brutto'),
                    'name' => 'markup',
                    'size' => 12,
                    'class' => 'fixed-width-md x-cast x-cast-float',
                    'required' => true,
                ),
                array(
                    'label' => $this->l('Aktywny'),
                    'type' => $this->bootstrap ? 'switch' : 'radio',
                    'name' => 'active',
                    'is_bool' => true,
                    'values' => array(
                        array('value' => 1, 'label' => $this->l('Tak')),
                        array('value' => 0, 'label' => $this->l('Nie'))
                    ),
                    'default_value' => 1
                ),
                array(
                    'type' => $this->bootstrap ? 'switch' : 'radio',
                    'label' => $this->l('Nadpisuje narzut kategorii'),
                    'desc' => $this->l('Wyłączenie tej opcji spowoduje zsumowanie narzutu hutrowni oraz kategorii'),
                    'name' => 'override_category',
                    'class' => 't',
                    'is_bool' => true,
                    'values' => array(
                        array(
                            'id' => 'override_category_on',
                            'value' => 1,
                            'label' => $this->l('Tak')
                        ),
                        array(
                            'id' => 'override_category_off',
                            'value' => 0,
                            'label' => $this->l('Nie')
                        )
                    )
                )
            ),
            'submit' => array(
                'title' => $this->l('Zapisz')
            )
        );

        return parent::renderForm();
    }

    public function getList($id_lang, $order_by = null, $order_way = null, $start = 0, $limit = null, $id_lang_shop = false)
    {
        parent::getList($id_lang, $order_by, $order_way, $start, $limit, $id_lang_shop);

        foreach ($this->_list as &$list)
        {
            $list['markup'] = $list['markup'] . ' ' . ($list['type'] == XImportPrice::MARKUP_VALUE ? $this->context->currency->sign : '%');
        }
    }

    public function getFieldsValue($obj)
    {
        $this->fields_value = parent::getFieldsValue($obj);

        if (!Validate::isLoadedObject($obj)) {
            $this->fields_value['range_from'] =
            $this->fields_value['range_to'] =
            $this->fields_value['markup'] = '0.00';
        }

        return $this->fields_value;
    }

    public function renderOptions()
    {
        unset($this->toolbar_btn);
        $this->display = 'options';
        $this->initToolbar();
        $helper = new HelperXImportOptions();
        $this->setHelperDisplay($helper);
        $helper->id = $this->id;
        $helper->tpl_vars = $this->tpl_option_vars;
        $options = $helper->generateOptions($this->fields_options);

        return $options;
    }

    protected function processUpdateOptions()
    {
        $this->beforeUpdateOptions();

        foreach ($this->fields_options as $category_data)
        {
            if (!isset($category_data['fields'])) {
                continue;
            }

            foreach ($category_data['fields'] as $key => $options) {
                XImportConfiguration::updateValue($key, Tools::getValue($key, ''));
            }
        }

        $this->confirmations[] = $this->_conf[6];
    }
}
