<?php

require_once (dirname(__FILE__) . '/../../x13import.php');

use x13import\Importer\Manufacturer\ManufacturerImporter;
use x13import\Importer\Manufacturer\Enum\ManufacturerImportOption;
use x13import\Importer\Manufacturer\Enum\ManufacturerImportProductsOption;
use x13import\Importer\Manufacturer\Enum\WholesalerManufacturerImportOption;
use x13import\Importer\Manufacturer\Enum\WholesalerManufacturerImportProductsOption;
use x13import\Repository\PrestaShopManufacturerRepository;
use x13import\Wholesaler\WholesalerConfiguration;

class AdminXImportManufacturersController extends XImportController
{
    /** @var array */
    private $wholesalers;

    /** @var string */
    private $wholesalerTab;

    public function __construct()
    {
        $this->_pagination = [25, 50, 100];
        $this->_default_pagination = 25;
        $this->list_no_link = true;

        $this->table = 'ximport_manufacturer';
        $this->identifier = 'id_ximport_manufacturer';
        $this->className = 'XImportManufacturer';

        parent::__construct();

        $this->tpl_folder = 'x_import_manufacturers/';

        $this->fields_list = array(
            'manufacturer' => array(
                'title' => $this->l('Producent')
            ),
            'import_option' => array(
                'title' => $this->l('Ustawienia importu'),
                'class' => 'fixed-width-xxl',
                'search'  => false,
                'orderby' => false
            ),
            'id_manufacturer' => array(
                'title' => '',
                'class' => 'fixed-width-xxl',
                'search'  => false,
                'orderby' => false
            ),
            'import_products_option' => array(
                'title' => 'Ustawienia importu produktów',
                'class' => 'fixed-width-xxl',
                'search'  => false,
                'orderby' => false
            )
        );

        $this->bulk_actions = [
            'manufacturer' => [
                'text' => $this->l('Ustawienia producentów'),
                'icon' => 'bulkManufacturer'
            ]
        ];
    }

    public function init()
    {
        parent::init();

        // @todo manufacturersDownloadSuccess

        // @todo dont load on ajax
        $this->wholesalers = XImportWholesalers::getWholesalers();

        if (empty($this->wholesalers)) {
            $this->display = 'view';
            $this->errors[] = $this->l('Brak hurtowni');
        } else {
            $this->display = 'list';
            $this->wholesalerTab = Tools::getValue('wholesalerTab', current($this->wholesalers)['name']);
            self::$currentIndex .= '&wholesalerTab=' . $this->wholesalerTab;
        }
    }

    public function setMedia($isNewTheme = false)
    {
        parent::setMedia($isNewTheme);

        $this->addCSS($this->module->getPathUri() . 'views/js/select2/css/select2.min.css');
        $this->addJS($this->module->getPathUri() . 'views/js/select2/js/select2.full.min.js');

        $this->addJS($this->module->getPathUri() . 'views/js/x13importManufacturers.js');
    }

    public function renderList()
    {
        $wholesalerConfiguration = new WholesalerConfiguration($this->wholesalerTab);

        $this->tpl_list_vars = [
            'wholesalers' => $this->wholesalers,
            'wholesalerTab' => $this->wholesalerTab,
            'wholesalerHasManufacturers' => $this->wholesalers[$this->wholesalerTab]['hasManufacturers'],
            'wholesalerImportManufacturers' => $wholesalerConfiguration->IMPORT_MANUFACTURERS,
            'wholesalerImportManufacturersProducts' => $wholesalerConfiguration->IMPORT_MANUFACTURERS_PRODUCTS,
            'wholesalerManufacturerImportOption' => WholesalerManufacturerImportOption::toChoseList(),
            'wholesalerManufacturerImportProductsOption' => WholesalerManufacturerImportProductsOption::toChoseList(),
            'manufacturerImportOption' => ManufacturerImportOption::toChoseList(),
            'manufacturerImportProductsOption' => ManufacturerImportProductsOption::toChoseList()
        ];

        Media::addJsDef([
            'x13ImportDef' => [
                'wholesalerTab' => $this->wholesalerTab,
                'manufacturersOnLoad' => PrestaShopManufacturerRepository::getManufacturers(true, true),
                'manufacturerImportOption' => ManufacturerImportOption::toChoseList()
            ]
        ]);

        $this->addRowAction('actionButtons');

        return parent::renderList();
    }

