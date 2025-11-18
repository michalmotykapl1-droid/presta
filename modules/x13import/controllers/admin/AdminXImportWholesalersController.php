<?php

require_once (dirname(__FILE__) . '/../../x13import.php');

use x13import\Component\Logger;
use x13import\Component\MaintenanceMode;
use x13import\Wholesaler\WholesalerConfiguration;
use x13import\Wholesaler\WholesalerOptions;
use x13import\Wholesaler\WholesalerUploader;

class AdminXImportWholesalersController extends XImportController
{
    public function __construct()
    {
        $this->table = 'ximport_configuration_wholesaler';

        parent::__construct();

        $this->tpl_folder = 'x_import_wholesalers/';

        $this->fields_options = array(
            'general' => array(
                'title' =>	$this->l('Hurtownie'),
                'image' => '../img/t/AdminPreferences.gif',
                'fields' =>	array(
                    'empty' => array(
                        'type' => 'empty'
                    )
                ),
                'submit' => array(
                    'title' => $this->l('Zapisz')
                )
            )
        );

        $wholesalers = [];
        foreach (XImportWholesalers::getWholesalers() as $wholesaler) {
            $wholesalerOptions = new WholesalerOptions(new WholesalerConfiguration($wholesaler['name']));
            $wholesalers[] = array_merge($wholesaler, [
                'baseOptions' => $wholesalerOptions->getBaseOptions(),
                'availabilityOptions' => $wholesalerOptions->getAvailabilityOptions(),
                'descriptionOptions' => $wholesalerOptions->getDescriptionOptions(),
                'additionalOptions' => $wholesalerOptions->getAdditionalOptions(),
                'importRestrictionOptions' => $wholesalerOptions->getImportRestrictionOptions()
            ]);
        }

        $iso = $this->context->language->iso_code;
        $this->tpl_option_vars['iso'] = file_exists(_PS_CORE_DIR_ . '/js/tiny_mce/langs/' . $iso . '.js') ? $iso : 'en';
        $this->tpl_option_vars['path_css'] = _THEME_CSS_DIR_;
        $this->tpl_option_vars['ad'] = __PS_BASE_URI__ . basename(_PS_ADMIN_DIR_);
        $this->tpl_option_vars['tinymce'] = true;
        $this->tpl_option_vars['wholesalers'] = $wholesalers;
        $this->tpl_option_vars['wholesalers_link'] = $this->context->link->getAdminLink('AdminXImportWholesalers');
        $this->tpl_option_vars['tab_wholesaler'] = Tools::getValue('tabWholesaler', '');
        $this->tpl_option_vars['tab_wholesaler_option'] = Tools::getValue('tabWholesalerOption', '');

        $this->tpl_option_vars['maintenance_mode'] = (MaintenanceMode::isShopDev()
            || MaintenanceMode::isModuleDev()
            || MaintenanceMode::isModuleDevEmployee($this->context->employee));
    }

    public function renderOptions()
    {
        if (Tools::getIsset('wholesalerUploadSuccess')) {
            if (count(explode(',', Tools::getValue('wholesalerUploadName'))) == 1) {
                $uploadWholesalersMessage = 'Dodano hurtownię %s, przejdź do jej konfiguracji';
            } else {
                $uploadWholesalersMessage = 'Dodano hurtownie %s, przejdź do ich konfiguracji';
            }

            $this->confirmations[] = sprintf($this->l($uploadWholesalersMessage), '<strong>' . str_replace(',', ', ', Tools::getValue('wholesalerUploadName')) . '</strong>');
        }

        unset($this->toolbar_btn);
        $this->display = 'options';
        $this->initToolbar();

        $helper = new HelperXImportOptions();
        $this->setHelperDisplay($helper);
        $helper->id = $this->id;
        $helper->tpl_vars = $this->tpl_option_vars;

        return $helper->generateOptions($this->fields_options);
    }

    public function initPageHeaderToolbar()
    {
        $this->page_header_toolbar_btn['products_off'] = array(
            'href' => self::$currentIndex.'&products_off&token='.$this->token,
            'desc' => $this->l('Wyłącz produkty ze wszystkich hurtowni'),
            'icon' => 'process-icon-off',
            'class' => 'x-products-off'
        );

        $this->page_header_toolbar_btn['products_delete'] = array(
            'href' => self::$currentIndex.'&products_delete&token='.$this->token,
            'desc' => $this->l('Usuń produkty ze wszystkich hurtowni'),
            'icon' => 'process-icon-delete',
            'class' => 'x-products-delete'
        );

        parent::initPageHeaderToolbar();
    }

