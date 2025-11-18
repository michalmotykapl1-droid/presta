<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once (dirname(__FILE__) . '/../x13allegro.php');

/**
 * @return bool
 */
function upgrade_module_6_1_4()
{
    $deviceColumnsExists = Db::getInstance()->executeS('
       SELECT COUNT(*) as `count`
       FROM information_schema.columns
       WHERE table_schema = "' . _DB_NAME_ . '"
           AND table_name = "' . _DB_PREFIX_ . 'xallegro_account"
           AND column_name = "device_code"'
    );

    $alter = [];
    if (isset($deviceColumnsExists[0]) && !(int)$deviceColumnsExists[0]['count']) {
        $alter[] = 'ADD `device_code` char(64) NULL AFTER `refresh_token`';
        $alter[] = 'ADD `user_code` char(64) NULL AFTER `refresh_token`';
    }

    if (!empty($alter)) {
        Db::getInstance()->execute('ALTER TABLE `' . _DB_PREFIX_ . 'xallegro_account` ' . implode(',', $alter));
    }

    // resetujemy autoryzacje
    Db::getInstance()->execute('
        UPDATE `' . _DB_PREFIX_ . 'xallegro_account`
        SET `access_token` = "",
            `refresh_token` = "",
            `expire_refresh` = "2016-01-01 00:00:00",
            `expire_authorization` = "2016-01-01 00:00:00"'
    );

    // wylaczamy tymczasowo sandboxy
    Db::getInstance()->execute('
        UPDATE `' . _DB_PREFIX_ . 'xallegro_account`
        SET `active` = 0
        WHERE `sandbox` = 1'
    );

    return true;
}
