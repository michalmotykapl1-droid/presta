<?php

use x13import\Wholesaler\WholesalerConfiguration;

require_once (dirname(__FILE__) . '/../../x13import.php');

class AdminXImportCategoriesController extends XImportController
{
    /** @var XImportCategory */
    protected $object;

    public function __construct()
    {
        $this->table = 'ximport_category';
        $this->identifier = 'id_ximport_category';
        $this->className = 'XImportCategory';
        $this->list_no_link = true;

        parent::__construct();

        $this->tpl_folder = 'x_import_categories/';

        $this->fields_list = array(
            'id_ximport_category' => array(
                'title' => $this->l('ID'),
                'align' => 'center',
                'class' => 'fixed-width-xs',
                'width' => 50,
                'orderby' => true
            ),
            'path' => array(
                'title' => $this->l('Kategoria hurtowni'),
                'width' => 'auto',
                'callback' => 'pathCallback'
            ),
            'name' => array(
                'title' => $this->l('Kategoria w sklepie'),
                'width' => 200,
                'class' => 'fixed-width-lg',
            ),
            'wholesaler_name' => array(
                'title' => $this->l('Nazwa hurtowni'),
                'width' => 100,
                'class' => 'fixed-width-md',
            ),
            'import' => array(
                'title' => $this->l('Import'),
                'type' => 'bool',
                'active' => 'import',
                'class' => 'fixed-width-sm center x-import',
                'align' => 'center',
                'width' => 80,
                'search'  => true,
                'orderby' => true
            ),
            'markup' => array(
                'title' => $this->l('Narzut'),
                'width' => 80
            )
        );

        $this->bulk_actions = array(
            'delete' => array(
                'text' => $this->l('Usuń wybrane'),
                'icon' => 'icon-trash'
            ),
            'import' => array(
                'text' => $this->l('Włącz import'),
                'icon' => 'icon-power-off text-success'
            ),
            'noimport' => array(
                'text' => $this->l('Wyłącz import'),
                'icon' => 'icon-power-off text-danger'
            ),
            array(
                'text' => 'divider'
            ),
            'markup' => array(
                'text' => $this->l('Ustaw narzut kategorii'),
                'icon' => 'bulkMarkup'
            ),
            'tittlePattern' => array(
                'text' => $this->l('Ustaw wzorzec nazwy produktu'),
                'icon' => 'bulkTittlePattern'
            )
        );

        // @todo refactoring
        $this->_conf[32] = $this->l('Kategorie zostały poprawne pobrane.');
        $this->_conf[33] = $this->l('Kategorie zostały poprawne zaimportowane.');
        $this->_conf[34] = $this->l('Produkty zostały poprawnie wyłączone.');
        $this->_conf[35] = $this->l('Produkty zostały poprawnie usunięte.');
        $this->_conf[36] = $this->l('Narzut został popranie zmieniony.');
        $this->_conf[37] = $this->l('Aktualizacja zakończona.');
        $this->_conf[38] = $this->l('Wzorzec nazwy produktu został popranie zmieniony.');
    }

    public function renderList()
    {
        $this->addRowAction('edit');
        $this->addRowAction('delete');

        return parent::renderList();
    }

