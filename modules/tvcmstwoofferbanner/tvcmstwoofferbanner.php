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

include_once 'classes/tvcmstwoofferbanner_image_upload.class.php';

class TvcmsTwoOfferBanner extends Module
{
    protected $config_form = false;

    public function __construct()
    {
        $this->name = 'tvcmstwoofferbanner';
        $this->tab = 'front_office_features';
        $this->version = '4.0.0';
        $this->author = 'ThemeVolty';
        $this->need_instance = 0;

        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('ThemeVolty - Two Offer Banner');
        $this->description = $this->l('This is Show Two Offer Banner in Front Side');

        $this->ps_versions_compliancy = ['min' => '1.7', 'max' => _PS_VERSION_];
        $this->module_key = '';

        $this->confirmUninstall = $this->l('Warning: all the data saved in your database will be deleted . ' .
            ' Are you sure you want uninstall this module?');
    }

    public function install()
    {
        // $this->createDefaultData();

        $this->installTab();

        return parent::install()
            && $this->registerHook('header')
            && $this->registerHook('backOfficeHeader')
            && $this->registerHook('displayWrapperTop')
            && $this->registerHook('displayContentWrapperTop')
            && $this->registerHook('displayHome');
    }

    public function createDefaultData()
    {
        $result = [];
        $languages = Language::getLanguages();

        foreach ($languages as $lang) {
            $result['TVCMSTWOOFFERBANNER_CAPTION'][$lang['id_lang']] = '<h6>motoz<sup>3<sup></h6><h4>World\'s First '
                 . '5G-upgradable</h4><div class="tvtwoofferbaner-btn"><a href=\'#\'>Shop Now</a></div>';

            $result['TVCMSTWOOFFERBANNER_CAPTION_2'][$lang['id_lang']] = '<h4>AirPods</h4><h6>Wireless. Effortless.'
                 . ' Magical.</h6>';
        }

        Configuration::updateValue('TVCMSTWOOFFERBANNER_IMAGE_NAME', 'demo_img_1.jpg');
        $ImageSizePath = _MODULE_DIR_ . $this->name . '/views/img/';
        $imagedata = getimagesize(_PS_BASE_URL_ . $ImageSizePath . 'demo_img_1.jpg');
        $width_1 = $imagedata[0];
        $height_1 = $imagedata[1];
        Configuration::updateValue('TVCMSTWOOFFERBANNER_IMAGE_WIDTH_1', $width_1);
        Configuration::updateValue('TVCMSTWOOFFERBANNER_IMAGE_HEIGHT_1', $height_1);
        Configuration::updateValue('TVCMSTWOOFFERBANNER_CAPTION', $result['TVCMSTWOOFFERBANNER_CAPTION'], true);

        // Default option is :- "left", "center", "right", "none".
        Configuration::updateValue('TVCMSTWOOFFERBANNER_CAPTION_SIDE', 'none');
        Configuration::updateValue('TVCMSTWOOFFERBANNER_LINK', '#');

        Configuration::updateValue('TVCMSTWOOFFERBANNER_IMAGE_NAME_2', 'demo_img_2.jpg');
        $ImageSizePath = _MODULE_DIR_ . $this->name . '/views/img/';
        $imagedata = getimagesize(_PS_BASE_URL_ . $ImageSizePath . 'demo_img_2.jpg');
        $width_2 = $imagedata[0];
        $height_2 = $imagedata[1];
        Configuration::updateValue('TVCMSTWOOFFERBANNER_IMAGE_WIDTH_2', $width_2);
        Configuration::updateValue('TVCMSTWOOFFERBANNER_IMAGE_HEIGHT_2', $height_2);
        Configuration::updateValue('TVCMSTWOOFFERBANNER_CAPTION_2', $result['TVCMSTWOOFFERBANNER_CAPTION_2'], true);

        // Default option is :- "left", "center", "right", "none".
        Configuration::updateValue('TVCMSTWOOFFERBANNER_CAPTION_SIDE_2', 'none');
        Configuration::updateValue('TVCMSTWOOFFERBANNER_LINK_2', '#');
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
            $tab->name[$lang['id_lang']] = 'Two Offer Banner';
        }
        $tab->id_parent = $parentTab_2->id;
        $tab->module = $this->name;
        $response &= $tab->add();

