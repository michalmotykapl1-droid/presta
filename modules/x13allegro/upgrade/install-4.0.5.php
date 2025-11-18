<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once (dirname(__FILE__) . '/../x13allegro.php');

/**
 * @return bool
 */
function upgrade_module_4_0_5()
{
    XAllegroAutoLoader::getInstance()
        ->generateClassIndex()
        ->autoload();

    if ((int)XAllegroConfiguration::get('DURATION_DEFAULT') == 4) {
        XAllegroConfiguration::updateValue('DURATION_DEFAULT', 3);
    }

    return true;
}
