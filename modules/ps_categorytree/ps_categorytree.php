<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://devdocs.prestashop.com/ for more information.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

use PrestaShop\PrestaShop\Core\Module\WidgetInterface;

class Ps_CategoryTree extends Module implements WidgetInterface
{
    /**
     * @var int A way to display the category tree: Home category
     */
    const CATEGORY_ROOT_HOME = 0;

    /**
     * @var int A way to display the category tree: Current category
     */
    const CATEGORY_ROOT_CURRENT = 1;

    /**
     * @var int A way to display the category tree: Parent category
     */
    const CATEGORY_ROOT_PARENT = 2;

    /**
     * @var int A way to display the category tree: Current category and its parent (if exists)
     */
    const CATEGORY_ROOT_CURRENT_PARENT = 3;

    public function __construct()
    {
        $this->name = 'ps_categorytree';
        $this->tab = 'front_office_features';
        $this->version = '3.0.1';
        $this->author = 'PrestaShop';

        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = $this->trans('Category tree links', [], 'Modules.Categorytree.Admin');
        $this->description = $this->trans('Help navigation on your store, show your visitors current category and subcategories.', [], 'Modules.Categorytree.Admin');
        $this->ps_versions_compliancy = ['min' => '1.7.7.0', 'max' => _PS_VERSION_];
    }

    public function install()
    {
        return parent::install() && $this->registerHook('displayLeftColumn');
    }

    public function uninstall()
    {
        if (!parent::uninstall() ||
            !Configuration::deleteByName('BLOCK_CATEG_MAX_DEPTH') ||
            !Configuration::deleteByName('BLOCK_CATEG_ROOT_CATEGORY')) {
            return false;
        }

        return true;
    }

    public function getContent()
    {
        $output = '';
        if (Tools::isSubmit('submitBlockCategories')) {
            $maxDepth = (int) (Tools::getValue('BLOCK_CATEG_MAX_DEPTH'));
            if ($maxDepth < 0) {
                $output .= $this->displayError($this->trans('Maximum depth: Invalid number.', [], 'Admin.Notifications.Error'));
            } else {
                Configuration::updateValue('BLOCK_CATEG_MAX_DEPTH', (int) $maxDepth);
                Configuration::updateValue('BLOCK_CATEG_SORT_WAY', Tools::getValue('BLOCK_CATEG_SORT_WAY'));
                Configuration::updateValue('BLOCK_CATEG_SORT', Tools::getValue('BLOCK_CATEG_SORT'));
                Configuration::updateValue('BLOCK_CATEG_ROOT_CATEGORY', Tools::getValue('BLOCK_CATEG_ROOT_CATEGORY'));

                Tools::redirectAdmin(AdminController::$currentIndex . '&configure=' . $this->name . '&token=' . Tools::getAdminTokenLite('AdminModules') . '&conf=6');
            }
        }

        return $output . $this->renderForm();
    }

    /**
     * Format category into an array compatible with existing templates.
     * ZMIANA: Dodajemy parametr $currentCategoryId
     */
    private function formatCategory($rawCategory, $idsOfCategoriesInPath, $currentCategoryId): array
    {
        $children = [];
        if (!empty($rawCategory['children'])) {
            foreach ($rawCategory['children'] as $k => $v) {
                // ZMIANA: Przekazujemy $currentCategoryId rekurencyjnie
                $children[$k] = $this->formatCategory($v, $idsOfCategoriesInPath, $currentCategoryId);
            }
        }

        return [
            'id' => $rawCategory['id_category'],
            'link' => $this->context->link->getCategoryLink($rawCategory['id_category'], $rawCategory['link_rewrite']),
            'name' => $rawCategory['name'],
            'desc' => $rawCategory['description'],
            'children' => $children,
            'in_path' => in_array($rawCategory['id_category'], $idsOfCategoriesInPath),
            // ZMIANA: Dodajemy flagę 'current'
            'current' => ((int)$rawCategory['id_category'] === (int)$currentCategoryId),
        ];
    }

