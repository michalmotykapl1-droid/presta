<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once (dirname(__FILE__) . '/../x13allegro.php');

/**
 * @return bool
 */
function upgrade_module_4_1_8()
{
    Db::getInstance()->execute('
        ALTER TABLE `' . _DB_PREFIX_ . 'xallegro_form`
            DROP INDEX id_allegro_form'
    );

    Db::getInstance()->execute('
        ALTER TABLE `' . _DB_PREFIX_ . 'xallegro_template`
            ADD `additional_images` char(255) NULL'
    );

    Db::getInstance()->execute('
        ALTER TABLE `' . _DB_PREFIX_ . 'xallegro_product`
            ADD `images` text NULL,
            ADD UNIQUE KEY (`id_product`, `id_product_attribute`)'
    );

    Db::getInstance()->execute('
        ALTER TABLE `' . _DB_PREFIX_ . 'xallegro_pas`
            MODIFY `invoice` int(10) unsigned NOT NULL'
    );

    Db::getInstance()->execute('
        ALTER TABLE `' . _DB_PREFIX_ . 'xallegro_form`
            ADD UNIQUE KEY (`id_allegro_form`, `id_xallegro_account`)'
    );

    return true;
}
