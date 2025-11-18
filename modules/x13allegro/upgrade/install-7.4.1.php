<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once (dirname(__FILE__) . '/../x13allegro.php');

use x13allegro\Adapter\DbAdapter;

/**
 * @return bool
 */
function upgrade_module_7_4_1()
{
    XAllegroAutoLoader::getInstance()
        ->generateClassIndex()
        ->autoload();

    $dbCharset = DbAdapter::getUtf8Collation();

    Db::getInstance()->execute('
        CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'xallegro_carrier_account` (
            `id_xallegro_account`       int(10) unsigned NOT NULL,
            `id_fields_shipment`        char(64) NOT NULL,
            `id_carrier`                int(10) unsigned NOT NULL DEFAULT 0,
            `id_operator`               char(32) NULL,
            `operator_name`             char(32) NULL,

            INDEX (`id_xallegro_account`),
            INDEX (`id_fields_shipment`),
            INDEX (`id_carrier`)
        ) ENGINE=InnoDB DEFAULT CHARSET=' . $dbCharset
    );

    return true;
}
