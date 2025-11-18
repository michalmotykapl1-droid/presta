<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once (dirname(__FILE__) . '/../x13allegro.php');

/**
 * @param $module x13allegro
 * @return bool
 */
function upgrade_module_4_0_8($module)
{
    /**
     * @since 4.2.0
     * No possibility to migrate suppliers and categories from older versions
     */
    Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'xallegro_status`');
    Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'xallegro_category`');
    Db::getInstance()->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'xallegro_pas`');

    $module->config->installStatuses();

    return true;
}
