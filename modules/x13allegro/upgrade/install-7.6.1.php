<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once (__DIR__ . '/../x13allegro.php');

use x13allegro\Adapter\DbAdapter;

/**
 * @param $module x13allegro
 * @return bool
 */
function upgrade_module_7_6_1($module)
{
    if (!DbAdapter::showColumnIndex('xallegro_log', 'displayed')) {
        Db::getInstance()->execute('
            ALTER TABLE `' . _DB_PREFIX_ . 'xallegro_log`
                ADD INDEX(`displayed`)'
        );
    }

    $module->registerHook('actionObjectOrderCarrierUpdateAfter');

    return true;
}
