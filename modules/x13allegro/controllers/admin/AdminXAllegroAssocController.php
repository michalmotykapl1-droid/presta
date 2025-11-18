<?php
/**
 * Plik kontrolera AdminXAllegroAssocController
 */

require_once (dirname(__FILE__) . '/../../x13allegro.php');

final class AdminXAllegroAssocController extends XAllegroController
{
    private $categoriesController;
    private $manufacturersController;

    public function __construct()
    {
        $this->table = 'xallegro_configuration';
        $this->identifier = 'id_xallegro_configuration';
        $this->className = 'XAllegroConfiguration';

        parent::__construct();

        $this->categoriesController = new AdminXAllegroAssocCategoriesController();
        $this->categoriesController->token = $this->token;
        $this->categoriesController->init();

        $this->manufacturersController = new AdminXAllegroAssocManufacturersController();
        $this->manufacturersController->token = $this->token;
        $this->manufacturersController->init();

        $this->tabAccess = Profile::getProfileAccess($this->context->employee->id_profile, Tab::getIdFromClassName('AdminXAllegroAssoc'));
    }

    public function postProcess()
    {
        foreach ($_GET as $get => $value) {
            if (preg_match('/^((?!id_).*)xallegro_(.*)$/', $get, $m)) {
                if ($m[2] == 'category') {
                    $controller = $this->context->link->getAdminLink('AdminXAllegroAssocCategories');
                    $identifier = $this->categoriesController->identifier;
                }
                else if ($m[2] == 'manufacturer') {
                    $controller = $this->context->link->getAdminLink('AdminXAllegroAssocManufacturers');
                    $identifier = $this->manufacturersController->identifier;
                }
                else {
                    continue;
                }

                $url = $controller . '&' . $get . '&' . $identifier . '=' . Tools::getValue($identifier);
                if (strpos($m[1], 'submitBulk') !== false) {
                    unset($_POST['token']);
                    $url .= '&' . http_build_query($_POST);
                }

                Tools::redirectAdmin($url);
            }
            unset($m);
        }

        return parent::postProcess();
    }

    public function initPageHeaderToolbar()
    {
        if (empty($this->display))
        {
            $this->page_header_toolbar_btn['allegro_current'] = array(
                'href' => $this->context->link->getAdminLink('AdminXAllegroAssocCategories') . '&add' . $this->categoriesController->table,
                'desc' => $this->l('Dodaj powiązanie kategorii'),
                'icon' => 'process-icon-new'
            );

            $this->page_header_toolbar_btn['allegro_sold'] = array(
                'href' => $this->context->link->getAdminLink('AdminXAllegroAssocManufacturers') . '&add' . $this->manufacturersController->table,
                'desc' => $this->l('Dodaj powiązanie producenta'),
                'icon' => 'process-icon-new'
            );
        }

        parent::initPageHeaderToolbar();
    }

    public function init()
    {
        parent::init();
        $this->getFieldsOptions();
    }

    public function beforeUpdateOptions()
    {
        $this->redirect_after = $this->context->link->getAdminLink('AdminXAllegroAssoc') . '&conf=6';
    }