    public function getList($id_lang, $order_by = null, $order_way = null, $start = 0, $limit = null, $id_lang_shop = false)
    {
        $this->_select = 'm.`name` as manufacturer_name';

        $this->_join = '
            LEFT JOIN `' . _DB_PREFIX_ . 'manufacturer` m
                ON (m.`id_manufacturer` = a.`id_manufacturer`)';

        $this->_where = 'AND a.`wholesaler` = "' . pSQL($this->wholesalerTab) . '"';
        $this->_orderBy = 'manufacturer';
        $this->_orderWay = 'ASC';

        parent::getList($id_lang, $order_by, $order_way, $start, $limit, $id_lang_shop);
    }

    public function displayActionButtonsLink($token, $id, $name = null)
    {
        $tpl = $this->context->smarty->createTemplate($this->module->getLocalPath() . 'views/templates/admin/' . $this->tpl_folder . 'helpers/list/action_buttons.tpl');
        $tpl->assign([
            'xManufacturerId' => $id
        ]);

        return $tpl->fetch();
    }

    public function ajaxProcessSearchManufacturers()
    {
        $this->returnAjax(PrestaShopManufacturerRepository::getManufacturers(true, true));
    }

    public function ajaxProcessCreateManufacturer()
    {
        try {
            $this->returnAjaxSuccess([
                'manufacturerId' => ManufacturerImporter::importManufacturer(Tools::getValue('name'))
            ]);
        }
        catch (Exception $e) {
            $this->returnAjaxError($e->getMessage());
        }
    }

    public function ajaxProcessSaveManufacturerMapping()
    {
        $data = Tools::getValue('data', []);

        try {
            if ($data['importOption'] === ManufacturerImportOption::ASSIGN_VALUE && empty($data['manufacturerId'])) {
                throw new UnexpectedValueException('Nie wybrano producenta do powiązania.');
            }

            $xManufacturer = new XImportManufacturer((int)$data['xManufacturerId']);
            $manufacturerImportOptionPrevious = $xManufacturer->import_option;

            if ($data['importOption'] === ManufacturerImportOption::ASSIGN_VALUE) {
                $xManufacturer->id_manufacturer = (int)$data['manufacturerId'];
            }
            // clear association when previous option was ASSIGN_VALUE
            else if ($manufacturerImportOptionPrevious === ManufacturerImportOption::ASSIGN_VALUE) {
                $xManufacturer->id_manufacturer = 0;
            }

            $xManufacturer->import_option = $data['importOption'];
            $xManufacturer->import_products_option = $data['importProductsOption'];

            // assign new Manufacturer if exist
            if ($xManufacturer->import_option !== ManufacturerImportOption::DONT_IMPORT && !$xManufacturer->id_manufacturer) {
                $xManufacturer->id_manufacturer = ManufacturerImporter::searchManufacturer($xManufacturer->manufacturer);
            }

            $xManufacturer->save();
            $result = $xManufacturer->toArray();

            if ($xManufacturer->id_manufacturer) {
                $result['name'] = Manufacturer::getNameById($xManufacturer->id_manufacturer);
            }

            $this->returnAjaxSuccess($result);
        }
        catch (Exception $e) {
            $this->returnAjaxError($e->getMessage());
        }
    }

    public function ajaxProcessSaveWholesalerOption()
    {
        try {
            $wholesalerConfiguration = new WholesalerConfiguration($this->wholesalerTab);
            $wholesalerConfiguration->{Tools::getValue('key')} = Tools::getValue('value');

            $this->returnAjaxSuccess();
        }
        catch (Exception $e) {
            $this->returnAjaxError($e->getMessage());
        }
    }

    public function ajaxProcessDownloadManufacturers()
    {
        $wholesalerClassName = XImportWholesalers::getWholesalerClassName($this->wholesalerTab);
        require_once (X13_IMPORT_WHOLESALERS_DIR . $this->wholesalerTab . '.php');

        try {
            /** @var XImportWholesalers $wholesaler */
            $wholesaler = new $wholesalerClassName();
            $wholesaler->importManufacturers();

            $this->returnAjaxSuccess();
        }
        catch (Exception $e) {
            $this->returnAjaxError($e->getMessage());
        }
    }
}