    // POPRAWKA BŁĘDU SQL: Przywrócenie oryginalnej logiki PrestaShop dla $maxdepth
    private function getCategories($category): array
    {
        if (null === $category) {
            return [];
        }

        // Determine max depth to get categories
        $maxdepth = (int) Configuration::get('BLOCK_CATEG_MAX_DEPTH');
        
        // POPRAWKA: Używamy oryginalnej logiki PrestaShop do obliczania głębokości
        if ($maxdepth > 0) {
            $maxdepth += $category->level_depth;
        }

        // Define filters to get categories
        $groups = Customer::getGroupsStatic((int) $this->context->customer->id);
        
        // POPRAWKA: Używamy oryginalnej logiki PrestaShop dla $sqlFilter
        $sqlFilter = $maxdepth ? 'AND c.`level_depth` <= ' . (int) $maxdepth : '';
        $orderBy = ' ORDER BY c.`level_depth` ASC, ' . (Configuration::get('BLOCK_CATEG_SORT') ? 'cl.`name`' : 'category_shop.`position`') . ' ' . (Configuration::get('BLOCK_CATEG_SORT_WAY') ? 'DESC' : 'ASC');

        // Retrieve them using the built in method (teraz z poprawnym $sqlFilter)
        $categories = Category::getNestedCategories($category->id, $this->context->language->id, true, $groups, true, $sqlFilter, $orderBy);
        if (empty($categories)) {
            return [];
        }

        // Get path to current category so we can use it for marking
        $idsOfCategoriesInPath = $this->getIdsOfCategoriesInPathToCurrentCategory();
        // Pobieramy ID aktualnej kategorii
        $currentCategory = $this->getCurrentCategory();
        $currentCategoryId = $currentCategory ? (int)$currentCategory->id : 0; 

        // And do our formatting
        $formattedCategories = [];
        // POPRAWKA: Upewniamy się, że iterujemy po właściwej gałęzi
        if (isset($categories[$category->id])) { 
            foreach ($categories[$category->id]['children'] as $k => $v) { 
                $formattedCategories[$k] = $this->formatCategory($v, $idsOfCategoriesInPath, $currentCategoryId);
            }
        } elseif (!empty($categories)) {
             // Fallback, jeśli getNestedCategories zwróciło tylko dzieci (w niektórych wersjach PS)
            foreach ($categories as $k => $v) {
                $formattedCategories[$k] = $this->formatCategory($v, $idsOfCategoriesInPath, $currentCategoryId);
            }
        }
        
        return $formattedCategories;
    }