    private function getFieldsOptions()
    {
        $this->fields_options = array(
            'general' => array(
                'title' =>	$this->l('Ustawienia globalnych powiązań'),
                'image' => false,
                'description' => $this->l('Dla poniższych parametrów moduł spróbuje ustawić wartość automatycznie, bazując na danych produktu PrestaShop.'),
                'fields' =>	array(
                    'AUCTION_USE_EAN' => array(
                        'title' => $this->l('Automatyczne powiązanie pola "EAN"'),
                        'type' => 'bool'
                    ),
                    'PARAMETERS_GLOBAL_ISBN' => array(
                        'title' => $this->l('Automatyczne powiązanie pola "ISBN"'),
                        'desc' => (version_compare(_PS_VERSION_, '1.7.0.0', '<')
                            ? $this->l('Opcja dostępna od PrestaShop 1.7')
                            : $this->l('Bazuje na polu "ISBN" z PrestaShop. Powiązuje pola "ISBN" oraz "ISSN".')),
                        'type' => 'bool',
                        'disabled' => version_compare(_PS_VERSION_, '1.7.0.0', '<')
                    ),
                    'PARAMETERS_GLOBAL_CONDITION' => array(
                        'title' => $this->l('Automatyczne powiązanie pola "Stan"'),
                        'type' => 'bool',
                        'desc' => $this->l('Bazuje na polu "Stan" z PrestaShop. Dotyczy tylko wartości "Nowy" i "Używany".')
                    ),
                    'PARAMETERS_GLOBAL_REFERENCE' => array(
                        'title' => $this->l('Automatyczne powiązanie pola "Kod producenta"'),
                        'type' => 'bool',
                        'desc' => $this->l('Bazuje na polu "Indeks" z PrestaShop. Powiązuje pola "Kod producenta", "Numer katalogowy", "Numer katalogowy producenta", "Numer katalogowy części".')
                    ),
                    'PARAMETERS_GLOBAL_MANUFACTURER' => array(
                        'title' => $this->l('Automatyczne powiązanie pola "Producent"'),
                        'type' => 'bool',
                        'desc' => $this->l('Bazuje na polu "Marka/Producent" z PrestaShop. Powiązuje pola "Marka", "Producent", "Producent części".')
                    )
                ),
                'submit' => array(
                    'title' => $this->l('Zapisz')
                )
            )
        );
    }

    public function initContent()
    {
        parent::initContent();
        $messages = [
            'confirmations',
            'informations',
            'warnings',
            'errors'
        ];
        foreach ($messages as $message) {
            $this->{$message} = array_merge(
                $this->{$message},
                $this->categoriesController->{$message},
                $this->manufacturersController->{$message}
            );
        }
    }

    public function renderList()
    {
        return $this->renderCategoryMatcherPanel() .
            $this->categoriesController->renderList() .
            $this->manufacturersController->renderList();
    }

    protected function renderCategoryMatcherPanel()
    {
        $this->context->smarty->assign([
            'admin_link' => $this->context->link->getAdminLink('AdminXAllegroAssoc'),
        ]);
        return $this->context->smarty->fetch(_PS_MODULE_DIR_.'x13allegro/views/templates/admin/x_allegro_assoc/category_matcher.tpl');
    }

