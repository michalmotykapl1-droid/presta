<?php
/**
 * 2007-2025 PrestaShop.
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
 *  @author    PrestaShop SA <contact@prestashop.com>
 *  @copyright 2007-2025 PrestaShop SA
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

include_once 'classes/tvcmssizechart_status.class.php';

class TvcmsSizeChart extends Module
{
    public function __construct()
    {
        $this->name = 'tvcmssizechart';
        $this->tab = 'front_office_features';
        $this->version = '4.0.0';
        $this->author = 'ThemeVolty';
        $this->need_instance = 0;

        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = $this->l('ThemeVolty - Size Charts');
        $this->description = $this->l('Display Charts and tables on Product page.');

        $this->ps_versions_compliancy = ['min' => '1.7', 'max' => _PS_VERSION_];
        $this->module_key = '';

        $this->confirmUninstall = $this->l('Warning: all the data saved in your database will be deleted.' .
            ' Are you sure you want uninstall this module?');
    }

    public function install()
    {
        $this->installTab();
        // $this->createDefaultData();

        return parent::install()
            && $this->registerHook('displayCustomtab')
            && $this->registerHook('displayBackOfficeHeader')
            && $this->registerHook('displayheader');
    }

    public function installTab()
    {
        $response = true;

        // First check for parent tab
        $parentTabID = Tab::getIdFromClassName('AdminThemeVolty');

        if ($parentTabID) {
            $parentTab = new Tab($parentTabID);
        } else {
            $parentTab = new Tab();
            $parentTab->active = 1;
            $parentTab->name = [];
            $parentTab->class_name = 'AdminThemeVolty';
            foreach (Language::getLanguages() as $lang) {
                $parentTab->name[$lang['id_lang']] = 'ThemeVolty Extension';
            }
            $parentTab->id_parent = 0;
            $parentTab->module = $this->name;
            $response &= $parentTab->add();
        }

        // Check for parent tab2
        $parentTab_2ID = Tab::getIdFromClassName('AdminThemeVoltyModules');
        if ($parentTab_2ID) {
            $parentTab_2 = new Tab($parentTab_2ID);
        } else {
            $parentTab_2 = new Tab();
            $parentTab_2->active = 1;
            $parentTab_2->name = [];
            $parentTab_2->class_name = 'AdminThemeVoltyModules';
            foreach (Language::getLanguages() as $lang) {
                $parentTab_2->name[$lang['id_lang']] = 'ThemeVolty Configure';
            }
            $parentTab_2->id_parent = $parentTab->id;
            $parentTab_2->module = $this->name;
            $response &= $parentTab_2->add();
        }
        // Created tab
        $tab = new Tab();
        $tab->active = 1;
        $tab->class_name = 'Admin' . $this->name;
        $tab->name = [];
        foreach (Language::getLanguages() as $lang) {
            $tab->name[$lang['id_lang']] = 'Size Chart [Size/Chart/Table]';
        }
        $tab->id_parent = $parentTab_2->id;
        $tab->module = $this->name;
        $response &= $tab->add();

        return $response;
    }

    public function createDefaultData()
    {
        Configuration::updateValue('TVCMSSIZECHART_TEXT_1', '<table><tbody><tr><td><strong>EU</strong></td>' .
            '<td>32</td><td>34</td><td>36</td><td>38</td><td>40</td><td>42</td><td>44</td><td>46</td></tr>' .
            '<tr><td><strong>US</strong></td><td>XX5</td><td>XS</td><td>S</td><td>M</td><td>L</td><td>XL' .
            '</td><td>XXL</td><td>XXL</td></tr><tr><td><strong>Arm Length</strong></td><td>61</td>' .
            '<td>61,5</td><td>62</td><td>62,5</td><td>63</td><td>63,5</td><td>64</td><td>64,5</td>' .
            '</tr><tr><td><strong>Bust Circumference</strong></td><td>80</td><td>84</td><td>88</td>' .
            '<td>92</td><td>96</td><td>101</td><td>106</td><td>111</td></tr><tr><td><strong>' .
            'Waist Girth</strong></td><td>61</td><td>65</td><td>69</td><td>73</td><td>77</td>' .
            '<td>82</td><td>87</td><td>92</td></tr><tr><td><strong>Hip Circumference</strong></td>' .
            '<td>87</td><td>91</td><td>95</td><td>99</td><td>103</td><td>108</td><td>113</td><td>' .
            '118</td></tr></tbody></table>');
        Configuration::updateValue('TVCMSSIZECHART_TEXT_1_NAME', '');
        Configuration::updateValue('TVCMSSIZECHART_TEXT_STATUS_1', '1');
        Configuration::updateValue('TVCMSSIZECHART_TEXT_2', '');
        Configuration::updateValue('TVCMSSIZECHART_TEXT_2_NAME', '');
        Configuration::updateValue('TVCMSSIZECHART_TEXT_STATUS_2', '0');
    }

    public function uninstall()
    {
        $this->uninstallTab();
        $this->deleteVariable();

        return parent::uninstall();
    }

    public function deleteVariable()
    {
        Configuration::deleteByName('TVCMSSIZECHART_TEXT_1');
        Configuration::deleteByName('TVCMSSIZECHART_TEXT_1_NAME');
        Configuration::deleteByName('TVCMSSIZECHART_TEXT_2_NAME');
        Configuration::deleteByName('TVCMSSIZECHART_TEXT_STATUS_1');
        Configuration::deleteByName('TVCMSSIZECHART_TEXT_2');
        Configuration::deleteByName('TVCMSSIZECHART_TEXT_STATUS_2');
    }

    public function uninstallTab()
    {
        $id_tab = Tab::getIdFromClassName('Admin' . $this->name);
        $tab = new Tab($id_tab);
        $tab->delete();

        return true;
    }

    public function getContent()
    {
        if (Tools::isSubmit('submitTvcmsSampleinstall') && '1' == Tools::getValue('tvinstalldata')) {
            $this->createDefaultData();
        }
        $message = $this->postProcess();
        $output = $message
                . $this->renderForm();

        return $output;
    }

    public function hookdisplayheader()
    {
        $this->context->controller->addJS($this->_path . 'views/js/front.js');
        $this->context->controller->addCSS($this->_path . 'views/css/front.css');
    }

    public function hookDisplayBackOfficeHeader()
    {
        if ($this->name == Tools::getValue('configure')) {
            $this->context->controller->addJS($this->_path . 'views/js/back.js');
            $this->context->controller->addCSS($this->_path . 'views/css/back.css');
        }
    }// hookDisplayBackOfficeHeader()

    public function postProcess()
    {
        $message = '';
        if (Tools::isSubmit('submittvcmsCustomCssJsMainTitleForm') && '0' == Tools::getValue('tvinstalldata')) {
            $tmp = Tools::getValue('TVCMSSIZECHART_TEXT_1');
            Configuration::updateValue('TVCMSSIZECHART_TEXT_1', $tmp, true);

            $tmp = Tools::getValue('TVCMSSIZECHART_TEXT_1_NAME');
            Configuration::updateValue('TVCMSSIZECHART_TEXT_1_NAME', $tmp);
            $tmp = Tools::getValue('TVCMSSIZECHART_TEXT_2_NAME');
            Configuration::updateValue('TVCMSSIZECHART_TEXT_2_NAME', $tmp);
            $tmp = Tools::getValue('TVCMSSIZECHART_TEXT_STATUS_1');
            Configuration::updateValue('TVCMSSIZECHART_TEXT_STATUS_1', $tmp);
            $tmp = Tools::getValue('TVCMSSIZECHART_TEXT_2');
            Configuration::updateValue('TVCMSSIZECHART_TEXT_2', $tmp, true);
            $tmp = Tools::getValue('TVCMSSIZECHART_TEXT_STATUS_2');
            Configuration::updateValue('TVCMSSIZECHART_TEXT_STATUS_2', $tmp);

            $this->clearCustomSmartyCache('tvcmssizechart_display_home.tpl');
            $message .= $this->displayConfirmation($this->l('Product Custom Text is Updated . '));
        }

        return $message;
    }

    public function clearCustomSmartyCache($cache_id)
    {
        if (Cache::isStored($cache_id)) {
            Cache::clean($cache_id);
        }
    }

    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
             . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = [
            'fields_value' => $this->getConfigFormValues(), // Add values for your inputs
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        ];

        $form = [];

        $tvcms_obj = new TvcmsSizeChartStatus();
        $show_fields = $tvcms_obj->fieldStatusInformation();
        if ($show_fields['main_status']) {
            $form[] = $this->tvcmsCustomCssJsMainTitleForm();
        }

        return $helper->generateForm($form);
    }

    protected function tvcmsCustomCssJsMainTitleForm()
    {
        $tvcms_obj = new TvcmsSizeChartStatus();
        $show_fields = $tvcms_obj->fieldStatusInformation();
        $input = [];

        $input[] = [
                'col' => 12,
                'type' => 'BtnInstallData',
                'name' => 'BtnInstallData',
                'label' => '',
            ];
        if ($show_fields['product_tab_name']) {
            $input[] = [
                    'col' => 7,
                    'type' => 'text',
                    'name' => 'TVCMSSIZECHART_TEXT_1_NAME',
                    'label' => $this->l('Custom Product Tab Name 1'),
                    'cols' => 40,
                    'rows' => 20,
                ];
        }
        if ($show_fields['product_tab']) {
            $input[] = [
                    'col' => 6,
                    'type' => 'textarea',
                    'name' => 'TVCMSSIZECHART_TEXT_1',
                    'label' => $this->l('Size Chart Content'),
                    'cols' => 40,
                    'rows' => 20,
                    'class' => 'rte',
                    'autoload_rte' => true,
                ];
        }

        if ($show_fields['product_tab_status']) {
            $input[] = [
                        'type' => 'switch',
                        'label' => $this->l('Size Chart Content Status'),
                        'name' => 'TVCMSSIZECHART_TEXT_STATUS_1',
                        'desc' => $this->l('Hide or show Icons . '),
                        'is_bool' => true,
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Show'),
                            ],
                            [
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Hide'),
                            ],
                        ],
                    ];
        }
        if ($show_fields['product_tab_name_2']) {
            $input[] = [
                    'col' => 7,
                    'type' => 'text',
                    'name' => 'TVCMSSIZECHART_TEXT_2_NAME',
                    'label' => $this->l('Custom Product Tab Name 2'),
                    'cols' => 40,
                    'rows' => 20,
                ];
        }

        if ($show_fields['product_tab_2']) {
            $input[] = [
                    'col' => 7,
                    'type' => 'textarea',
                    'name' => 'TVCMSSIZECHART_TEXT_2',
                    'label' => $this->l('Shipping Info Content'),
                    'rows' => 20,
                    'class' => 'rte',
                    'autoload_rte' => true,
                ];
        }

        if ($show_fields['product_tab_2_status']) {
            $input[] = [
                        'type' => 'switch',
                        'label' => $this->l('Shipping Info Content Status'),
                        'name' => 'TVCMSSIZECHART_TEXT_STATUS_2',
                        'desc' => $this->l('Hide or show icons . '),
                        'is_bool' => true,
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Show'),
                            ],
                            [
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Hide'),
                            ],
                        ],
                    ];
        }

        return [
            'form' => [
                'legend' => [
                'title' => $this->l('Size Chart/Tables'),
                'icon' => 'icon-support',
                ],
                'input' => $input,
                'submit' => [
                    'title' => $this->l('Save'),
                    'name' => 'submittvcmsCustomCssJsMainTitleForm',
                ],
            ],
        ];
    }

    protected function getConfigFormValues()
    {
        $fields = [];
        // $languages = Language::getLanguages();
        $fields['TVCMSSIZECHART_TEXT_1'] = Configuration::get('TVCMSSIZECHART_TEXT_1', true);
        $fields['TVCMSSIZECHART_TEXT_1_NAME'] = Configuration::get('TVCMSSIZECHART_TEXT_1_NAME');
        $fields['TVCMSSIZECHART_TEXT_STATUS_1'] = Configuration::get('TVCMSSIZECHART_TEXT_STATUS_1');
        $fields['TVCMSSIZECHART_TEXT_2'] = Configuration::get('TVCMSSIZECHART_TEXT_2', true);
        $fields['TVCMSSIZECHART_TEXT_2_NAME'] = Configuration::get('TVCMSSIZECHART_TEXT_2_NAME');
        $fields['TVCMSSIZECHART_TEXT_STATUS_2'] = Configuration::get('TVCMSSIZECHART_TEXT_STATUS_2');

        $path = _MODULE_DIR_ . $this->name . '/views/img/';
        $this->context->smarty->assign('path', $path);

        return $fields;
    }

    public function hookdisplayCustomtab()
    {
        if (!Cache::isStored('tvcmssizechart_display_home.tpl')) {
            $TVCMSSIZECHART_TEXT_1 = Configuration::get('TVCMSSIZECHART_TEXT_1', true);
            $this->context->smarty->assign('TVCMSSIZECHART_TEXT_1', $TVCMSSIZECHART_TEXT_1);
            $TVCMSSIZECHART_TEXT_1_NAME = Configuration::get('TVCMSSIZECHART_TEXT_1_NAME');
            $this->context->smarty->assign('TVCMSSIZECHART_TEXT_1_NAME', $TVCMSSIZECHART_TEXT_1_NAME);
            $TVCMSSIZECHART_TEXT_STATUS_1 = Configuration::get('TVCMSSIZECHART_TEXT_STATUS_1');
            $this->context->smarty->assign('TVCMSSIZECHART_TEXT_STATUS_1', $TVCMSSIZECHART_TEXT_STATUS_1);
            $TVCMSSIZECHART_TEXT_2 = Configuration::get('TVCMSSIZECHART_TEXT_2', true);
            $this->context->smarty->assign('TVCMSSIZECHART_TEXT_2', $TVCMSSIZECHART_TEXT_2);
            $TVCMSSIZECHART_TEXT_2_NAME = Configuration::get('TVCMSSIZECHART_TEXT_2_NAME');
            $this->context->smarty->assign('TVCMSSIZECHART_TEXT_2_NAME', $TVCMSSIZECHART_TEXT_2_NAME);
            $TVCMSSIZECHART_TEXT_STATUS_2 = Configuration::get('TVCMSSIZECHART_TEXT_STATUS_2');
            $this->context->smarty->assign('TVCMSSIZECHART_TEXT_STATUS_2', $TVCMSSIZECHART_TEXT_STATUS_2);
            $output = $this->display(__FILE__, 'views/templates/front/display_home.tpl');
            Cache::store('tvcmssizechart_display_home.tpl', $output);
        }

        return Cache::retrieve('tvcmssizechart_display_home.tpl');
    }
}
