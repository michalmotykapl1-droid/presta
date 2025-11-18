<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once (dirname(__FILE__) . '/../x13allegro.php');

use x13allegro\Api\XAllegroApiTools;

/**
 * @param $module x13allegro
 * @return bool
 */
function upgrade_module_5_0_1($module)
{
    // generate new class_index
    XAllegroAutoLoader::getInstance()
        ->generateClassIndex()
        ->autoload();

    // new hooks
    $module->registerHook('displayHeader');
    $module->registerHook('displayLeftColumnProduct');
    $module->registerHook('displayRightColumnProduct');
    $module->registerHook('displayProductButtons');
    $module->registerHook('displayProductAdditionalInfo');
    $module->registerHook('displayProductAllegroAuctionLink');
    $module->registerHook('displayAdminProductsExtra');
    $module->registerHook('displayAdminOrder');
    $module->registerHook('displayAdminOrderTabShip');
    $module->registerHook('displayAdminOrderContentShip');
    $module->registerHook('actionObjectProductDeleteAfter');
    $module->registerHook('actionObjectProductUpdateAfter');
    $module->registerHook('actionObjectCombinationDeleteAfter');
    $module->registerHook('actionObjectManufacturerDeleteAfter');
    $module->registerHook('actionProductSave');
    $module->registerHook('actionAdminPerformanceControllerAfter');
    $module->registerHook('actionClearSf2Cache');

    $module->removeOverride('AdminOrdersController');
    XAllegroApiTools::rrmdir(_PS_MODULE_DIR_ . 'x13allegro/override/controllers');

    $module->reinstallTabs();

    return true;
}
