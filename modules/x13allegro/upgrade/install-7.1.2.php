<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once (dirname(__FILE__) . '/../x13allegro.php');

/**
 * @return bool
 */
function upgrade_module_7_1_2()
{
    XAllegroAutoLoader::getInstance()
        ->generateClassIndex()
        ->autoload();

    Db::getInstance()->execute('
        ALTER TABLE `' . _DB_PREFIX_ . 'xallegro_auction`
            ADD `archived_date` datetime NULL AFTER `closed`,
            ADD `archived` tinyint(1) unsigned NOT NULL DEFAULT 0 AFTER `closed`,
            ADD INDEX(`archived`)'
    );

    return true;
}
