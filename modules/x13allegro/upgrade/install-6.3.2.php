<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once (dirname(__FILE__) . '/../x13allegro.php');

use x13allegro\Adapter\DbAdapter;

/**
 * @param $module x13allegro
 * @return bool
 */
function upgrade_module_6_3_2($module)
{
    $hooksToRegister = [
        'displayAdminOrderMain',
        'actionGetAdminOrdersButton',
        'displayAdminOrderTabLink',
        'displayAdminOrderTabContent',
        'displayOrderPreview',
        'actionDispatcher'
    ];

    foreach ($hooksToRegister as $hook) {
        $module->registerHook($hook);
    }

    $dbCharset = DbAdapter::getUtf8Collation();
    
    if ($dbCharset == 'utf8mb4') {
        Db::getInstance()->execute('
            ALTER TABLE `' . _DB_PREFIX_ . 'xallegro_template` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci'
        );

        Db::getInstance()->execute('
            ALTER TABLE `' . _DB_PREFIX_ . 'xallegro_product` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci'
        );
    }

    Db::getInstance()->execute('
        ALTER TABLE `' . _DB_PREFIX_ . 'xallegro_order`
            ADD `fulfillment_status` char(32) NOT NULL AFTER `delivery_method`,
            ADD INDEX(`fulfillment_status`)'
    );

    Db::getInstance()->execute('
        CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'xallegro_order_status_fulfillment` (
            `allegro_status` char(32) NULL,
            `id_order_state` int(10) unsigned NULL,
            
            INDEX (`allegro_status`),
            INDEX (`id_order_state`)
        ) ENGINE=InnoDB DEFAULT CHARSET=' . $dbCharset
    );

    $hasIndex = Db::getInstance()->executeS('show index from `'._DB_PREFIX_.'xallegro_carrier_package_info` where Key_name="id_order"');
    $nbRows = Db::getInstance()->getValue('SELECT count(`id_order_carrier`) AS `nbResults` FROM `'._DB_PREFIX_.'xallegro_carrier_package_info`');

    if (!$hasIndex && $nbRows < 25000) {
        Db::getInstance()->execute('
            ALTER TABLE `' . _DB_PREFIX_ .'xallegro_carrier_package_info`
                ADD INDEX(`id_order`)'
        );
    }

    // new override in this version
    $module->addOverride('OrderHistory');

    return true;
}
