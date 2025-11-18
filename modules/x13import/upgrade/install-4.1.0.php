<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once (dirname(__FILE__) . '/../x13import.php');

/**
 * @param $module x13import
 * @return bool
 */
function upgrade_module_4_1_0($module)
{
    $pairs = Db::getInstance()->executeS('
        SELECT CONCAT(`id_ximport_category`, ".", `id_category`) AS pair, count(*) AS total
        FROM `' . _DB_PREFIX_ . 'ximport_category_additional`
        GROUP BY `pair`
        HAVING `total` > 1
        ORDER BY `total` DESC'
    );

    foreach ($pairs as $row)
    {
        $pair = explode(".", $row['pair']);

        Db::getInstance()->execute('
            DELETE FROM `' . _DB_PREFIX_ . 'ximport_category_additional`
            WHERE `id_ximport_category` = ' . (int)$pair[0] . '
                AND `id_category` = ' . (int)$pair[1]
        );

        Db::getInstance()->execute('
            INSERT INTO `' . _DB_PREFIX_ . 'ximport_category_additional` 
                (`id_ximport_category`, `id_category`)
            VALUES (' . (int)$pair[0] . ', ' . (int)$pair[1] . ')'
        );
    }

    Db::getInstance()->execute('ALTER TABLE `' . _DB_PREFIX_ . 'ximport_category_additional` DROP INDEX `category`');
    Db::getInstance()->execute('ALTER TABLE `' . _DB_PREFIX_ . 'ximport_category_additional` ADD UNIQUE KEY `category` (`id_ximport_category`, `id_category`)');

    return true;
}
