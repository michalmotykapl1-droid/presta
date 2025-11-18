<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once (dirname(__FILE__) . '/../x13allegro.php');

/**
 * @return bool
 */
function upgrade_module_4_0_4()
{
    XAllegroAutoLoader::getInstance()
        ->generateClassIndex()
        ->autoload();

    XAllegroConfiguration::updateValue('PAGE_SYNC', 0);

    // fix duplication on some servers
    XAllegroConfiguration::deleteByName('LOCK_SYNC');
    XAllegroConfiguration::updateValue('LOCK_SYNC', 0);

    Db::getInstance()->execute('ALTER TABLE `' . _DB_PREFIX_ . 'xallegro_template` ADD `is_new` tinyint(1) unsigned NOT NULL DEFAULT 0');
    Db::getInstance()->execute('ALTER TABLE `' . _DB_PREFIX_ . 'xallegro_configuration` ADD CONSTRAINT conf_name UNIQUE (`name`)');

    return true;
}