    public function renderForm()
    {
        $fields_form = [
            'form' => [
                'legend' => [
                    'title' => $this->trans('Settings', [], 'Admin.Global'),
                    'icon' => 'icon-cogs',
                ],
                'input' => [
                    [
                        'type' => 'radio',
                        'label' => $this->trans('Category root', [], 'Modules.Categorytree.Admin'),
                        'name' => 'BLOCK_CATEG_ROOT_CATEGORY',
                        'hint' => $this->trans('Select which category is displayed in the block. The current category is the one the visitor is currently browsing.', [], 'Modules.Categorytree.Admin'),
                        'values' => [
                            [
                                'id' => 'home',
                                'value' => static::CATEGORY_ROOT_HOME,
                                'label' => $this->trans('Home category', [], 'Modules.Categorytree.Admin'),
                            ],
                            [
                                'id' => 'current',
                                'value' => static::CATEGORY_ROOT_CURRENT,
                                'label' => $this->trans('Current category', [], 'Modules.Categorytree.Admin'),
                            ],
                            [
                                'id' => 'parent',
                                'value' => static::CATEGORY_ROOT_PARENT,
                                'label' => $this->trans('Parent category', [], 'Modules.Categorytree.Admin'),
                            ],
                            [
                                'id' => 'current_parent',
                                'value' => static::CATEGORY_ROOT_CURRENT_PARENT,
                                'label' => $this->trans('Current category, unless it has no subcategories, in which case the parent category of the current category is used', [], 'Modules.Categorytree.Admin'),
                            ],
                        ],
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->trans('Maximum depth', [], 'Modules.Categorytree.Admin'),
                        'name' => 'BLOCK_CATEG_MAX_DEPTH',
                        'desc' => $this->trans('Set the maximum depth of category sublevels displayed in this block (0 = infinite).', [], 'Modules.Categorytree.Admin'),
                    ],
                    [
                        'type' => 'radio',
                        'label' => $this->trans('Sort', [], 'Admin.Actions'),
                        'name' => 'BLOCK_CATEG_SORT',
                        'values' => [
                            [
                                'id' => 'name',
                                'value' => 1,
                                'label' => $this->trans('By name', [], 'Admin.Global'),
                            ],
                            [
                                'id' => 'position',
                                'value' => 0,
                                'label' => $this->trans('By position', [], 'Admin.Global'),
                            ],
                        ],
                    ],
                    [
                        'type' => 'radio',
                        'label' => $this->trans('Sort order', [], 'Admin.Actions'),
                        'name' => 'BLOCK_CATEG_SORT_WAY',
                        'values' => [
                            [
                                'id' => 'name',
                                'value' => 1,
                                'label' => $this->trans('Descending', [], 'Admin.Global'),
                            ],
                            [
                                'id' => 'position',
                                'value' => 0,
                                'label' => $this->trans('Ascending', [], 'Admin.Global'),
                            ],
                        ],
                    ],
                ],
                'submit' => [
                    'title' => $this->trans('Save', [], 'Admin.Actions'),
                ],
            ],
        ];

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->submit_action = 'submitBlockCategories';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = [
            'fields_value' => $this->getConfigFieldsValues(),
        ];

        return $helper->generateForm([$fields_form]);
    }

    public function getConfigFieldsValues()
    {
        return [
            'BLOCK_CATEG_MAX_DEPTH' => Tools::getValue('BLOCK_CATEG_MAX_DEPTH', Configuration::get('BLOCK_CATEG_MAX_DEPTH')),
            'BLOCK_CATEG_SORT_WAY' => Tools::getValue('BLOCK_CATEG_SORT_WAY', Configuration::get('BLOCK_CATEG_SORT_WAY')),
            'BLOCK_CATEG_SORT' => Tools::getValue('BLOCK_CATEG_SORT', Configuration::get('BLOCK_CATEG_SORT')),
            'BLOCK_CATEG_ROOT_CATEGORY' => Tools::getValue('BLOCK_CATEG_ROOT_CATEGORY', Configuration::get('BLOCK_CATEG_ROOT_CATEGORY')),
        ];
    }

    public function renderWidget($hookName = null, array $configuration = [])
    {
        $this->smarty->assign($this->getWidgetVariables($hookName, $configuration));

        return $this->fetch('module:ps_categorytree/views/templates/hook/ps_categorytree.tpl');
    }

    public function getWidgetVariables($hookName = null, array $configuration = [])
    {
        switch ((int)Configuration::get('BLOCK_CATEG_ROOT_CATEGORY')) { // Używamy (int) dla pewności
            case static::CATEGORY_ROOT_HOME:
                $rootCategory = $this->getHomeCategory();
                break;
            case static::CATEGORY_ROOT_CURRENT:
                $rootCategory = $this->getCurrentCategory();
                break;
            case static::CATEGORY_ROOT_PARENT:
                $rootCategory = $this->tryToGetParentCategoryIfAvailable($this->getCurrentCategory());
                break;
            case static::CATEGORY_ROOT_CURRENT_PARENT:
                $rootCategory = $this->getCurrentCategory();
                // Sprawdzamy poprawnie załadowany obiekt przed wywołaniem metody
                if ($rootCategory && !$rootCategory->getSubCategories($rootCategory->id, true)) {
                    $rootCategory = $this->tryToGetParentCategoryIfAvailable($rootCategory);
                }
                break;
            default:
                $rootCategory = $this->getHomeCategory();
        }
        
        if (!$rootCategory) {
            $rootCategory = $this->getHomeCategory();
        }

        $categoriesTree = $this->getCategories($rootCategory);
        
                $this->sortSubcategoriesAlphabetically($categoriesTree, 0);
        return [
            'categories' => [ 
                'name' => $rootCategory->name,
                'link' => $this->context->link->getCategoryLink($rootCategory),
                'id' => $rootCategory->id,
                'children' => $categoriesTree 
            ],
            'currentCategory' => $this->getCurrentCategory() ? (int)$this->getCurrentCategory()->id : 0,
        ];
    }

