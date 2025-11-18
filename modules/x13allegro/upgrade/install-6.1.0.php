<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once (dirname(__FILE__) . '/../x13allegro.php');

/**
 * @return bool
 */
function upgrade_module_6_1_0()
{
    XAllegroAutoLoader::getInstance()
        ->generateClassIndex()
        ->autoload();

    Db::getInstance()->execute('ALTER TABLE `' . _DB_PREFIX_ . 'xallegro_category` ADD `name` CHAR(128) AFTER `id_categories`');

    return true;
}