    public function setMedia($isNewTheme = false)
    {
        parent::setMedia($isNewTheme);

        $this->addJS(_PS_JS_DIR_ . 'tiny_mce/tiny_mce.js');

        if (version_compare(_PS_VERSION_, '1.6.0.11', '<')) {
            $this->addJS(_PS_JS_DIR_ . 'tinymce.inc.js');
        } else {
            $this->addJS(_PS_JS_DIR_ . 'admin/tinymce.inc.js');
        }

        $this->addJS($this->module->getPathUri() . 'views/js/x13importWholesalers.js');
    }

    public function postProcess()
    {
        if (Tools::isSubmit('submitAddNewWholesaler')) {
            if (isset($_FILES['wholesaler_file']) && !empty($_FILES['wholesaler_file']['tmp_name'])) {
                $uploader = new WholesalerUploader($_FILES['wholesaler_file']);

                if ((int)$_FILES['wholesaler_file']['error'] !== UPLOAD_ERR_OK) {
                    $this->errors[] = $this->l('Wystąpił błąd podczas przesyłania pliku.');
                    return false;
                }

                if (!$uploader->validateUploadedFile()) {
                    $this->errors[] = $this->l('Przesłano nieprawidłowy typ pliku.');
                    return false;
                }

                try {
                    $wholesalers = $uploader->moveUploadedFile();

                    if (!empty($wholesalers)) {
                        Tools::redirectAdmin($this->context->link->getAdminLink('AdminXImportWholesalers')
                            . '&wholesalerUploadSuccess&wholesalerUploadName=' . implode(',', $wholesalers)
                            . (count($wholesalers) == 1 ? '&tabWholesaler=' . $wholesalers[0] : ''));
                    }

                    $this->errors[] = $this->l('Przesłany plik lub archiwum hurtowni jest nieprawidłowe.');
                }
                catch (Exception $exception) {
                    $this->errors[] = $exception->getMessage();
                }
            }
            else {
                $this->errors[] = $this->l('Nie wybrano pliku do przesłania.');
            }
        }

        return parent::postProcess();
    }

    protected function processUpdateOptions()
    {
        $this->beforeUpdateOptions();

        foreach ($this->fields_options as $category_data) {
            if (!isset($category_data['fields'])) {
                continue;
            }

            foreach ($category_data['fields'] as $key => $options) {
                if ($options['type'] != 'hr' || $options['type'] != 'empty') {
                    XImportConfiguration::updateValue($key, trim(Tools::getValue($key, '')));
                }
            }
        }

        $configurationValues = Tools::getValue('wholesaler', []);

        foreach (XImportWholesalers::getWholesalers() as $wholesaler) {
            $wholesalerConfiguration = new WholesalerConfiguration($wholesaler['name']);

            if (isset($configurationValues[$wholesaler['name']])) {
                foreach ($configurationValues[$wholesaler['name']] as $key => $value) {
                    $wholesalerConfiguration->{$key} = (!is_array($value) ? trim($value) : array_filter($value));
                }
            }
        }

        Tools::redirectAdmin($this->context->link->getAdminLink('AdminXImportWholesalers') .
            '&conf=6&tabWholesaler=' . Tools::getValue('tabWholesaler', '') . '&tabWholesalerOption=' . Tools::getValue('tabWholesalerOption', ''));
    }

    public function ajaxProcessCountProducts()
    {
        die(json_encode([
            'status' => true,
            'count' => XImportProduct::countProducts(Tools::getValue('wholesalerCode', null))
        ]));
    }

    public function ajaxProcessProductsDelete()
    {
        $products = XImportProduct::getProductsIds(Tools::getValue('wholesalerCode', null), (int)Tools::getValue('limit'));

        if (empty($products)) {
            if (XImportConfiguration::get('CLEAR_CACHE')) {
                try {
                    Hook::exec('actionProductUpdate');
                } catch (Exception $ex) {
                    Logger::instance()->exception($ex);
                }
            }

            die(json_encode([
                'status' => true,
                'finish' => true
            ]));
        }

        foreach ($products as $productId) {
            $product = new Product($productId);
            if (Validate::isLoadedObject($product)) {
                $product->delete();
            }
        }

        die(json_encode([
            'status' => true,
            'finish' => false
        ]));
    }

    public function ajaxProcessProductsOff()
    {
        $wholesalerCode = Tools::getValue('wholesalerCode', null);
        $countActive = XImportProduct::countProducts($wholesalerCode, true);

        XImportProduct::unActiveAllProducts($wholesalerCode);

        if (XImportConfiguration::get('CLEAR_CACHE')) {
            try {
                Hook::exec('actionProductUpdate');
            } catch (Exception $ex) {
                Logger::instance()->exception($ex);
            }
        }

        die(json_encode([
            'status' => true,
            'count' => $countActive
        ]));
    }
}
