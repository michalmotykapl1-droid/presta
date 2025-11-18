<?php

use x13allegro\Adapter\DbAdapter;

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once (dirname(__FILE__) . '/../x13allegro.php');

/**
 * @return bool
 */
function upgrade_module_6_5_0()
{
    $dbCharset = DbAdapter::getUtf8Collation();

     // generate new class_index
    XAllegroAutoLoader::getInstance()
        ->generateClassIndex()
        ->autoload();

    Db::getInstance()->execute('
        ALTER TABLE `' . _DB_PREFIX_ . 'xallegro_template`
        MODIFY `additional_images` text NULL'
    );

    Db::getInstance()->execute('
        ALTER TABLE `'._DB_PREFIX_.'xallegro_category`
        MODIFY `id_categories` text NULL'
    );

    Db::getInstance()->execute('
        CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'xallegro_configuration_account` (
            `id_xallegro_configuration_account` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `name`                              char(32) NOT NULL,
            `value`                             text NOT NULL,
            `id_account`                        int(10) unsigned NOT NULL,
    
            PRIMARY KEY (`id_xallegro_configuration_account`),
            UNIQUE KEY (`name`, `id_account`)
        ) ENGINE=InnoDB DEFAULT CHARSET=' . $dbCharset
    );

    XAllegroConfiguration::updateValue('CLOSE_AUCTION_TRESHOLD', '0');

    return true;
}
