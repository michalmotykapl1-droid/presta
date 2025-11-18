<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once (dirname(__FILE__) . '/../x13import.php');

/**
 * @param $module x13import
 * @return bool
 */
function upgrade_module_4_3_3($module)
{
    return Db::getInstance()->execute('
        ALTER TABLE `' . _DB_PREFIX_ . 'ximport_item`
        MODIFY `code` VARCHAR(128)'
    ) && Db::getInstance()->execute('
        ALTER TABLE `' . _DB_PREFIX_ . 'ximport_item_image`
        MODIFY `code` VARCHAR(128)'
    ) && Db::getInstance()->execute('
        ALTER TABLE `' . _DB_PREFIX_ . 'ximport_image`
        MODIFY `code` VARCHAR(128)'
    ) && Db::getInstance()->execute('
        ALTER TABLE `' . _DB_PREFIX_ . 'ximport_product`
        MODIFY `code` VARCHAR(128)'
    );
}