    public function renderForm()
    {
        $root_category = Category::getRootCategory();
        $root_category = array('id_category' => $root_category->id, 'name' => $root_category->name);
        $selectedCategoriesIds =
        $selectedCategories = [];

        if (!Validate::isLoadedObject($this->object)) {
            return '';
        }

        if ($this->object->id) {
            $selectedCategoriesIds = XImportCategory::getAdditionalCategoriesById($this->object->id);

            foreach ($selectedCategoriesIds as $selectedCategoryId) {
                $selectedCategories[] = [
                    'id_category' => $selectedCategoryId,
                    'name' => (new Category($selectedCategoryId, $this->context->language->id))->name
                ];
            }
        }

        $this->fields_form = array(
            'legend' => array(
                'title' => $this->l('Podstawowe dane'),
            ),
            'input' => array(
                array(
                    'type' => 'text',
                    'label' => $this->l('Kategoria hurtowni:'),
                    'name' => 'path',
                    'size' => 50,
                    'disabled' => true,
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Wzorzec nazwy produktu:'),
                    'desc' => $this->l('Wzorzec nazwy produktu może zawierać dowolne znaki alfanumeryczne oraz zmienne:').
                        '<br />'.
                        ' - <b>{%name%}</b> - '.$this->l('która zostanie zastąpiona nazwą produktu').
                        '<br />'.
                        ' - <b>{%reference%}</b> - '.$this->l('która zostanie zastąpiona kodem referencyjnym').
                        '<br />'.
                        ' - <b>{%manufacturer%}</b> - '.$this->l('która zostanie zastąpiona nazwą producenta').
                        '<br />'.
                        ' - <b>[%feature%]</b> - '.$this->l('która zostanie zastapiona wartością podanej cechy, np. [%Materiał%]'),
                    'name' => 'title_pattern',
                    'size' => 50
                ),
                array(
                    'type' => $this->bootstrap ? 'switch' : 'radio',
                    'label' => $this->l('Import danych:'),
                    'name' => 'import',
                    'class' => 't',
                    'is_bool' => true,
                    'values' => array(
                        array(
                            'id' => 'on',
                            'value' => 1,
                            'label' => $this->l('Tak')
                        ),
                        array(
                            'id' => 'off',
                            'value' => 0,
                            'label' => $this->l('Nie')
                        )
                    )
                ),
                array(
                    'type' => 'select',
                    'label' => $this->l('Rodzaj narzutu:'),
                    'name' => 'markup_type',
                    'required' => true,
                    'class' => 'fixed-width-xxl',
                    'options' => array(
                        'query' => array(
                            array('id_markup' => XImportCategory::MARKUP_PERCENT, 'name' => $this->l('procent')),
                            array('id_markup' => XImportCategory::MARKUP_AMOUNT, 'name' => $this->l('kwota'))
                        ),
                        'name' => 'name',
                        'id' => 'id_markup',
                    )
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Narzut:'),
                    'name' => 'markup',
                    'class' => 'fixed-width-md x-cast x-cast-float x-cast-float x-cast-unsigned',
                    'desc' => $this->l('Narzut do ceny brutto')
                ),
                array(
                    'type' => 'categories',
                    'label' => $this->l('Kategorie w sklepie:'),
                    'name' => 'id_additional_category',
                    'required' => true,
                    'tree'  => array(
                        'id'  => 'categories-tree',
                        'root_category' => $root_category['id_category'],
                        'use_search' => true,
                        'use_checkbox' => true,
                        'use_radio' => false,
                        'selected_categories' => $selectedCategoriesIds,
                    ),
                    'values' => array(
                        'trads' => array(
                            'Root' => $root_category,
                            'selected' => $this->l('Selected'),
                            'Collapse All' => $this->l('Collapse All'),
                            'Check All' => $this->l('Check All'),
                            'Uncheck All' => $this->l('Uncheck All'),
                            'Expand All' => $this->l('Expand All'),
                            'search' => $this->l('search'),
                        ),
                        'selected_cat' => $selectedCategoriesIds,
                        'disabled_categories' => array(),
                        'input_name' => 'id_additional_category[]',
                        'use_checkbox' => true,
                        'use_radio' => false,
                        'use_search' => true,
                        'top_category' => Category::getTopCategory(),
                        'use_context' => true,
                    )
                ),
                array(
                    'type' => 'select',
                    'label' => $this->l('Kategoria główna:'),
                    'name' => 'id_category',
                    'required' => true,
                    'class' => 'fixed-width-xxl',
                    'options' => array(
                        'query' => $selectedCategories,
                        'name' => 'name',
                        'id' => 'id_category',
                    )
                ),
            ),
            'submit' => array(
                'name' => 'save',
                'title' => $this->l('Zapisz'),
                'class' => 'button btn btn-default pull-right'
            )
        );

        return parent::renderForm();
    }

    public function initPageHeaderToolbar()
    {
        // hack toolbar
        unset($this->toolbar_btn['new']);
        $this->toolbar_btn['back'] = array(
            'href' => '',
            'desc' => ''
        );

        if (empty($this->display)) {
            $this->page_header_toolbar_btn['categories_download'] = array(
                'href' => '#',
                'desc' => $this->l('Pobierz kategorie z hurtowni'),
                'icon' => 'process-icon-download',
                'class' => 'x-categories-download'
            );

            $this->page_header_toolbar_btn['categories_import'] = array(
                'href' => '#',
                'desc' => $this->l('Importuj kategorie do sklepu'),
                'icon' => 'process-icon-download',
                'class' => 'x-categories-import'
            );
        }

        parent::initPageHeaderToolbar();
    }

