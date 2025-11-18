<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once (dirname(__FILE__) . '/../x13allegro.php');

/**
 * @return bool
 */
function upgrade_module_4_1_1()
{
    Db::getInstance()->execute('
        ALTER TABLE `' . _DB_PREFIX_ . 'xallegro_account`
            ADD `session_handle` char(255) NULL AFTER `refresh_token`,
            ADD `expire_session` datetime NOT NULL DEFAULT "2016-01-01 00:00:00"'
    );

    return true;
}
