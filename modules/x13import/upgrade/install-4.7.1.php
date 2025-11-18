<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once (dirname(__FILE__) . '/../x13import.php');

/**
 * @return bool
 */
function upgrade_module_4_7_1()
{
    // generate new class_index
    XImportAutoLoader::getInstance()
        ->generateClassIndex()
        ->autoload();

    return true;
}