    public function setMedia($isNewTheme = false)
    {
        parent::setMedia($isNewTheme);

        if (version_compare(_PS_VERSION_, '1.7', '<')) {
            $this->addJS(PS_ADMIN_DIR . '/themes/default/js/tree.js');
            $this->addJS(PS_ADMIN_DIR . '/themes/default/js/vendor/typeahead.min.js');
        }

        $this->addJS($this->module->getPathUri() . 'views/js/x13importCategories.js');
    }

    public function getList($id_lang, $order_by = null, $order_way = null, $start = 0, $limit = null, $id_lang_shop = false)
    {
        $this->_select .= 'l.`name` AS `name`, a.`markup_type`';
        $this->_join .= 'LEFT JOIN `' . _DB_PREFIX_ . 'category_lang` AS `l` ON (l.`id_category` = a.`id_category` AND l.`id_lang` = ' . (int)$this->context->language->id . ')';
        $this->_orderBy = 'path';
        $this->_group .= 'GROUP BY a.`id_ximport_category`';

        parent::getList($id_lang, $order_by, $order_way, $start, $limit, $id_lang_shop);
    }

    public function pathCallback($path)
    {
        return str_replace('###', ' / ', $path);
    }

    public function processUpdate()
    {
        /** @var XImportCategory $object */
        $object = parent::processUpdate();

        if (Tools::isSubmit('submitAdd' . $this->table) && Validate::isLoadedObject($object)) {
            $object->addAdditionalCategory(Tools::getValue('id_additional_category', []));
            $object->id_category = (int)Tools::getValue('id_category');

            return $object->save();
        }

        return $object;
    }

    protected function processBulkImport()
    {
        if (is_array($this->boxes) && !empty($this->boxes))
        {
            $result = true;
            foreach ($this->boxes as $id)
            {
                $class = new $this->className($id);
                $class->import = 1;
                $class->save();
            }

            $this->redirect_after = self::$currentIndex.'&conf=29&token='.$this->token;
        }
        else
            $this->errors[] = Tools::displayError('Musisz wybrać przynajmniej jeden element do zmiany.');

        if (isset($result))
            return $result;
        else
            return false;
    }

    protected function processBulkNoimport()
    {
        if (is_array($this->boxes) && !empty($this->boxes))
        {
            $result = true;
            foreach ($this->boxes as $id)
            {
                $class = new $this->className($id);
                $class->import = 0;
                $class->save();
            }

            $this->redirect_after = self::$currentIndex.'&conf=29&token='.$this->token;
        }
        else
            $this->errors[] = Tools::displayError('Musisz wybrać przynajmniej jeden element do zmiany.');

        if (isset($result))
            return $result;
        else
            return false;
    }

    protected function processBulkMarkup()
    {
        if (is_array($this->boxes) && !empty($this->boxes)) {
            $result = true;
            foreach ($this->boxes as $id) {
                $class = new $this->className((int)$id);
                $class->markup = (float)Tools::getValue('bulk_markup', 0);
                $class->markup_type = Tools::getValue('bulk_markup_type', XImportCategory::MARKUP_PERCENT);
                $class->save();
            }

            $this->redirect_after = self::$currentIndex.'&conf=36&token='.$this->token;
        }
        else
            $this->errors[] = Tools::displayError('Musisz wybrać przynajmniej jeden element do zmiany.');

        if (isset($result)) {
            return $result;
        }
        else {
            return false;
        }
    }

    protected function processBulkTittlePattern()
    {
        if (is_array($this->boxes) && !empty($this->boxes)) {
            $result = true;
            foreach ($this->boxes as $id) {
                $class = new $this->className((int)$id);
                $class->title_pattern = Tools::getValue('bulk_title_pattern', '');
                $class->save();
            }

            $this->redirect_after = self::$currentIndex.'&conf=38&token='.$this->token;
        }
        else
            $this->errors[] = Tools::displayError('Musisz wybrać przynajmniej jeden element do zmiany.');

        if (isset($result)) {
            return $result;
        }
        else {
            return false;
        }
    }

