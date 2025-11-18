<?php

require_once (dirname(__FILE__) . '/../../x13import.php');

use x13import\Importer\Feature\FeatureImporter;
use x13import\Importer\Feature\FeatureRepository;
use x13import\Importer\Feature\Enum\FeatureImportOption;
use x13import\Importer\Feature\Enum\FeatureValueImportOption;
use x13import\Importer\Feature\Enum\FeatureValueImportDefaultOption;
use x13import\Importer\Feature\Enum\WholesalerFeatureImportOption;
use x13import\Repository\PrestaShopFeatureRepository;
use x13import\Wholesaler\WholesalerConfiguration;

class AdminXImportFeaturesController extends XImportController
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

        $this->table = 'ximport_feature';
        $this->identifier = 'id_ximport_feature';
        $this->className = 'XImportFeature';

        parent::__construct();

        $this->tpl_folder = 'x_import_features/';

        $this->fields_list = array(
            'name' => array(
                'title' => $this->l('Grupa cech'),
                'filter_key' => 'a!name'
            ),
            'values_nb' => array(
                'title' => $this->l('Wartości'),
                'align' => 'center',
                'search'  => false,
                'orderby' => false
            ),
            'import_option' => array(
                'title' => $this->l('Ustawienia importu'),
                'class' => 'fixed-width-xxl',
                'search'  => false,
                'orderby' => false
            ),
            'id_feature' => array(
                'title' => '',
                'class' => 'fixed-width-xxl',
                'search'  => false,
                'orderby' => false
            ),
            'import_as_custom' => array(
                'title' => '',
                'class' => 'fixed-width-xxl',
                'search'  => false,
                'orderby' => false
            )
        );

        $this->bulk_actions = [
            'feature_group' => [
                'text' => $this->l('Ustawienia grup cech'),
                'icon' => 'bulkFeatureGroup'
            ],
            'feature_value' => [
                'text' => $this->l('Ustawienia wartości cech'),
                'icon' => 'bulkFeatureValue'
            ]
        ];
    }

    public function init()
    {
        parent::init();

        // @todo featuresDownloadSuccess

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

        $this->addJS($this->module->getPathUri() . 'views/js/x13importFeatures.js');
    }

    public function renderList()
    {
        $wholesalerConfiguration = new WholesalerConfiguration($this->wholesalerTab);

        $this->tpl_list_vars = [
            'wholesalers' => $this->wholesalers,
            'wholesalerTab' => $this->wholesalerTab,
            'wholesalerHasFeatures' => $this->wholesalers[$this->wholesalerTab]['hasFeatures'],
            'wholesalerImportFeatures' => $wholesalerConfiguration->IMPORT_FEATURES,
            'wholesalerFeatureImportOption' => WholesalerFeatureImportOption::toChoseList(),
            'featureImportOption' => FeatureImportOption::toChoseList(),
            'featureValueImportOption' => FeatureValueImportOption::toChoseList()
        ];

        Media::addJsDef([
            'x13ImportDef' => [
                'wholesalerTab' => $this->wholesalerTab,
                'featureGroupsOnLoad' => PrestaShopFeatureRepository::getAllFeatureGroups($this->context->language->id, true),
                'featureImportOption' => FeatureImportOption::toChoseList(),
                'featureValueImportOption' => FeatureValueImportOption::toChoseList()
            ]
        ]);

        $this->addRowAction('actionButtons');

        return parent::renderList();
    }

    public function getList($id_lang, $order_by = null, $order_way = null, $start = 0, $limit = null, $id_lang_shop = false)
    {
        $this->_select = '
            fl.`name` as feature_name,
            COUNT(xfv.`id_ximport_feature_value`) as values_nb,
            IFNULL(a.`import_as_custom`, a.`custom`) as import_as_custom';

        $this->_join = '
            LEFT JOIN `' . _DB_PREFIX_ . 'ximport_feature_value` xfv
                ON (xfv.`fingerprint_feature` = a.`fingerprint_feature`)
            LEFT JOIN `' . _DB_PREFIX_ . 'feature_lang` fl
                ON (fl.`id_feature` = a.`id_feature`
                    AND fl.`id_lang` = ' . (int)$this->context->language->id . ')';

        $this->_where = 'AND a.`wholesaler` = "' . pSQL($this->wholesalerTab) . '"';
        $this->_group = 'GROUP BY a.`id_ximport_feature`';
        $this->_orderBy = 'name';
        $this->_orderWay = 'ASC';

        parent::getList($id_lang, $order_by, $order_way, $start, $limit, $id_lang_shop);
    }

    public function displayActionButtonsLink($token, $id, $name = null)
    {
        $tpl = $this->context->smarty->createTemplate($this->module->getLocalPath() . 'views/templates/admin/' . $this->tpl_folder . 'helpers/list/action_buttons.tpl');
        $tpl->assign([
            'xFeatureId' => $id
        ]);

        return $tpl->fetch();
    }

    // @todo refacto names
    public function ajaxProcessLoadFeaturesValues()
    {
        $xFeature = new XImportFeature((int)Tools::getValue('xFeatureId'));

        $tpl = $this->context->smarty->createTemplate($this->module->getLocalPath() . 'views/templates/admin/' . $this->tpl_folder . 'features-values-list.tpl');
        $tpl->assign([
            'xFeatureId' => $xFeature->id,
            'xFeatureName' => $xFeature->name,
            'xFeatureImportDefaultOption' => $xFeature->import_default_option,
            'featureValueImportOption' => FeatureValueImportOption::toChoseList(),
            'featureValueImportDefaultOption' => FeatureValueImportDefaultOption::toChoseList(),
            'featuresValues' => FeatureRepository::getFeatureValues($xFeature->id, true, $this->context->language->id),
            'featureId' => (int)Tools::getValue('featureId'),
            'featureImportOption' => Tools::getValue('featureImportOption'),
            'isOdd' => Tools::getValue('isOdd')
        ]);

        $this->returnAjaxSuccess([
            'featuresValuesList' => $tpl->fetch()
        ]);
    }

    public function ajaxProcessSearchFeatures()
    {
        $this->returnAjax(PrestaShopFeatureRepository::getAllFeatureGroups($this->context->language->id, true));
    }

    public function ajaxProcessSearchFeatureValues()
    {
        $this->returnAjax(PrestaShopFeatureRepository::getFeatureValues((int)Tools::getValue('featureId'), $this->context->language->id, true));
    }

    public function ajaxProcessCreateFeature()
    {
        try {
            $this->returnAjaxSuccess([
                'featureId' => FeatureImporter::importFeatureGroup(Tools::getValue('name'))
            ]);
        }
        catch (Exception $e) {
            $this->returnAjaxError($e->getMessage());
        }
    }

    public function ajaxProcessCreateFeatureValue()
    {
        try {
            if (!($featureId = (int)Tools::getValue('featureId'))) {
                $featureId = FeatureImporter::importFeatureGroup(Tools::getValue('name'));
            }

            $this->returnAjaxSuccess([
                'featureId' => $featureId,
                'featureValueId' => FeatureImporter::importFeatureValue($featureId, Tools::getValue('value'))
            ]);
        }
        catch (Exception $e) {
            $this->returnAjaxError($e->getMessage());
        }
    }

    public function ajaxProcessSaveFeatureMapping()
    {
        $data = Tools::getValue('data', []);
        $result = [];

        try {
            if ($data['importOption'] === FeatureImportOption::ASSIGN_VALUE && empty($data['featureId'])) {
                throw new UnexpectedValueException('Nie wybrano grupy cech do powiązania.');
            }

            $xFeature = new XImportFeature((int)$data['xFeatureId']);
            $featureIdPrevious = $xFeature->id_feature;
            $featureImportOptionPrevious = $xFeature->import_option;

            if ($data['importOption'] === FeatureImportOption::ASSIGN_VALUE) {
                $xFeature->id_feature = (int)$data['featureId'];
            }
            // clear association when previous option was ASSIGN_VALUE
            else if ($featureImportOptionPrevious === FeatureImportOption::ASSIGN_VALUE) {
                $xFeature->id_feature = 0;
            }

            if (!empty($data['importOptionDefault'])) {
                $xFeature->import_default_option = $data['importOptionDefault'];
            }

            $xFeature->import_as_custom = (bool)$data['importAsCustom'];
            $xFeature->import_option = $data['importOption'];

            // assign new Feature if exist
            if ($xFeature->import_option !== FeatureImportOption::DONT_IMPORT && !$xFeature->id_feature) {
                $xFeature->id_feature = FeatureImporter::searchFeatureGroup($xFeature->name);
            }

            $xFeature->save();
            $result = $xFeature->toArray();

            if ($xFeature->id_feature) {
                $feature = new Feature($xFeature->id_feature, $this->context->language->id);
                $result['name'] = $feature->name;
            }

            if (!empty($data['featureValues'])) {
                foreach ($data['featureValues'] as $featureValue) {
                    if ($featureValue['importOption'] === FeatureValueImportOption::ASSIGN_VALUE && empty($featureValue['featureValueId'])) {
                        throw new UnexpectedValueException('Nie wybrano wartości cech do powiązania.');
                    }

                    $xFeatureValue = new XImportFeatureValue((int)$featureValue['xFeatureValueId']);
                    $featureValueImportOptionPrevious = $xFeatureValue->import_option;

                    if ($featureValue['importOption'] === FeatureValueImportOption::ASSIGN_VALUE) {
                        $xFeatureValue->id_feature_value = (int)$featureValue['featureValueId'];
                    }
                    // clear association when previous option was ASSIGN_VALUE
                    // or previous featureId was different than current
                    else if ($featureValueImportOptionPrevious === FeatureValueImportOption::ASSIGN_VALUE
                        || $xFeature->id_feature != $featureIdPrevious
                    ) {
                        $xFeatureValue->id_feature_value = 0;
                    }

                    $xFeatureValue->import_option = $featureValue['importOption'];

                    // assign new FeatureValue if exist
                    if ($xFeatureValue->import_option !== FeatureValueImportOption::DONT_IMPORT
                        && !$xFeatureValue->id_feature_value
                        && $xFeature->id_feature
                        && !$xFeature->import_as_custom
                    ) {
                        $xFeatureValue->id_feature_value = FeatureImporter::searchFeatureValue($xFeature->id_feature, $xFeatureValue->value);
                    }

                    $xFeatureValue->save();
                    $resultFeatureValue = $xFeatureValue->toArray();

                    if ($xFeature->id_feature) {
                        $featureValue = new FeatureValue($xFeatureValue->id_feature_value, $this->context->language->id);
                        $resultFeatureValue['value'] = $featureValue->value;
                    }

                    $result['featureValues'][] = $resultFeatureValue;
                }
            } else {
                // clear association when previous featureId was different than current
                // and assign new FeatureValue if exist
                if ($xFeature->id_feature != $featureIdPrevious) {
                    foreach (FeatureRepository::getFeatureValues($xFeature->id) as $featureValue) {
                        $xFeatureValue = new XImportFeatureValue((int)$featureValue['id_ximport_feature_value']);
                        $xFeatureValue->id_feature_value = 0;

                        if ($xFeatureValue->import_option !== FeatureValueImportOption::DONT_IMPORT) {
                            $xFeatureValue->id_feature_value = FeatureImporter::searchFeatureValue($xFeature->id_feature, $xFeatureValue->value);
                        }

                        $xFeatureValue->save();
                    }
                }
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
            $wholesalerConfiguration->IMPORT_FEATURES = Tools::getValue('value');

            $this->returnAjaxSuccess();
        }
        catch (Exception $e) {
            $this->returnAjaxError($e->getMessage());
        }
    }

    public function ajaxProcessDownloadFeatures()
    {
        $wholesalerClassName = XImportWholesalers::getWholesalerClassName($this->wholesalerTab);
        require_once (X13_IMPORT_WHOLESALERS_DIR . $this->wholesalerTab . '.php');

        try {
            /** @var XImportWholesalers $wholesaler */
            $wholesaler = new $wholesalerClassName();
            $wholesaler->importFeatures();

            $this->returnAjaxSuccess();
        }
        catch (Exception $e) {
            $this->returnAjaxError($e->getMessage());
        }
    }
}
