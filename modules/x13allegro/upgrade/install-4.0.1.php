<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once (dirname(__FILE__) . '/../x13allegro.php');

/**
 * @return bool
 */
function upgrade_module_4_0_1()
{
    XAllegroAutoLoader::getInstance()
        ->generateClassIndex()
        ->autoload();

    XAllegroConfiguration::updateValue('IMPORT_ORDERS_CHUNK', 25);

    return true;
}
