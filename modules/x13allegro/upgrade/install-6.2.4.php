<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once (dirname(__FILE__) . '/../x13allegro.php');

/**
 * @return bool
 */
function upgrade_module_6_2_4()
{
    Db::getInstance()->execute('
        ALTER TABLE `' . _DB_PREFIX_ .'xallegro_product`
            CHANGE `sync_price` `sync_price` tinyint(1) NOT NULL DEFAULT 4'
    );

    Db::getInstance()->execute('
        UPDATE `' . _DB_PREFIX_ .'xallegro_product`
        SET `sync_price` = 4
        WHERE `sync_price` = 1'
    );

    return true;
}
