<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once (dirname(__FILE__) . '/../x13import.php');

/**
 * @return bool
 */
function upgrade_module_4_7_2()
{
    // fingerprint fix
    XImportConfiguration::updateValue('FINGERPRINT_REGENERATION_472', 1);

    // clear all item tables
    Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'ximport_item`');
    Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'ximport_item_image`');

    Db::getInstance()->execute('
        ALTER TABLE `' . _DB_PREFIX_ . 'ximport_item`
            ADD `fingerprint_old` varchar(32) NOT NULL AFTER `fingerprint`,
            ADD INDEX(`fingerprint_old`)'
    );

    return true;
}
