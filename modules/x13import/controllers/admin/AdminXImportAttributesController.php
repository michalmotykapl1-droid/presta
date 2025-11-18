<?php

require_once (dirname(__FILE__) . '/../../x13import.php');

use x13import\Adapter\AttributeAdapter;
use x13import\Importer\Attribute\AttributeImporter;
use x13import\Importer\Attribute\AttributeRepository;
use x13import\Importer\Attribute\Enum\AttributeGroupImportOption;
use x13import\Importer\Attribute\Enum\AttributeImportDefaultOption;
use x13import\Importer\Attribute\Enum\AttributeImportOption;
use x13import\Importer\Attribute\Enum\WholesalerAttributeImportOption;
use x13import\Repository\PrestaShopAttributeRepository;
use x13import\Wholesaler\WholesalerConfiguration;

class AdminXImportAttributesController extends XImportController
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

        $this->table = 'ximport_attribute_group';
        $this->identifier = 'id_ximport_attribute_group';
        $this->className = 'XImportAttributeGroup';

        parent::__construct();

        $this->tpl_folder = 'x_import_attributes/';

        $this->fields_list = array(
            'group_name' => array(
                'title' => $this->l('Grupa atrybutów'),
                'filter_key' => 'a!group_name'
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
            'id_attribute_group' => array(
                'title' => '',
                'class' => 'fixed-width-xxl',
                'search'  => false,
                'orderby' => false
            )
        );

        $this->bulk_actions = [
            'attribute_group' => [
                'text' => $this->l('Ustawienia grup atrybutów'),
                'icon' => 'bulkAttributeGroup'
            ],
            'attribute' => [
                'text' => $this->l('Ustawienia wartości atrybutów'),
                'icon' => 'bulkAttribute'
            ]
        ];
    }

    public function init()
    {
        parent::init();

        // @todo attributesDownloadSuccess

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

        $this->addJS($this->module->getPathUri() . 'views/js/x13importAttributes.js');
    }

    public function renderList()
    {
        $wholesalerConfiguration = new WholesalerConfiguration($this->wholesalerTab);

        $this->tpl_list_vars = [
            'wholesalers' => $this->wholesalers,
            'wholesalerTab' => $this->wholesalerTab,
            'wholesalerHasAttributes' => $this->wholesalers[$this->wholesalerTab]['hasAttributes'],
            'wholesalerImportAttributes' => $wholesalerConfiguration->IMPORT_ATTRIBUTES,
            'wholesalerAttributeImportOption' => WholesalerAttributeImportOption::toChoseList(),
            'attributeGroupImportOption' => AttributeGroupImportOption::toChoseList(),
            'attributeImportOption' => AttributeImportOption::toChoseList()
        ];

        Media::addJsDef([
            'x13ImportDef' => [
                'wholesalerTab' => $this->wholesalerTab,
                'attributeGroupsOnLoad' => PrestaShopAttributeRepository::getAllAttributeGroups($this->context->language->id, true),
                'attributeGroupImportOption' => AttributeGroupImportOption::toChoseList(),
                'attributeImportOption' => AttributeImportOption::toChoseList()
            ]
        ]);

        $this->addRowAction('actionButtons');

        return parent::renderList();
    }

    public function getList($id_lang, $order_by = null, $order_way = null, $start = 0, $limit = null, $id_lang_shop = false)
    {
        $this->_select = '
            agl.`name` as attribute_group_name,
            COUNT(xat.`id_ximport_attribute`) as values_nb';

        $this->_join = '
            LEFT JOIN `' . _DB_PREFIX_ . 'ximport_attribute` xat
                ON (xat.`fingerprint_attribute_group` = a.`fingerprint_attribute_group`)
            LEFT JOIN `' . _DB_PREFIX_ . 'attribute_group_lang` agl
                ON (agl.`id_attribute_group` = a.`id_attribute_group`
                    AND agl.`id_lang` = ' . (int)$this->context->language->id . ')';

        $this->_where = 'AND a.`wholesaler` = "' . pSQL($this->wholesalerTab) . '"';
        $this->_group = 'GROUP BY a.`id_ximport_attribute_group`';
        $this->_orderBy = 'group_name';
        $this->_orderWay = 'ASC';

        parent::getList($id_lang, $order_by, $order_way, $start, $limit, $id_lang_shop);
    }

    public function displayActionButtonsLink($token, $id, $name = null)
    {
        $tpl = $this->context->smarty->createTemplate($this->module->getLocalPath() . 'views/templates/admin/' . $this->tpl_folder . 'helpers/list/action_buttons.tpl');
        $tpl->assign([
            'xAttributeGroupId' => $id
        ]);

        return $tpl->fetch();
    }

    public function ajaxProcessLoadAttributeNames()
    {
        $xAttributeGroup = new XImportAttributeGroup((int)Tools::getValue('xAttributeGroupId'));

        $tpl = $this->context->smarty->createTemplate($this->module->getLocalPath() . 'views/templates/admin/' . $this->tpl_folder . 'attribute-names-list.tpl');
        $tpl->assign([
            'xAttributeGroupId' => $xAttributeGroup->id,
            'xAttributeGroupName' => $xAttributeGroup->group_name,
            'xAttributeImportDefaultOption' => $xAttributeGroup->import_default_option,
            'attributeImportOption' => AttributeImportOption::toChoseList(),
            'attributeImportDefaultOption' => AttributeImportDefaultOption::toChoseList(),
            'attributes' => AttributeRepository::getAttributes($xAttributeGroup->id, true, $this->context->language->id),
            'attributeGroupId' => (int)Tools::getValue('attributeGroupId'),
            'attributeGroupImportOption' => Tools::getValue('attributeGroupImportOption'),
            'isOdd' => Tools::getValue('isOdd')
        ]);

        $this->returnAjaxSuccess([
            'attributeNamesList' => $tpl->fetch()
        ]);
    }

    public function ajaxProcessSearchAttributeGroups()
    {
        $this->returnAjax(PrestaShopAttributeRepository::getAllAttributeGroups($this->context->language->id, true));
    }

    public function ajaxProcessSearchAttributeNames()
    {
        $this->returnAjax(PrestaShopAttributeRepository::getAttributeNames((int)Tools::getValue('attributeGroupId'), $this->context->language->id, true));
    }

    public function ajaxProcessCreateAttributeGroup()
    {
        try {
            $this->returnAjaxSuccess([
                'attributeGroupId' => AttributeImporter::importAttributeGroup(Tools::getValue('name'))
            ]);
        }
        catch (Exception $e) {
            $this->returnAjaxError($e->getMessage());
        }
    }

    public function ajaxProcessCreateAttribute()
    {
        try {
            if (!($attributeGroupId = (int)Tools::getValue('attributeGroupId'))) {
                $attributeGroupId = AttributeImporter::importAttributeGroup(Tools::getValue('groupName'));
            }

            $this->returnAjaxSuccess([
                'attributeGroupId' => $attributeGroupId,
                'attributeId' => AttributeImporter::importAttribute($attributeGroupId, Tools::getValue('name'))
            ]);
        }
        catch (Exception $e) {
            $this->returnAjaxError($e->getMessage());
        }
    }

    public function ajaxProcessSaveAttributeMapping()
    {
        $data = Tools::getValue('data', []);
        $result = [];

        try {
            if ($data['importOption'] === AttributeGroupImportOption::ASSIGN_VALUE && empty($data['attributeGroupId'])) {
                throw new UnexpectedValueException('Nie wybrano grupy atrybutów do powiązania.');
            }

            $xAttributeGroup = new XImportAttributeGroup((int)$data['xAttributeGroupId']);
            $attributeGroupIdPrevious = $xAttributeGroup->id_attribute_group;
            $attributeGroupImportOptionPrevious = $xAttributeGroup->import_option;

            if ($data['importOption'] === AttributeGroupImportOption::ASSIGN_VALUE) {
                $xAttributeGroup->id_attribute_group = (int)$data['attributeGroupId'];
            }
            // clear association when previous option was ASSIGN_VALUE
            else if ($attributeGroupImportOptionPrevious === AttributeGroupImportOption::ASSIGN_VALUE) {
                $xAttributeGroup->id_attribute_group = 0;
            }

            if (!empty($data['importOptionDefault'])) {
                $xAttributeGroup->import_default_option = $data['importOptionDefault'];
            }

            $xAttributeGroup->import_option = $data['importOption'];

            // assign new AttributeGroup if exist
            if ($xAttributeGroup->import_option !== AttributeGroupImportOption::DONT_IMPORT && !$xAttributeGroup->id_attribute_group) {
                $xAttributeGroup->id_attribute_group = AttributeImporter::searchAttributeGroup($xAttributeGroup->group_name);
            }

            $xAttributeGroup->save();
            $result = $xAttributeGroup->toArray();

            if ($xAttributeGroup->id_attribute_group) {
                $attributeGroup = new AttributeGroup($xAttributeGroup->id_attribute_group, $this->context->language->id);
                $result['group_name'] = $attributeGroup->name;
            }

            if (!empty($data['attributeNames'])) {
                foreach ($data['attributeNames'] as $attributeName) {
                    if ($attributeName['importOption'] === AttributeImportOption::ASSIGN_VALUE && empty($attributeName['attributeId'])) {
                        throw new UnexpectedValueException('Nie wybrano wartości atrybutu do powiązania.');
                    }

                    $xAttribute = new XImportAttribute((int)$attributeName['xAttributeId']);
                    $attributeImportOptionPrevious = $xAttribute->import_option;

                    if ($attributeName['importOption'] === AttributeImportOption::ASSIGN_VALUE) {
                        $xAttribute->id_attribute = (int)$attributeName['attributeId'];
                    }
                    // clear association when previous option was ASSIGN_VALUE
                    // or previous attributeGroupId was different than current
                    else if ($attributeImportOptionPrevious === AttributeImportOption::ASSIGN_VALUE
                        || $xAttributeGroup->id_attribute_group != $attributeGroupIdPrevious
                    ) {
                        $xAttribute->id_attribute = 0;
                    }

                    $xAttribute->import_option = $attributeName['importOption'];

                    // assign new Attribute if exist
                    if ($xAttribute->import_option !== AttributeImportOption::DONT_IMPORT
                        && !$xAttribute->id_attribute
                        && $xAttributeGroup->id_attribute_group
                    ) {
                        $xAttribute->id_attribute = AttributeImporter::searchAttribute($xAttributeGroup->id_attribute_group, $xAttribute->name);
                    }

                    $xAttribute->save();
                    $resultAttribute = $xAttribute->toArray();

                    if ($xAttribute->id_attribute) {
                        /** @var Attribute|ProductAttribute $attributeClass */
                        $attributeClass = AttributeAdapter::getNamespace();

                        $attribute = new $attributeClass($xAttribute->id_attribute, $this->context->language->id);
                        $resultAttribute['name'] = $attribute->name;
                    }

                    $result['attributeNames'][] = $resultAttribute;
                }
            } else {
                // clear association when previous attributeGroupId was different than current
                // and assign new Attribute if exist
                if ($xAttributeGroup->id_attribute_group != $attributeGroupIdPrevious) {
                    foreach (AttributeRepository::getAttributes($xAttributeGroup->id) as $attribute) {
                        $xAttribute = new XImportAttribute((int)$attribute['id_ximport_attribute']);
                        $xAttribute->id_attribute = 0;

                        if ($xAttribute->import_option !== AttributeImportOption::DONT_IMPORT) {
                            $xAttribute->id_attribute = AttributeImporter::searchAttribute($xAttributeGroup->id_attribute_group, $xAttribute->name);
                        }

                        $xAttribute->save();
                    }
                }
            }

            $this->returnAjaxSuccess($result);
        }
        catch (Exception $e) {
            $this->returnAjaxError($e->getMessage());
        }

        $this->returnAjaxSuccess();
    }

    public function ajaxProcessSaveWholesalerOption()
    {
        try {
            $wholesalerConfiguration = new WholesalerConfiguration($this->wholesalerTab);
            $wholesalerConfiguration->IMPORT_ATTRIBUTES = Tools::getValue('value');

            $this->returnAjaxSuccess();
        }
        catch (Exception $e) {
            $this->returnAjaxError($e->getMessage());
        }
    }

    public function ajaxProcessDownloadAttributes()
    {
        $wholesalerClassName = XImportWholesalers::getWholesalerClassName($this->wholesalerTab);
        require_once (X13_IMPORT_WHOLESALERS_DIR . $this->wholesalerTab . '.php');

        try {
            /** @var XImportWholesalers $wholesaler */
            $wholesaler = new $wholesalerClassName();
            $wholesaler->importAttributes();

            $this->returnAjaxSuccess();
        }
        catch (Exception $e) {
            $this->returnAjaxError($e->getMessage());
        }
    }
}