    /*
     * Tries to retrieve current category from the context.
     */
    private function getCurrentCategory(): ?Category 
    {
        $controller = $this->context->controller;
        $category = null; 

        if ($controller instanceof CategoryController && Validate::isLoadedObject($controller->getCategory())) {
            $category = $controller->getCategory();
        } elseif ($controller instanceof ProductController && Validate::isLoadedObject($controller->getProduct()) && $controller->getProduct()->id_category_default) {
             $category = new Category($controller->getProduct()->id_category_default, $this->context->language->id);
             if (!Validate::isLoadedObject($category)) {
                 $category = null; 
             }
        }
        
        if (!$category) {
            $id_category = (int)Tools::getValue('id_category');
            if ($id_category) {
                 $category = new Category($id_category, $this->context->language->id);
                 if (!Validate::isLoadedObject($category)) {
                     $category = null; 
                 }
            }
        }

        if (!$category) {
             return null;
        }

        return $category;
    }

    /*
     * Tries to get a parent of the current category.
     */
    private function tryToGetParentCategoryIfAvailable($category): Category
    {
        if (!$category) {
             return $this->getHomeCategory();
        }

        if ($category->is_root_category || !$category->id_parent || $category->id == Configuration::get('PS_HOME_CATEGORY')) {
            return $category;
        }

        $parentCategory = new Category($category->id_parent, $this->context->language->id);

        if (!Validate::isLoadedObject($parentCategory)) {
            return $this->getHomeCategory();
        }

        if (!$parentCategory->active || !$category->checkAccess((int) $this->context->customer->id) || !$category->existsInShop($this->context->shop->id)) {
            return $this->tryToGetParentCategoryIfAvailable($parentCategory);
        }

        return $parentCategory;
    }

    private function getIdsOfCategoriesInPathToCurrentCategory(): array
    {
        $currentCategory = $this->getCurrentCategory();
        if (!$currentCategory) {
            return [];
        }
        $categories = $currentCategory->getParentsCategories($this->context->language->id);

        return array_column($categories, 'id_category');
    }
    /**
     * Sorts subcategories alphabetically (locale-aware) starting from depth >= 1.
     * Top-level categories (depth 0) keep their original order.
     */
    private function sortSubcategoriesAlphabetically(array &$nodes, int $depth = 0): void
    {
        if (empty($nodes) || !is_array($nodes)) {
            return;
        }
        if ($depth >= 1) {
            $collator = class_exists('\\Collator') ? new \Collator('pl_PL') : null;
            usort($nodes, function ($a, $b) use ($collator) {
                $na = isset($a['name']) ? (string)$a['name'] : '';
                $nb = isset($b['name']) ? (string)$b['name'] : '';
                if ($collator instanceof \Collator) {
                    return $collator->compare($na, $nb);
                }
                $ta = @iconv('UTF-8', 'ASCII//TRANSLIT', $na);
                $tb = @iconv('UTF-8', 'ASCII//TRANSLIT', $nb);
                if ($ta !== false && $tb !== false) {
                    return strcasecmp($ta, $tb);
                }
                return strcasecmp($na, $nb);
            });
        }
        foreach ($nodes as &$node) {
            if (!empty($node['children']) && is_array($node['children'])) {
                $this->sortSubcategoriesAlphabetically($node['children'], $depth + 1);
            }
        }
    }



    private function getHomeCategory(): Category
    {
        return new Category((int) Configuration::get('PS_HOME_CATEGORY'), $this->context->language->id);
    }
}