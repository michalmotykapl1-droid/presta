<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once (dirname(__FILE__) . '/../x13import.php');

/**
 * @param $module x13import
 * @return bool
 */
function upgrade_module_4_0_0($module)
{
    // generate new class_index
    XImportAutoLoader::getInstance()
        ->generateClassIndex()
        ->autoload();

    $module->reinstallTabs();

    $path  = _PS_MODULE_DIR_ . 'x13import/controllers/admin/';
    $dir = dir($path);

    // usuwamy stare kontrolery
    while (false !== ($file = $dir->read())) {
        if (preg_match('/^((?!Controller).)*\.php$/', $file)) {
            @unlink($path . $file);
        }
    }

    // usuwamy stare klasy i pliki
    @unlink(_PS_MODULE_DIR_ . 'x13import/classes/XImportThreshold.php');
    @unlink(_PS_MODULE_DIR_ . 'x13import/classes/XImportExclude.php');
    @unlink(_PS_MODULE_DIR_ . 'x13import/js/ximport.js');
    @rmdir(_PS_MODULE_DIR_ . 'x13import/js');

    // nowe opcje w konfiguracji
    foreach (array(
        'UPDATE_DESC_SHORT' => XImportConfiguration::get('UPDATE_DESC'),
        'UPDATE_WHOLESALE_PRICE' => XImportConfiguration::get('UPDATE_PRICE'),
        'DISABLE_NO_IMAGES' => 0,
        'MAX_IMAGE_SIZE' => 5,
        'MAX_IMAGE_PIXEL' => 4500
     ) as $key => $value
    ) {
        XImportConfiguration::updateValue($key, $value);
    }

    return true;
}
