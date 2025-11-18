<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once (dirname(__FILE__) . '/../x13import.php');

/**
 * @param $module x13import
 * @return bool
 */
function upgrade_module_4_0_4($module)
{
    // generate new class_index
    XImportAutoLoader::getInstance()
        ->generateClassIndex()
        ->autoload();

    // nowe opcje w konfiguracji
    XImportConfiguration::updateValue('ALLOW_NO_IMAGES', 1);

    return Db::getInstance()->execute('
        ALTER TABLE `' . _DB_PREFIX_ . 'ximport_item`
            ADD `carriers` text NULL'
    );
}
