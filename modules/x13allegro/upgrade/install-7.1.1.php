<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once (dirname(__FILE__) . '/../x13allegro.php');

/**
 * @param $module x13allegro
 * @return bool
 */
function upgrade_module_7_1_1($module)
{
    XAllegroAutoLoader::getInstance()
        ->generateClassIndex()
        ->autoload();

    XAllegroConfiguration::updateValue('IMAGES_MANUFACTURER_TYPE', '');
    XAllegroConfiguration::updateValue('ORDER_ADD_PAYMENT_WHEN_COD', 0);

    $module->registerHook('actionOrderStatusUpdate');

    return true;
}
