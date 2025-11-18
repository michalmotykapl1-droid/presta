<?php
/**
 * 2007-2025 PrestaShop
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
 * @author PrestaShop SA <contact@prestashop.com>
 * @copyright  2007-2025 PrestaShop SA
 * @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

// START Debug Logger Inclusion
require_once(_PS_MODULE_DIR_ . 'tvcmssearch/classes/TvcmsSearchLogger.php');
TvcmsSearchLogger::info('TvcmsSearch module main file loaded.');
// END Debug Logger Inclusion

// START MODIFICATION: Add use statements for services
use TvcmsSearch\Services\DietFeatureService;
use TvcmsSearch\Services\CategoryService;
use TvcmsSearch\Services\ManufacturerService;
use TvcmsSearch\Services\ProductSearchService;
use PrestaShop\PrestaShop\Core\Module\WidgetInterface;

class TvcmsSearch extends Module
{
    private $templateFile;

    public $options; // Used to store category structure

    private $optionsCount = 0;

    // Define the ID of the category that should be the header (Products matched to diet)
    const DIET_CATEGORY_ID = 167; // Make sure this ID is correct for your store

    // START MODIFICATION: Add private variables
    /** @var DietFeatureService */
    private $dietFeatureService;

    /** @var CategoryService */
    private $categoryService;

    /** @var ManufacturerService */
    private $manufacturerService;

    /** @var ProductSearchService */
    private $productSearchService;
    // END MODIFICATION

    public function __construct()
    {
        // Ensure _PS_VERSION_ is defined before proceeding
        if (!defined('_PS_VERSION_')) {
            exit;
        }

        // START MODIFICATION: Add manual PSR-4 autoloader
        spl_autoload_register(function ($class) {
            $prefix = 'TvcmsSearch\\';
            if (0 !== strpos($class, $prefix)) {
                return;
            }
            $relative = substr($class, strlen($prefix));
            $file = __DIR__ . '/src/' . str_replace('\\', '/', $relative) . '.php';
            if (file_exists($file)) {
                require_once $file;
            }
        });
        // END MODIFICATION

        $this->name = 'tvcmssearch';
        $this->tab = 'front_office_features';
        $this->author = 'ThemeVolty';
        $this->version = '4.0.0';
        $this->need_instance = 0;

        parent::__construct();

        $this->displayName = 'ThemeVolty - Quick Search';
        $this->description = 'Adds a quick search field to your website.';

        $this->ps_versions_compliancy = ['min' => '1.7', 'max' => _PS_VERSION_];
        $this->module_key = '';

        $this->confirmUninstall = $this->l('Warning: all the data saved in your database will be deleted.' .
            ' Are you sure you want uninstall this module?');
        
        TvcmsSearchLogger::debug('TvcmsSearch constructor finished.');

        // START MODIFICATION: Add service initialization
        $context = Context::getContext();
        $this->dietFeatureService = new DietFeatureService($context);
        $this->categoryService = new CategoryService($context);
        $this->manufacturerService = new ManufacturerService($context);
        $this->productSearchService = new ProductSearchService($context);
        // END MODIFICATION
    }

    public function install()
    {
        \Configuration::updateValue('TVCMSSEARCH_DEBUG_LOG', 0);

        TvcmsSearchLogger::info('Starting module installation.');
        Configuration::updateValue('TVCMSSEARCH_DROPDOWN_THEME', 'classic');
        Configuration::updateValue('TVCMSSEARCH_DROPDOWN_ALIGN', 'left');
        Configuration::updateValue('TVCMSSEARCH_INSTANT_SEARCH', 1);
        Configuration::updateValue('TVCMSSEARCH_SHOW_PRICES', 1);
        Configuration::updateValue('TVCMSSEARCH_SHOW_IMAGES', 1);
        Configuration::updateValue('TVCMSSEARCH_MAX_RESULTS', 8);
        Configuration::updateValue('TVCMSSEARCH_SHOW_CATEGORIES', 1);
        Configuration::updateValue('TVCMSSEARCH_FUZZY_LEVEL', 1);
        Configuration::updateValue('TVCMSSEARCH_WITHIN_WORD', 0);
        Configuration::updateValue('TVCMSSEARCH_ONLY_AVAILABLE', 0);
        Configuration::updateValue('TVCMSSEARCH_CAT_CLICK_MODE', 'ajax');
        Configuration::updateValue('TVCMSSEARCH_SHOW_CAT_COUNT', 1);
        Configuration::updateValue('TVCMSSEARCH_SHOW_DIET', 1);
        Configuration::updateValue('TVCMSSEARCH_SHOW_MANUFACTURER', 1);
        Configuration::updateValue('TVCMSSEARCH_SHOW_DIET_FILTER', 1);
        TvcmsSearchLogger::info('Default configuration values set during installation.');

        if (!$this->installTab('AdminTvCmsSearchConfig', 'Konfiguracja wyszukiwarki', 'IMPROVE')) {
            TvcmsSearchLogger::error('Failed to install AdminTvCmsSearchConfig tab.');
            return false;
        }
        TvcmsSearchLogger::info('AdminTvCmsSearchConfig tab installation attempted.');

        $result = parent::install()
            && $this->registerHook('displayNavSearchBlock')
            && $this->registerHook('displaySearch')
            && $this->registerHook('displayMobileSearchBlock')
            && $this->registerHook('displayHeader');

        if ($result) {
            TvcmsSearchLogger::info('Module installation successful.');
        } else {
            TvcmsSearchLogger::error('Module installation failed at final hook registration step.');
        }
        return $result;
    }

    public function uninstall()
    {
        \Configuration::deleteByName('TVCMSSEARCH_DEBUG_LOG');

        TvcmsSearchLogger::info('Starting module uninstallation.');
        $id_tab = (int)Tab::getIdFromClassName('AdminTvCmsSearchConfig');
        if ($id_tab) {
            $tab = new Tab($id_tab);
            if (!$tab->delete()) {
                TvcmsSearchLogger::error('Failed to delete AdminTvCmsSearchConfig tab during uninstallation.');
                return false;
            }
            TvcmsSearchLogger::info('AdminTvCmsSearchConfig tab deleted during uninstallation.');
        }

        Configuration::deleteByName('TVCMSSEARCH_DROPDOWN_THEME');
        Configuration::deleteByName('TVCMSSEARCH_DROPDOWN_ALIGN');
        Configuration::deleteByName('TVCMSSEARCH_INSTANT_SEARCH');
        Configuration::deleteByName('TVCMSSEARCH_SHOW_PRICES');
        Configuration::deleteByName('TVCMSSEARCH_SHOW_IMAGES');
        Configuration::deleteByName('TVCMSSEARCH_MAX_RESULTS');
        Configuration::deleteByName('TVCMSSEARCH_SHOW_CATEGORIES');
        Configuration::deleteByName('TVCMSSEARCH_FUZZY_LEVEL');
        Configuration::deleteByName('TVCMSSEARCH_WITHIN_WORD');
        Configuration::deleteByName('TVCMSSEARCH_ONLY_AVAILABLE');
        Configuration::deleteByName('TVCMSSEARCH_CAT_CLICK_MODE');
        Configuration::deleteByName('TVCMSSEARCH_SHOW_CAT_COUNT');
        Configuration::deleteByName('TVCMSSEARCH_SHOW_DIET');
        Configuration::deleteByName('TVCMSSEARCH_SHOW_MANUFACTURER');
        Configuration::deleteByName('TVCMSSEARCH_SHOW_DIET_FILTER');
        TvcmsSearchLogger::info('Configuration values deleted during uninstallation.');

        $result = parent::uninstall();
        if ($result) {
            TvcmsSearchLogger::info('Module uninstallation successful.');
        } else {
            TvcmsSearchLogger::error('Module uninstallation failed.');
        }
        return $result;
    }
    
    private function installTab($className, $tabName, $parent = 'IMPROVE')
    {
        TvcmsSearchLogger::info('Attempting to install tab: ' . $className . ' with name ' . $tabName . ' under parent ' . $parent);
        $tab = new Tab();
        $tab->active = 1;
        $tab->class_name = $className;
        $tab->name = array();
        foreach (Language::getLanguages(true) as $lang) {
            $tab->name[$lang['id_lang']] = $tabName;
        }
        $id_parent = Tab::getIdFromClassName($parent);
        if (!$id_parent) {
            TvcmsSearchLogger::error('Parent tab ID not found for class: ' . $parent);
            return false;
        }
        $tab->id_parent = $id_parent;
        $tab->module = $this->name;
        
        if (!$tab->add()) {
            TvcmsSearchLogger::error('Failed to add tab object to database for class: ' . $className);
            return false;
        }
        TvcmsSearchLogger::info('Tab ' . $className . ' added successfully.');
        return true;
    }
    
    public function getContent()
    {
        TvcmsSearchLogger::info('Executing getContent method in TvcmsSearch (main module class).');
        $redirect_link = Context::getContext()->link->getAdminLink('AdminTvCmsSearchConfig', false) . '&token=' . Tools::getAdminTokenLite('AdminTvCmsSearchConfig') . '&conf=4';
        TvcmsSearchLogger::info('Redirecting from getContent to AdminTvCmsSearchConfig: ' . $redirect_link);
        Tools::redirectAdmin($redirect_link);
    }
    
    public function hookdisplayHeader()
    {

        // tvcmssearch: mobile CSS (≤768px)
        if (isset($this->context->controller)) {
            $this->context->controller->registerStylesheet(
                $this->name . '-mobile-css',
                'modules/' . $this->name . '/views/css/tvcmssearch.mobile.css',
                ['media' => 'only screen and (max-width: 768px)', 'priority' => 60]
            );
        }

        // tvcmssearch: mobile JS (init mobile layout; safe on desktop)
        if (isset($this->context->controller)) {
            $this->context->controller->registerJavascript(
                $this->name . '-mobile-js',
                'modules/' . $this->name . '/views/js/tvcmssearch.mobile.js',
                ['position' => 'bottom', 'priority' => 60]
            );
        }


        $this->context->controller->addJqueryUI('ui.autocomplete');
        $this->context->controller->registerJavascript('modules-tvcmssearch', 'modules/'
            . $this->name . '/views/js/tvcmssearch.js', ['position' => 'bottom', 'priority' => 150]);

        // POPRAWKA: Dodajemy brakującą zmienną tvcmssearch_results_url
        Media::addJsDef([
            'tvcmssearch_instant' => (bool)Configuration::get('TVCMSSEARCH_INSTANT_SEARCH'),
            'tvcmssearch_ajax_url' => $this->context->link->getModuleLink($this->name, 'ajax', [], true),
            'tvcmssearch_click_mode' => Configuration::get('TVCMSSEARCH_CAT_CLICK_MODE'),
            'tvcmssearch_results_url' => $this->context->link->getModuleLink($this->name, 'results', [], true),
        ]);
        // pass Presta min search length to JS (non-destructive)
        Media::addJsDef(['tvcmssearch_min_chars' => (int)Configuration::get('PS_SEARCH_MINWORDLEN', 5)]);

        TvcmsSearchLogger::debug('JS variables passed to front office.');

        $this->context->controller->addCSS($this->_path . 'views/css/front.css');
    }

    public function getAjaxResult()
    {
        TvcmsSearchLogger::info('Entering getAjaxResult method.');
        $maxResults = (int)Configuration::get('TVCMSSEARCH_MAX_RESULTS', 8);
        $showPrices = (bool)Configuration::get('TVCMSSEARCH_SHOW_PRICES');
        $showImages = (bool)Configuration::get('TVCMSSEARCH_SHOW_IMAGES');
        $showCategories = (bool) Configuration::get('TVCMSSEARCH_SHOW_CATEGORIES'); 
        $showCatCount = (bool) Configuration::get('TVCMSSEARCH_SHOW_CAT_COUNT');
        $showDiet = (bool) Configuration::get('TVCMSSEARCH_SHOW_DIET');
        
        // ZMIANA: Dodano odczyt nowej opcji
        $showDietFilter = (bool)Configuration::get('TVCMSSEARCH_SHOW_DIET_FILTER');
        
        $onlyAvailable = (bool)Configuration::get('TVCMSSEARCH_ONLY_AVAILABLE', 0);
        
        TvcmsSearchLogger::debug('Config: maxResults=' . $maxResults . ', showPrices=' . ($showPrices ? 'true' : 'false') . ', showImages=' . ($showImages ? 'true' : 'false'));
        TvcmsSearchLogger::debug('Config: showCategories directly after read: ' . ($showCategories ? 'true' : 'false'));
        TvcmsSearchLogger::debug('Config: showCatCount directly after read: ' . ($showCatCount ? 'true' : 'false'));

        $context = Context::getContext();
        $search_words = Tools::getValue('search_words');
        $category_id = Tools::getValue('category_id');
        $cat_id = trim($category_id);
        TvcmsSearchLogger::debug('Received search_words: "' . $search_words . '", category_id: "' . $cat_id . '"');

        $id_lang = $this->context->language->id;
        
        // ================================================================
        // START POPRAWKI: Zmiana sortowania z 'position' na 'weight'
        // ================================================================
        // Zmienia to sortowanie z domyślnej pozycji w katalogu na sortowanie 
        // według trafności wyszukiwania.
        $products = $this->productSearchService->getProducts(
            $search_words,
            1,
            99999,
            'weight', // <-- POPRAWKA: Było 'position'
            'desc',
            false,
            true,
            $onlyAvailable 
        );
        // ================================================================
        // KONIEC POPRAWKI
        // ================================================================

        $productCategories = $this->productSearchService->getCategoriesFromProducts($products);
        
        
        
        // MOBILE TOP CATEGORIES (server-side) — only when explicit mobile=1
        $tv_is_mobile = (bool)Tools::getValue('mobile');
        if ($tv_is_mobile && is_array($productCategories)) {
            usort($productCategories, function($a,$b){
                $ac = isset($a['product_count']) ? (int)$a['product_count'] : 0;
                $bc = isset($b['product_count']) ? (int)$b['product_count'] : 0;
                if ($ac === $bc) {
                    $an = isset($a['name']) ? Tools::strtolower($a['name']) : '';
                    $bn = isset($b['name']) ? Tools::strtolower($b['name']) : '';
                    return strcmp($an,$bn);
                }
                return ($bc <=> $ac);
            });
            $productCategories = array_slice($productCategories, 0, 5);
        }
// SORT PATCH 2025-10-06: sort categories by product_count DESC
        if (is_array($productCategories)) {
            usort($productCategories, function($a, $b) {
                $ac = isset($a['product_count']) ? (int)$a['product_count'] : 0;
                $bc = isset($b['product_count']) ? (int)$b['product_count'] : 0;
                if ($ac === $bc) {
                    // optional secondary sort by name ASC
                    $an = isset($a['name']) ? Tools::strtolower($a['name']) : '';
                    $bn = isset($b['name']) ? Tools::strtolower($b['name']) : '';
                    return strcmp($an, $bn);
                }
                return ($bc <=> $ac);
            });
        }
// ZMIANA: Ta linia jest teraz opcjonalna, aby nie mylić z nowymi filtrami.
        // Można ją usunąć, jeśli nie jest potrzebna.
        $dietCategories = $this->productSearchService->getDietaryPreferencesFromProducts($products);
        TvcmsSearchLogger::debug('Dynamically fetched Dietary Categories: ' . json_encode($dietCategories));

        // ZMIANA: Pobieranie danych o cechach dietetycznych
        $diet_features_data = [];
        if ($showDietFilter) {
            $diet_features_data = $this->dietFeatureService->getFeaturesForProducts($products);
        }

        $search_controller_url = $this->context->link->getPageLink('search', null, null, null, false, null, true);
        $this->context->smarty->assign('search_controller_url', $search_controller_url);

        $this->context->smarty->assign([
            'products' => $products,
            'options'  => [
                'categories'      => $productCategories,
                'diet_categories' => $dietCategories, // Nadal przekazywane, jeśli są używane gdzie indziej
            ],
            'showCategories' => $showCategories,
            'showCatCount'   => $showCatCount,
            'showDiet'       => $showDiet,
            // ZMIANA: Przekazanie danych o cechach do szablonu
            'showDietFilter' => $showDietFilter,
            'diet_features'  => $diet_features_data['unique_features'] ?? [],
        ]);

        $return_data = [];
        if (!empty($products)) { 
            foreach ($products as $product) {
                $add_product_to_results = false; 

                if ('undefined' != $cat_id && '0' != $cat_id) {
                    $target_category_id = (int)$cat_id;
                    $product_direct_categories = Product::getProductCategories($product['id_product']);
                    if (in_array($target_category_id, $product_direct_categories)) {
                        $add_product_to_results = true;
                    } else {
                        foreach ($product_direct_categories as $prod_cat_id) {
                            $category_obj = new Category($prod_cat_id, $context->language->id, $context->shop->id);
                            $category_parents = $category_obj->getParentsCategories($context->language->id);
                            foreach ($category_parents as $parent_cat) {
                                if ((int)$parent_cat['id_category'] === $target_category_id) {
                                    $add_product_to_results = true;
                                    break 2; 
                                }
                            }
                        }
                    }
                } else {
                    $add_product_to_results = true;
                }

                if ($add_product_to_results) {
                    $return_data[$product['id_product']] = $product;
                    if ($showImages) {
                        $image = Image::getCover($product['id_product']);
                        if ($image && isset($image['id_image'])) {
                            $img_type = ImageType::getFormattedName('small');
                            $tmp = $context->link->getImageLink($product['link_rewrite'], $image['id_image'], $img_type);
                            $return_data[$product['id_product']]['cover_image'] = $tmp;
                        }
                    }
                }
            }
        }
        
        $html = ''; 
        $result_data = [];
        $result_data['total'] = count($return_data); 
        
        if (!empty($return_data)) {
            $i = 1;
            foreach ($return_data as $data) {
                if ($i <= $maxResults) {
                    $prod_name = $data['name'];
                    $prod_link = $data['link'];
                    $categoriesHtml = '';

                    if ($showCategories) {
                        // Używamy $this->categoryService zamiast starej metody
                        $catNames = $this->categoryService->getCategoryNamesForProduct((int)$data['id_product']);
                        if (!empty($catNames)) {
                            $categoriesHtml = '<div class="tvsearch-dropdown-categories">' . implode(', ', $catNames) . '</div>';
                        }
                    }
                    
                    $image_html = '';
                    if ($showImages && isset($data['cover_image'])) {
                        $image_html = '<div class=\'tvsearch-dropdown-img-block\'><img src=\'' . $data['cover_image'] . '\' alt=\'' . $prod_name . '\' /></div>';
                    }

                    $price_html = '';
                    if ($showPrices) {
                        if (isset($data['specific_prices']) && !empty($data['specific_prices'])) {
                            $new_price = Tools::displayPrice($data['price']);
                            $old_price = Tools::displayPrice($data['price_without_reduction']);
                            $price_html = '<span class=\'price\'>' . $new_price . '</span><span class=\'regular-price\'>' . $old_price . '</span>';
                        } else {
                            $new_price = Tools::displayPrice($data['price']);
                            $price_html = '<div class=\'price\'>' . $new_price . '</div>';
                        }
                        $price_html = '<div class=\'product-price-and-shipping\'>' . $price_html . '</div>';
                    }

                    // ZMIANA: Dodanie atrybutu data-feature-values do HTML produktu
                    $feature_values_string = '';
                    if ($showDietFilter && isset($diet_features_data['product_features_map'][$data['id_product']])) {
                        $feature_values_string = implode(',', $diet_features_data['product_features_map'][$data['id_product']]);
                    }
                    $data_attribute = 'data-feature-values=\'' . $feature_values_string . '\'';

                    $html .= '<div class=\'tvsearch-dropdown-wrapper clearfix\' ' . $data_attribute . '><a href=\'' . $prod_link . '\'>' . $image_html . '<div class=\'tvsearch-dropdown-content-box\'><div class=\'tvsearch-dropdown-title\'>' . $prod_name . '</div>' . $price_html . $categoriesHtml . '</div></a></div>';
                    ++$i;
                } else {
                    break; 
                }
            }
        }

        if (!empty($html)) {
            $result_data['html'] = $html; 
            $this->context->smarty->assign('result_data', $result_data);
            return $this->display(__FILE__, 'views/templates/front/display_ajax_result.tpl');
        } else {
            $result_data['html'] = ''; 
            $this->context->smarty->assign('result_data', $result_data);
            return $this->display(__FILE__, 'views/templates/front/display_ajax_result.tpl');
        }
    }

    private function assignTemplateVariables()
    {
        $showCategories = (bool) Configuration::get('TVCMSSEARCH_SHOW_CATEGORIES');
        $showDiet = (bool) Configuration::get('TVCMSSEARCH_SHOW_DIET');
        $search_controller_url = $this->context->link->getPageLink('search', null, null, null, false, null, true);

        $this->context->smarty->assign([
            'options' => [
                'categories'      => [],
                'diet_categories' => [],
            ],
            'search_controller_url' => $search_controller_url,
            'showCategories'        => $showCategories,
            'showDiet'              => $showDiet,
        ]);
    }

    public function hookdisplayNavSearchBlock()
    {
        TvcmsSearchLogger::info('Executing hookdisplayNavSearchBlock.');
        $this->assignTemplateVariables();
        return $this->display(__FILE__, 'views/templates/front/display_search.tpl');
    }

    public function hookdisplaySearch()
    {

        // tvcmssearch: pass min search length from Presta to JS
        if (class_exists('Media')) {
            Media::addJsDef([
                'tvcmssearch_min_chars' => (int)Configuration::get('PS_SEARCH_MINWORDLEN', 5),
            ]);
        }

        // tvcmssearch: register mobile-only CSS (≤768px)
        if (Context::getContext()->controller) {
            Context::getContext()->controller->registerStylesheet(
                'tvcmssearch-mobile-css',
                'modules/'.$this->name.'/views/css/tvcmssearch.mobile.css',
                ['media' => 'only screen and (max-width: 768px)', 'priority' => 200]
            );
        }

        // tvcmssearch: register mobile-only JS
        if (Context::getContext()->controller) {
            Context::getContext()->controller->registerJavascript(
                'tvcmssearch-mobile-js',
                'modules/'.$this->name.'/views/js/tvcmssearch.mobile.js',
                ['position' => 'bottom', 'priority' => 200]
            );
        }


        TvcmsSearchLogger::info('Executing hookdisplaySearch.');
        $this->assignTemplateVariables();
        return $this->display(__FILE__, 'views/templates/front/display_search.tpl');
    }

    public function hookdisplayMobileSearchBlock()
    {
        TvcmsSearchLogger::info('Executing hookdisplayMobileSearchBlock.');
        $this->assignTemplateVariables();
        return $this->display(__FILE__, 'views/templates/front/display_mobile_search.tpl');
    }
}