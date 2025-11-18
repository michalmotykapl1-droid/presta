<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once (dirname(__FILE__) . '/../x13allegro.php');

/**
 * @param $module x13allegro
 * @return bool
 */
function upgrade_module_7_4_6($module)
{
    $module->unregisterHook('actionUpdateQuantity');
    $module->registerHook('actionObjectStockAvailableUpdateAfter');

    return true;
}