    public function ajaxProcessSearchPsCategories()
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $q = trim((string)Tools::getValue('q', ''));
            $idLang = (int)$this->context->language->id;
            $limit = (int)Tools::getValue('limit', 20);
            $rows = [];
            if ($q !== '' && ctype_digit($q)) {
                $sql = new \DbQuery();
                $sql->select('c.id_category, cl.name, c.id_parent');
                $sql->from('category', 'c');
                $sql->innerJoin('category_lang', 'cl', 'cl.id_category=c.id_category AND cl.id_lang='.(int)$idLang.\Shop::addSqlRestrictionOnLang('cl'));
                $sql->where('c.id_category='.(int)$q);
                $row = \Db::getInstance()->getRow($sql);
                if ($row) { $rows[] = $row; }
            }
            if ($q !== '') {
                $sql2 = new \DbQuery();
                $sql2->select('c.id_category, cl.name, c.id_parent');
                $sql2->from('category', 'c');
                $sql2->innerJoin('category_lang', 'cl', 'cl.id_category=c.id_category AND cl.id_lang='.(int)$idLang.\Shop::addSqlRestrictionOnLang('cl'));
                $sql2->where('cl.name LIKE "%'.pSQL($q).'%"');
                $sql2->orderBy('cl.name ASC');
                $sql2->limit($limit);
                $res = \Db::getInstance()->executeS($sql2);
                if (is_array($res)) {
                    $seen = array_column($rows, 'id_category');
                    foreach ($res as $r) {
                        if (!in_array((int)$r['id_category'], array_map('intval',$seen), true)) {
                            $rows[] = $r;
                        }
                    }
                }
            }
            $out = [];
            foreach ($rows as $r) {
                $out[] = [
                    'id'   => (int)$r['id_category'],
                    'path' => $this->buildPsCategoryPath((int)$r['id_category'], $idLang),
                ];
            }
            die(json_encode(['success'=>true, 'items'=>$out]));
        } catch (\Exception $e) {
            die(json_encode(['success'=>false, 'message'=>(string)$e]));
        }
    }

    protected function buildPsCategoryPath($idCategory, $idLang)
    {
        $path = [];
        $current = (int)$idCategory;
        for ($i=0; $i<12 && $current>0; $i++) {
            $sql = new \DbQuery();
            $sql->select('c.id_parent, cl.name');
            $sql->from('category', 'c');
            $sql->innerJoin('category_lang', 'cl', 'cl.id_category=c.id_category AND cl.id_lang='.(int)$idLang.\Shop::addSqlRestrictionOnLang('cl'));
            $sql->where('c.id_category='.(int)$current);
            $row = \Db::getInstance()->getRow($sql);
            if (!$row) break;
            array_unshift($path, trim((string)$row['name']));
            $current = (int)$row['id_parent'];
            if ($current==0 || $current==1) break;
        }
        return implode(' > ', $path);
    }

    public function ajaxProcessGetPsCategoryTree()
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $idLang = (int)$this->context->language->id;
            $idShop = (int)$this->context->shop->id;
            $sql = new \DbQuery();
            $sql->select('c.id_category, c.id_parent, c.active, cl.name');
            $sql->from('category', 'c');
            $sql->innerJoin('category_lang', 'cl', 'cl.id_category=c.id_category AND cl.id_lang='.(int)$idLang.\Shop::addSqlRestrictionOnLang('cl'));
            $sql->orderBy('c.id_parent ASC, cl.name ASC');
            $rows = \Db::getInstance()->executeS($sql);

            $sqlMap = new \DbQuery();
            $sqlMap->select('id_categories'); 
            $sqlMap->from('xallegro_category'); 
            $mapRows = \Db::getInstance()->executeS($sqlMap);
            $mapped = [];
            foreach ((array)$mapRows as $r) {
                $mapped[(int)$r['id_categories']] = true;
            }
            
            $nodes = []; $children = [];
            foreach ((array)$rows as $r) {
                $id = (int)$r['id_category']; $parent = (int)$r['id_parent'];
                $nodes[$id] = ['id'=>$id,'name'=>(string)$r['name'],'parent'=>$parent,'active'=>(int)$r['active'],'mapped'=>!empty($mapped[$id])];
                $children[$parent][] = $id;
            }
            $shopRoot = (int)\Db::getInstance()->getValue('SELECT id_category FROM `'._DB_PREFIX_.'shop` WHERE id_shop='.(int)$idShop);
            if ($shopRoot <= 0) { $shopRoot = (int)\Configuration::get('PS_ROOT_CATEGORY'); }
            if ($shopRoot <= 0) { $shopRoot = 2; }
            $build = function($parent) use (&$build,&$children,&$nodes){
                $arr=[];
                if (!empty($children[$parent])) {
                    foreach ($children[$parent] as $cid) {
                        if (empty($nodes[$cid])) continue;
                        $n=$nodes[$cid];
                        $arr[] = ['id'=>$n['id'],'name'=>$n['name'],'mapped'=>(bool)$n['mapped'],'active'=>(bool)$n['active'],'children'=>$build($cid)];
                    }
                }
                return $arr;
            };
            $tree = $build($shopRoot);
            die(json_encode(['success'=>true, 'tree'=>$tree]));
        } catch (\Exception $e) {
            die(json_encode(['success'=>false, 'message'=>(string)$e]));
        }
    }

    public function ajaxProcessSuggestAllegroCategories()
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $id_category = (int)Tools::getValue('id_category');
            $use_products = (bool)Tools::getValue('use_products', false);
            $use_ean = (bool)Tools::getValue('use_ean', false);
            $limit = (int)Tools::getValue('limit', 8);
            $debug = (bool)Tools::getValue('debug', false);
            $ean_limit = (int)Tools::getValue('ean_limit', 5);
            if ($ean_limit <= 0) { $ean_limit = 5; } elseif ($ean_limit > 200) { $ean_limit = 200; }

            if ($id_category <= 0) {
                die(json_encode(['success' => false, 'message' => 'Brak id_category']));
            }
            
            require_once _PS_MODULE_DIR_.'x13allegro/classes/php81/Service/CategoryMatcherService.php';

            // --- POCZĄTEK POPRAWKI ---
            // Zmieniamy drugi argument z `$this->module` na `null`, aby serwis sam tworzył poprawne połączenie API.
            $svc = new \x13allegro\Service\CategoryMatcherService($this->context->shop->id, null, 'allegro-pl');
            // --- KONIEC POPRAWKI ---
            $result = $svc->suggestForCategory($id_category, $use_products, $limit, $use_ean, $debug, $ean_limit);
            
            die(json_encode(['success' => true, 'suggestions' => $result['items'], 'debug'=>$result['debug']]));
        } catch (\Exception $e) {
            die(json_encode(['success' => false, 'message' => 'Błąd krytyczny: ' . $e->getMessage()]));
        }
    }

    public function ajaxProcessSaveCategoryMapping()
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $id_category = (int)Tools::getValue('id_category');
            $allegro_cat = Tools::getValue('allegro_category_id');
            $confidence = (float)Tools::getValue('confidence', 0);
            $source = Tools::getValue('source', 'name');
            $allegro_cat_path = Tools::getValue('allegro_category_path', '');

            if ($id_category <= 0 || !$allegro_cat) {
                die(json_encode(['success' => false, 'message' => 'Brak danych do zapisu']));
            }

            require_once _PS_MODULE_DIR_.'x13allegro/classes/php81/Service/CategoryMatcherService.php';
            
            // --- POCZĄTEK POPRAWKI ---
            $svc = new \x13allegro\Service\CategoryMatcherService($this->context->shop->id, null, 'allegro-pl');
            // --- KONIEC POPRAWKI ---
            
            $svc->saveMapping($id_category, $allegro_cat, $confidence, $source, $allegro_cat_path);

            die(json_encode(['success' => true]));
        } catch (\Exception $e) {
            die(json_encode(['success' => false, 'message' => (string)$e]));
        }
    }

    public function ajaxProcessUpdateAllegroCategories()
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            require_once _PS_MODULE_DIR_.'x13allegro/classes/php81/Service/CategoryMatcherService.php';
            // --- POCZĄTEK POPRAWKI ---
            $svc = new \x13allegro\Service\CategoryMatcherService($this->context->shop->id, null, 'allegro-pl');
            // --- KONIEC POPRAWKI ---
            
            $result = $svc->updateAndCacheAllegroTree();

            if ($result['success']) {
                die(json_encode(['success' => true, 'message' => 'Kategorie zaktualizowane. Zapisano ' . $result['count'] . ' kategorii.']));
            } else {
                die(json_encode(['success' => false, 'message' => $result['message']]));
            }
        } catch (\Exception $e) {
            die(json_encode(['success' => false, 'message' => 'Błąd krytyczny: ' . $e->getMessage()]));
        }
    }

    public function ajaxProcessAutoMapCategory()
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $idCategory = (int)\Tools::getValue('id_category');
            $save = (bool)\Tools::getValue('save', false);

            if ($idCategory <= 0) {
                http_response_code(400);
                die(json_encode(['success'=>false,'message'=>'Brak id_category']));
            }

            require_once _PS_MODULE_DIR_ . 'x13allegro/classes/php81/Service/CategoryAutoMapperService.php';
            $svc = new \x13allegro\Service\CategoryAutoMapperService();
            $res = $svc->autoMapPsCategory($idCategory, 80, 30, $save);

            die(json_encode($res));
        } catch (\Throwable $e) {
            http_response_code(500);
            die(json_encode(['success'=>false,'message'=>$e->getMessage()]));
        }
    }


}