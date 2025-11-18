<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once (dirname(__FILE__) . '/../x13import.php');

use x13import\Adapter\DbAdapter;

/**
 * @param $module x13import
 * @return bool
 */
function upgrade_module_4_5_0()
{
    $dbCharset = DbAdapter::getUtf8Collation();

    if ('utf8mb4' == $dbCharset) {
        Db::getInstance()->execute('ALTER TABLE `'._DB_PREFIX_.'ximport_configuration` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci');
        Db::getInstance()->execute('ALTER TABLE `'._DB_PREFIX_.'ximport_configuration_wholesaler` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci');
        Db::getInstance()->execute('ALTER TABLE `'._DB_PREFIX_.'ximport_price` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci');
        Db::getInstance()->execute('ALTER TABLE `'._DB_PREFIX_.'ximport_category` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci');
        Db::getInstance()->execute('ALTER TABLE `'._DB_PREFIX_.'ximport_category_additional` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci');
        Db::getInstance()->execute('ALTER TABLE `'._DB_PREFIX_.'ximport_image` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci');
        Db::getInstance()->execute('ALTER TABLE `'._DB_PREFIX_.'ximport_image_attribute` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci');
        Db::getInstance()->execute('ALTER TABLE `'._DB_PREFIX_.'ximport_attachment` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci');
        Db::getInstance()->execute('ALTER TABLE `'._DB_PREFIX_.'ximport_item_image` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci');
        Db::getInstance()->execute('ALTER TABLE `'._DB_PREFIX_.'ximport_item_attachment` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci');
        Db::getInstance()->execute('ALTER TABLE `'._DB_PREFIX_.'ximport_tmp` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci');
        Db::getInstance()->execute('ALTER TABLE `'._DB_PREFIX_.'ximport_product` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci');
    }

    return true;
}