        return $response;
    }

    public function uninstall()
    {
        $this->deleteVariable();
        $this->uninstallTab();

        return parent::uninstall();
    }

    public function deleteVariable()
    {
        Configuration::deleteByName('TVCMSTWOOFFERBANNER_IMAGE_NAME');
        Configuration::deleteByName('TVCMSTWOOFFERBANNER_IMAGE_WIDTH_1');
        Configuration::deleteByName('TVCMSTWOOFFERBANNER_IMAGE_HEIGHT_1');
        Configuration::deleteByName('TVCMSTWOOFFERBANNER_CAPTION');
        Configuration::deleteByName('TVCMSTWOOFFERBANNER_CAPTION_SIDE');
        Configuration::deleteByName('TVCMSTWOOFFERBANNER_LINK');

        Configuration::deleteByName('TVCMSTWOOFFERBANNER_IMAGE_NAME_2');
        Configuration::deleteByName('TVCMSTWOOFFERBANNER_IMAGE_WIDTH_2');
        Configuration::deleteByName('TVCMSTWOOFFERBANNER_IMAGE_HEIGHT_2');
        Configuration::deleteByName('TVCMSTWOOFFERBANNER_CAPTION_2');
        Configuration::deleteByName('TVCMSTWOOFFERBANNER_CAPTION_SIDE_2');
        Configuration::deleteByName('TVCMSTWOOFFERBANNER_LINK_2');
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
        $messages = '';
        $tmp = [];
        $result = [];
        if (Tools::isSubmit('submitTvcmsSampleinstall') && '1' == Tools::getValue('tvinstalldata')) {
            $this->createDefaultData();
        }
        if (((bool) Tools::isSubmit('submittvcmstwoofferbanner')) == true && '0' == Tools::getValue('tvinstalldata')) {
            $obj_image = new TvcmsTwoOfferBannerImageUpload();
            $languages = Language::getLanguages(false);
            if (!empty($_FILES['TVCMSTWOOFFERBANNER_IMAGE_NAME']['name'])) {
                $old_img_path = Configuration::get('TVCMSTWOOFFERBANNER_IMAGE_NAME');
                $tmp = $_FILES['TVCMSTWOOFFERBANNER_IMAGE_NAME'];
                $ans = $obj_image->imageUploading($tmp, $old_img_path);
                if ($ans['success']) {
                    Configuration::updateValue('TVCMSTWOOFFERBANNER_IMAGE_NAME', $ans['name']);
                    Configuration::updateValue('TVCMSTWOOFFERBANNER_IMAGE_WIDTH_1', $ans['width']);
                    Configuration::updateValue('TVCMSTWOOFFERBANNER_IMAGE_HEIGHT_1', $ans['height']);
                } else {
                    $messages .= $result['error'];
                }
            }

            foreach ($languages as $lang) {
                $tmp = Tools::getValue('TVCMSTWOOFFERBANNER_CAPTION_' . $lang['id_lang']);
                $result['TVCMSTWOOFFERBANNER_CAPTION'][$lang['id_lang']] = $tmp;
            }

            $tmp = $result['TVCMSTWOOFFERBANNER_CAPTION'];
            Configuration::updateValue('TVCMSTWOOFFERBANNER_CAPTION', $tmp, true);

            $tmp = Tools::getValue('TVCMSTWOOFFERBANNER_CAPTION_SIDE');
            Configuration::updateValue('TVCMSTWOOFFERBANNER_CAPTION_SIDE', $tmp);

            $tmp = Tools::getValue('TVCMSTWOOFFERBANNER_LINK');
            Configuration::updateValue('TVCMSTWOOFFERBANNER_LINK', $tmp);

            if (!empty($_FILES['TVCMSTWOOFFERBANNER_IMAGE_NAME_2']['name'])) {
                $old_img_path = Configuration::get('TVCMSTWOOFFERBANNER_IMAGE_NAME_2');
                $tmp = $_FILES['TVCMSTWOOFFERBANNER_IMAGE_NAME_2'];
                $ans = $obj_image->imageUploading($tmp, $old_img_path);
                if ($ans['success']) {
                    Configuration::updateValue('TVCMSTWOOFFERBANNER_IMAGE_NAME_2', $ans['name']);
                    Configuration::updateValue('TVCMSTWOOFFERBANNER_IMAGE_WIDTH_2', $ans['width']);
                    Configuration::updateValue('TVCMSTWOOFFERBANNER_IMAGE_HEIGHT_2', $ans['height']);
                } else {
                    $messages .= $result['error'];
                }
            }

            foreach ($languages as $lang) {
                $tmp = Tools::getValue('TVCMSTWOOFFERBANNER_CAPTION_2_' . $lang['id_lang']);
                $result['TVCMSTWOOFFERBANNER_CAPTION_2'][$lang['id_lang']] = $tmp;
            }

            $tmp = $result['TVCMSTWOOFFERBANNER_CAPTION_2'];
            Configuration::updateValue('TVCMSTWOOFFERBANNER_CAPTION_2', $tmp, true);

            $tmp = Tools::getValue('TVCMSTWOOFFERBANNER_CAPTION_SIDE_2');
            Configuration::updateValue('TVCMSTWOOFFERBANNER_CAPTION_SIDE_2', $tmp);

            $tmp = Tools::getValue('TVCMSTWOOFFERBANNER_LINK_2');
            Configuration::updateValue('TVCMSTWOOFFERBANNER_LINK_2', $tmp);

            $this->clearCustomSmartyCache('tvcmstwoofferbanner_display_home.tpl');

            $messages .= $this->displayConfirmation($this->l('Offer Banner Information is Updated'));
        }

        $output = $messages . $this->renderForm();

        return $output;
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
        $helper->submit_action = 'submittvcmstwoofferbanner';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
             . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = [
            'fields_value' => $this->getConfigFormValues(), // Add values for your inputs
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        ];

        return $helper->generateForm([$this->getConfigForm()]);
    }

    protected function getConfigForm()
    {
        return [
            'form' => [
                'legend' => [
                'title' => $this->l('Two Offer Banner Configuration'),
                'icon' => 'icon-cogs',
                ],
                'input' => [
                    [
                        'col' => 12,
                        'type' => 'BtnInstallData',
                        'name' => 'BtnInstallData',
                        'label' => '',
                    ],
                    [
                        'col' => 6,
                        'name' => 'TVCMSTWOOFFERBANNER_IMAGE_NAME',
                        'type' => 'file_upload',
                        'label' => $this->l('Image'),
                    ],
                    [
                        'col' => 7,
                        'name' => 'TVCMSTWOOFFERBANNER_CAPTION',
                        'type' => 'textarea',
                        'lang' => true,
                        'label' => $this->l('Image Caption'),
                        'desc' => $this->l('Enter image caption'),
                        'cols' => 40,
                        'rows' => 10,
                        'class' => 'rte',
                        'autoload_rte' => true,
                    ],
                    [
                        'type' => 'select',
                        'label' => $this->l('Display Banner Side'),
                        'name' => 'TVCMSTWOOFFERBANNER_CAPTION_SIDE',
                        'desc' => $this->l('Select where you show text'),
                        'options' => [
                            'query' => [
                                [
                                    'id_option' => 'left',
                                    'name' => 'Left',
                                ],
                                [
                                    'id_option' => 'top-left',
                                    'name' => 'Top Left',
                                ],
                                [
                                    'id_option' => 'bottom-left',
                                    'name' => 'Top Bottom',
                                ],
                                [
                                    'id_option' => 'center',
                                    'name' => 'Center',
                                ],
                                [
                                    'id_option' => 'top-center',
                                    'name' => 'Top Center',
                                ],
                                [
                                    'id_option' => 'bottom-center',
                                    'name' => 'Bottom Center',
                                ],
                                [
                                    'id_option' => 'right',
                                    'name' => 'Right',
                                ],
                                [
                                    'id_option' => 'top-right',
                                    'name' => 'Top Right',
                                ],
                                [
                                    'id_option' => 'bottom-right',
                                    'name' => 'Bottom Right',
                                ],
                                [
                                    'id_option' => 'none',
                                    'name' => 'None',
                                ],
                            ],
                            'id' => 'id_option',
                            'name' => 'name',
                        ],
                    ],
                    [
                        'col' => 6,
                        'name' => 'TVCMSTWOOFFERBANNER_LINK',
                        'type' => 'text',
                        'label' => $this->l('Link'),
                        'desc' => $this->l('Enter image link'),
                    ],
                    [
                        'col' => 6,
                        'name' => 'TVCMSTWOOFFERBANNER_IMAGE_NAME_2',
                        'type' => 'file_upload_2',
                        'label' => $this->l('Image 2'),
                    ],
                    [
                        'col' => 7,
                        'name' => 'TVCMSTWOOFFERBANNER_CAPTION_2',
                        'type' => 'textarea',
                        'lang' => true,
                        'label' => $this->l('Image Caption 2'),
                        'desc' => $this->l('Enter image caption 2'),
                        'cols' => 40,
                        'rows' => 10,
                        'class' => 'rte',
                        'autoload_rte' => true,
                    ],
                    [
                        'type' => 'select',
                        'label' => $this->l('Display Banner Side 2'),
                        'name' => 'TVCMSTWOOFFERBANNER_CAPTION_SIDE_2',
                        'desc' => $this->l('Select where you show text 2'),
                        'options' => [
                            'query' => [
                                [
                                    'id_option' => 'left',
                                    'name' => 'Left',
                                ],
                                [
                                    'id_option' => 'top-left',
                                    'name' => 'Top Left',
                                ],
                                [
                                    'id_option' => 'bottom-left',
                                    'name' => 'Top Bottom',
                                ],
                                [
                                    'id_option' => 'center',
                                    'name' => 'Center',
                                ],
                                [
                                    'id_option' => 'top-center',
                                    'name' => 'Top Center',
                                ],
                                [
                                    'id_option' => 'bottom-center',
                                    'name' => 'Bottom Center',
                                ],
                                [
                                    'id_option' => 'right',
                                    'name' => 'Right',
                                ],
                                [
                                    'id_option' => 'top-right',
                                    'name' => 'Top Right',
                                ],
                                [
                                    'id_option' => 'bottom-right',
                                    'name' => 'Bottom Right',
                                ],
                                [
                                    'id_option' => 'none',
                                    'name' => 'None',
                                ],
                            ],
                            'id' => 'id_option',
                            'name' => 'name',
                        ],
                    ],
                    [
                        'col' => 6,
                        'name' => 'TVCMSTWOOFFERBANNER_LINK_2',
                        'type' => 'text',
                        'label' => $this->l('Link 2'),
                        'desc' => $this->l('Enter image link 2'),
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Save'),
                ],
            ],
        ];
    }

    protected function getConfigFormValues()
    {
        $path = _MODULE_DIR_ . $this->name . '/views/img/';
        $this->context->smarty->assign('path', $path);

        $fields = [];

        $languages = Language::getLanguages();
        foreach ($languages as $lang) {
            $tmp = Configuration::get('TVCMSTWOOFFERBANNER_CAPTION', $lang['id_lang'], true);
            $fields['TVCMSTWOOFFERBANNER_CAPTION'][$lang['id_lang']] = $tmp;

            $tmp = Configuration::get('TVCMSTWOOFFERBANNER_CAPTION_2', $lang['id_lang'], true);
            $fields['TVCMSTWOOFFERBANNER_CAPTION_2'][$lang['id_lang']] = $tmp;
        }

        $tmp = Configuration::get('TVCMSTWOOFFERBANNER_IMAGE_NAME');
        $fields['TVCMSTWOOFFERBANNER_IMAGE_NAME'] = $tmp;

        $tmp = Configuration::get('TVCMSTWOOFFERBANNER_IMAGE_WIDTH_1');
        $fields['TVCMSTWOOFFERBANNER_IMAGE_WIDTH_1'] = $tmp;

        $tmp = Configuration::get('TVCMSTWOOFFERBANNER_IMAGE_HEIGHT_1');
        $fields['TVCMSTWOOFFERBANNER_IMAGE_HEIGHT_1'] = $tmp;

        $tmp = Configuration::get('TVCMSTWOOFFERBANNER_CAPTION_SIDE');
        $fields['TVCMSTWOOFFERBANNER_CAPTION_SIDE'] = $tmp;

        $tmp = Configuration::get('TVCMSTWOOFFERBANNER_LINK');
        $fields['TVCMSTWOOFFERBANNER_LINK'] = $tmp;

        $tmp = Configuration::get('TVCMSTWOOFFERBANNER_IMAGE_NAME_2');
        $fields['TVCMSTWOOFFERBANNER_IMAGE_NAME_2'] = $tmp;

        $tmp = Configuration::get('TVCMSTWOOFFERBANNER_IMAGE_WIDTH_2');
        $fields['TVCMSTWOOFFERBANNER_IMAGE_WIDTH_2'] = $tmp;

        $tmp = Configuration::get('TVCMSTWOOFFERBANNER_IMAGE_HEIGHT_2');
        $fields['TVCMSTWOOFFERBANNER_IMAGE_HEIGHT_2'] = $tmp;

        $tmp = Configuration::get('TVCMSTWOOFFERBANNER_CAPTION_SIDE_2');
        $fields['TVCMSTWOOFFERBANNER_CAPTION_SIDE_2'] = $tmp;

        $tmp = Configuration::get('TVCMSTWOOFFERBANNER_LINK_2');
        $fields['TVCMSTWOOFFERBANNER_LINK_2'] = $tmp;

        return $fields;
    }

    public function hookBackOfficeHeader()
    {
        if ($this->name == Tools::getValue('configure')) {
            $this->context->controller->addJS($this->_path . 'views/js/back.js');
            $this->context->controller->addCSS($this->_path . 'views/css/back.css');
        }
    }

    public function hookHeader()
    {
        $this->context->controller->addJS($this->_path . 'views/js/front.js');
        $this->context->controller->addCSS($this->_path . 'views/css/front.css');
    }

    public function hookdisplayHome()
    {
        return $this->showResult();
    }

    public function hookdisplayWrapperTop()
    {
        return $this->showResult();
    }

    public function hookdisplayContentWrapperTop()
    {
        return $this->showResult();
    }

    public function hookdisplayRightColumn()
    {
        return $this->showResult();
    }

    public function showResult()
    {
        $data = [];

        if (!Cache::isStored('tvcmstwoofferbanner_display_home.tpl')) {
            $languages = Language::getLanguages();
            foreach ($languages as $lang) {
                $tmp = Configuration::get('TVCMSTWOOFFERBANNER_CAPTION', $lang['id_lang'], true);
                $data['TVCMSTWOOFFERBANNER_CAPTION'][$lang['id_lang']] = $tmp;

                $tmp = Configuration::get('TVCMSTWOOFFERBANNER_CAPTION_2', $lang['id_lang'], true);
                $data['TVCMSTWOOFFERBANNER_CAPTION_2'][$lang['id_lang']] = $tmp;
            }

            $tmp = Configuration::get('TVCMSTWOOFFERBANNER_IMAGE_NAME');
            $data['TVCMSTWOOFFERBANNER_IMAGE_NAME'] = $tmp;

            $tmp = Configuration::get('TVCMSTWOOFFERBANNER_IMAGE_WIDTH_1');
            $data['TVCMSTWOOFFERBANNER_IMAGE_WIDTH_1'] = $tmp;

            $tmp = Configuration::get('TVCMSTWOOFFERBANNER_IMAGE_HEIGHT_1');
            $data['TVCMSTWOOFFERBANNER_IMAGE_HEIGHT_1'] = $tmp;

            $tmp = Configuration::get('TVCMSTWOOFFERBANNER_CAPTION_SIDE');
            $data['TVCMSTWOOFFERBANNER_CAPTION_SIDE'] = $tmp;

            $tmp = Configuration::get('TVCMSTWOOFFERBANNER_LINK');
            $data['TVCMSTWOOFFERBANNER_LINK'] = $tmp;

            $tmp = Configuration::get('TVCMSTWOOFFERBANNER_IMAGE_NAME_2');
            $data['TVCMSTWOOFFERBANNER_IMAGE_NAME_2'] = $tmp;

            $tmp = Configuration::get('TVCMSTWOOFFERBANNER_IMAGE_WIDTH_2');
            $data['TVCMSTWOOFFERBANNER_IMAGE_WIDTH_2'] = $tmp;

            $tmp = Configuration::get('TVCMSTWOOFFERBANNER_IMAGE_HEIGHT_2');
            $data['TVCMSTWOOFFERBANNER_IMAGE_HEIGHT_2'] = $tmp;

            $tmp = Configuration::get('TVCMSTWOOFFERBANNER_CAPTION_SIDE_2');
            $data['TVCMSTWOOFFERBANNER_CAPTION_SIDE_2'] = $tmp;

            $tmp = Configuration::get('TVCMSTWOOFFERBANNER_LINK_2');
            $data['TVCMSTWOOFFERBANNER_LINK_2'] = $tmp;

            $cookie = Context::getContext()->cookie;
            $id_lang = $cookie->id_lang;

            $this->context->smarty->assign('language_id', $id_lang);
            $this->context->smarty->assign('data', $data);

            $path = _MODULE_DIR_ . $this->name . '/views/img/';
            $this->context->smarty->assign('path', $path);

            $output = $this->display(__FILE__, 'views/templates/front/display_home.tpl');
            Cache::store('tvcmstwoofferbanner_display_home.tpl', $output);
        }

        return Cache::retrieve('tvcmstwoofferbanner_display_home.tpl');
    }
}
