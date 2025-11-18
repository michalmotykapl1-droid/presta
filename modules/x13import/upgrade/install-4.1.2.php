<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once (dirname(__FILE__) . '/../x13import.php');

/**
 * @param $module x13import
 * @return bool
 */
function upgrade_module_4_1_2($module)
{
    Db::getInstance()->execute('
        ALTER TABLE `' . _DB_PREFIX_ . 'ximport_tmp`
            ADD `basic_wholesale_price` decimal(20,6) NOT NULL DEFAULT 0,
            ADD `basic_weight` decimal(20,6) NOT NULL DEFAULT 0'
    );

    $products = Db::getInstance()->executeS('
        SELECT `id_ximport_product`, `code`
        FROM `' . _DB_PREFIX_ . 'ximport_product`
        WHERE `id_product_attribute` = 0
            AND (`fingerprint` = "" OR `fingerprint` IS NULL)'
    );

    if ($products) {
        foreach ($products as $product) {
            Db::getInstance()->execute('
                UPDATE `' . _DB_PREFIX_ . 'ximport_product`
                SET `fingerprint` = "' . pSQL(md5($product['code'] . serialize(array()))) . '"
                WHERE `id_ximport_product` = ' . (int)$product['id_ximport_product']
            );
        }
    }

    return true;
}
