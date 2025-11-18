<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once (dirname(__FILE__) . '/../x13allegro.php');

/**
 * @return bool
 */
function upgrade_module_4_0_2()
{
    Db::getInstance()->execute('ALTER TABLE `' . _DB_PREFIX_ . 'xallegro_form` ADD `processed` tinyint(1) unsigned NOT NULL DEFAULT 0');

    Db::getInstance()->execute('
        UPDATE `' . _DB_PREFIX_ . 'xallegro_form` xf
        LEFT JOIN `' . _DB_PREFIX_ . 'orders` o
            ON (xf.`id_order` = o.`id_order`)
        SET `processed` = 1
        WHERE xf.`content` <> ""
            AND xf.`processed` = 0'
    );

    return true;
}
