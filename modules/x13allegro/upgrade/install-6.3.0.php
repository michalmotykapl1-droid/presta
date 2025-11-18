<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once (dirname(__FILE__) . '/../x13allegro.php');

/**
 * @return bool
 */
function upgrade_module_6_3_0()
{
    XAllegroAutoLoader::getInstance()
        ->generateClassIndex()
        ->autoload();

    if (!is_dir(_PS_MODULE_DIR_ . 'x13allegro/cache/json')) {
        mkdir(_PS_MODULE_DIR_ . 'x13allegro/cache/json', 0775, true);
    }

    if (!is_dir(_PS_MODULE_DIR_ . 'x13allegro/backups')) {
        mkdir(_PS_MODULE_DIR_ . 'x13allegro/backups', 0775, true);
    }

    if (!is_dir(_PS_MODULE_DIR_ . 'x13allegro/backups/auctions')) {
        mkdir(_PS_MODULE_DIR_ . 'x13allegro/backups/auctions', 0775, true);
    }

    XAllegroConfiguration::updateValue('PRICE_TAX', 1);

    return true;
}
