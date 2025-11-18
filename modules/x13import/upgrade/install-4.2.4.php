<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once (dirname(__FILE__) . '/../x13import.php');

/**
 * @param $module x13import
 * @return bool
 */
function upgrade_module_4_2_4($module)
{
    // generate new class_index
    XImportAutoLoader::getInstance()
        ->generateClassIndex()
        ->autoload();

    return XImportConfiguration::updateValue('CLEAR_CACHE', 1);
}