    public function ajaxProcessCategoriesDownload()
    {
        $wholesalerName = Tools::getValue('wholesaler');
        $wholesalerClassName = XImportWholesalers::getWholesalerClassName($wholesalerName);

        require_once (X13_IMPORT_WHOLESALERS_DIR . $wholesalerName . '.php');

        try {
            /** @var XImportWholesalers $wholesaler */
            $wholesaler = new $wholesalerClassName();
            $wholesaler->importCategories();
        }
        catch (Exception $exception) {
            die(json_encode([
                'status' => false,
                'message' => $exception->getMessage()
            ]));
        }

        die(json_encode([
            'status' => true
        ]));
    }

    public function ajaxProcessCategoriesImportForm()
    {
        $categoryTree = (new HelperTreeCategories('categoriesAssigned', $this->l('Wybierz kategorie sklepu do przypisania')))
            ->setInputName('categoriesAssigned')
            ->setRootCategory(Category::getRootCategory()->id)
            ->setUseSearch(true)
            ->setUseCheckBox(false)
            ->render();

        $tpl = $this->context->smarty->createTemplate($this->module->getLocalPath() . 'views/templates/admin/' . $this->tpl_folder . 'categories_import_form.tpl');
        $tpl->assign('categoryTree', $categoryTree);

        die(json_encode([
            'form' => $tpl->fetch()
        ]));
    }

    public function ajaxProcessCategoriesImportCount()
    {
        die(json_encode([
            'count' => XImportCategory::countCategoriesToImport()
        ]));
    }

    public function ajaxProcessCategoriesImport()
    {
        $categories = XImportCategory::getCategoriesToImport((int)Tools::getValue('limit'));

        if (empty($categories)) {
            die(json_encode([
                'status' => true,
                'finish' => true
            ]));
        }

        XImportCategory::importCategories($categories, Category::getRootCategory());

        die(json_encode([
            'status' => true,
            'finish' => false
        ]));
    }

    public function ajaxProcessCategoriesImportSelected()
    {
        switch ((int)Tools::getValue('categoriesImportMode')) {
            case XImportCategory::MODE_IMPORT_TREE:
            case XImportCategory::MODE_IMPORT_LAST_CHILD:
                XImportCategory::importCategories(
                    Tools::getValue('categoriesToAssign'),
                    new Category((int)Tools::getValue('categoriesAssigned')[0]),
                    (int)Tools::getValue('categoriesImportMode'),
                    (int)Tools::getValue('categoriesOverwrite')
                );
                break;

            case XImportCategory::MODE_ASSIGN:
                foreach (Tools::getValue('categoriesToAssign') as $categoryId) {
                    $xCategory = new XImportCategory($categoryId);

                    // do not overwrite assigned categories
                    if (!Tools::getValue('categoriesOverwrite') && $xCategory->id_category) {
                        continue;
                    }

                    $xCategory->addAdditionalCategory(Tools::getValue('categoriesAssigned'));
                    $xCategory->id_category = (int)Tools::getValue('categoriesImportDefault');
                    $xCategory->save();
                }
                break;
        }

        die(json_encode([
            'status' => true
        ]));
    }

    public function ajaxProcessCategoriesRegenerateTree()
    {
        Category::regenerateEntireNtree();

        die(json_encode([
            'status' => true
        ]));
    }

    public function ajaxProcessUpdateMarkup()
    {
        $category = new XImportCategory(Tools::getValue('id'));
        $category->markup = Tools::getValue('markup');
        $category->markup_type = Tools::getValue('markupType');
        $category->save();

        die(json_encode(array(
            'confirmations' => 'Narzut został pomyślnie zapisany.',
            'markup' => $category->markup,
            'markupType' => $category->markup_type
        )));
    }

    public function ajaxProcessUpdateImport()
    {
        $category = new XImportCategory(Tools::getValue('id'));
        $category->import = !$category->import;
        $category->save();

        die(json_encode(array(
            'confirmations' => 'Zmiany zostały pomyślnie zapisane.'
        )));
    }
}
