<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once (dirname(__FILE__) . '/../x13import.php');

use x13import\Adapter\DbAdapter;
use x13import\Wholesaler\WholesalerConfiguration;

/**
 * @param x13import $module
 * @return bool
 */
function upgrade_module_5_0_0($module)
{
    // generate new class_index
    XImportAutoLoader::getInstance()
        ->generateClassIndex()
        ->autoload();

    $dbCharset = DbAdapter::getUtf8Collation();

    Db::getInstance()->execute('
        ALTER TABLE `' . _DB_PREFIX_ . 'ximport_configuration`
            MODIFY `name` char(64)'
    );

    Db::getInstance()->execute('
        ALTER TABLE `' . _DB_PREFIX_ . 'ximport_configuration_wholesaler`
        RENAME `' . _DB_PREFIX_ . 'ximport_configuration_wholesaler_backup`'
    );
    Db::getInstance()->execute('
        CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'ximport_configuration_wholesaler` (
            `wholesaler`                varchar(64) NOT NULL,
            `name`                      varchar(64) NOT NULL,
            `value`                     text NOT NULL,
            
            PRIMARY KEY (`wholesaler`, `name`)
        ) ENGINE=InnoDB DEFAULT CHARSET=' . $dbCharset
    );

    Db::getInstance()->execute('
        CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'ximport_attribute_group` (
            `id_ximport_attribute_group`    int(10) unsigned NOT NULL AUTO_INCREMENT,
            `fingerprint_attribute_group`   varchar(32) NOT NULL,
            `wholesaler`                    varchar(64) NOT NULL,
            `wholesaler_code`               varchar(10) NOT NULL,
            `group_name`                    varchar(64) NOT NULL,
            `import_option`                 enum("DONT_IMPORT", "STANDARD_IMPORT", "ASSIGN_VALUE", "DEFAULT_OPTION") NOT NULL,
            `import_default_option`         enum("DONT_IMPORT", "STANDARD_IMPORT", "DEFAULT_OPTION") NOT NULL,
            `id_attribute_group`            int(10) unsigned NULL,
            
            PRIMARY KEY (`id_ximport_attribute_group`),
            UNIQUE INDEX (`fingerprint_attribute_group`),
            INDEX (`wholesaler`),
            INDEX (`wholesaler_code`),
            INDEX (`import_option`),
            INDEX (`import_default_option`),
            INDEX (`id_attribute_group`)
        ) ENGINE=InnoDB DEFAULT CHARSET=' . $dbCharset
    );
    Db::getInstance()->execute('
        CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'ximport_attribute` (
            `id_ximport_attribute`          int(10) unsigned NOT NULL AUTO_INCREMENT,
            `fingerprint_attribute`         varchar(32) NOT NULL,
            `fingerprint_attribute_group`   varchar(32) NOT NULL,
            `name`                          varchar(128) NOT NULL,
            `import_option`                 enum("DONT_IMPORT", "STANDARD_IMPORT", "ASSIGN_VALUE", "DEFAULT_OPTION") NOT NULL,
            `id_attribute`                  int(10) unsigned NULL,
            
            PRIMARY KEY (`id_ximport_attribute`),
            UNIQUE INDEX (`fingerprint_attribute`, `fingerprint_attribute_group`),
            INDEX (`fingerprint_attribute`),
            INDEX (`fingerprint_attribute_group`),
            INDEX (`import_option`),
            INDEX (`id_attribute`)
        ) ENGINE=InnoDB DEFAULT CHARSET=' . $dbCharset
    );

    Db::getInstance()->execute('
        CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'ximport_feature` (
            `id_ximport_feature`        int(10) unsigned NOT NULL AUTO_INCREMENT,
            `fingerprint_feature`       varchar(32) NOT NULL,
            `wholesaler`                varchar(64) NOT NULL,
            `wholesaler_code`           varchar(10) NOT NULL,
            `name`                      varchar(128) NOT NULL,
            `custom`                    tinyint(1) NOT NULL DEFAULT 0,
            `import_as_custom`          tinyint(1) NULL,
            `import_option`             enum("DONT_IMPORT", "STANDARD_IMPORT", "ASSIGN_VALUE", "DEFAULT_OPTION") NOT NULL,
            `import_default_option`     enum("DONT_IMPORT", "STANDARD_IMPORT", "DEFAULT_OPTION") NOT NULL,
            `id_feature`                int(10) unsigned NULL,
            
            PRIMARY KEY (`id_ximport_feature`),
            UNIQUE INDEX (`fingerprint_feature`),
            INDEX (`wholesaler`),
            INDEX (`wholesaler_code`),
            INDEX (`import_option`),
            INDEX (`import_default_option`),
            INDEX (`id_feature`)
        ) ENGINE=InnoDB DEFAULT CHARSET=' . $dbCharset
    );
    Db::getInstance()->execute('
        CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'ximport_feature_value` (
            `id_ximport_feature_value`  int(10) unsigned NOT NULL AUTO_INCREMENT,
            `fingerprint_feature_value` varchar(32) NOT NULL,
            `fingerprint_feature`       varchar(32) NOT NULL,
            `value`                     varchar(255) NOT NULL,
            `import_option`             enum("DONT_IMPORT", "STANDARD_IMPORT", "ASSIGN_VALUE", "DEFAULT_OPTION") NOT NULL,
            `id_feature_value`          int(10) unsigned NULL,
            
            PRIMARY KEY (`id_ximport_feature_value`),
            UNIQUE INDEX (`fingerprint_feature_value`, `fingerprint_feature`),
            INDEX (`fingerprint_feature_value`),
            INDEX (`fingerprint_feature`),
            INDEX (`import_option`),
            INDEX (`id_feature_value`)
        ) ENGINE=InnoDB DEFAULT CHARSET=' . $dbCharset
    );
    Db::getInstance()->execute('
        CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'ximport_feature_value_custom` (
            `fingerprint_feature_value` varchar(32) NOT NULL,
            `id_product`                int(10) unsigned NOT NULL,
            `id_feature_value`          int(10) unsigned NOT NULL,
            `id_feature_value_assign`   int(10) unsigned NOT NULL DEFAULT 0,
            
            PRIMARY KEY (`fingerprint_feature_value`, `id_product`, `id_feature_value`),
            INDEX (`fingerprint_feature_value`),
            INDEX (`id_product`),
            INDEX (`id_feature_value`),
            INDEX (`id_feature_value_assign`)
        ) ENGINE=InnoDB DEFAULT CHARSET=' . $dbCharset
    );

    Db::getInstance()->execute('
        CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'ximport_manufacturer` (
            `id_ximport_manufacturer`   int(10) unsigned NOT NULL AUTO_INCREMENT,
            `fingerprint_manufacturer`  varchar(32) NOT NULL,
            `wholesaler`                varchar(64) NOT NULL,
            `wholesaler_code`           varchar(10) NOT NULL,
            `manufacturer`              varchar(64) NOT NULL,
            `import_option`             enum("DONT_IMPORT", "STANDARD_IMPORT", "ASSIGN_VALUE", "DEFAULT_OPTION") NOT NULL,
            `import_products_option`    enum("DONT_IMPORT", "STANDARD_IMPORT", "DEFAULT_OPTION") NOT NULL,
            `id_manufacturer`           int(10) unsigned NULL,
            
            PRIMARY KEY (`id_ximport_manufacturer`),
            UNIQUE INDEX (`fingerprint_manufacturer`),
            INDEX (`wholesaler`),
            INDEX (`wholesaler_code`),
            INDEX (`import_option`),
            INDEX (`import_products_option`),
            INDEX (`id_manufacturer`)
        ) ENGINE=InnoDB DEFAULT CHARSET=' . $dbCharset
    );

    Db::getInstance()->execute('
        ALTER TABLE `' . _DB_PREFIX_ . 'ximport_item`
            DROP `attributes`,
            DROP `features`,
            DROP `manufacturer`,
            ADD `fingerprint_manufacturer` varchar(32) NOT NULL DEFAULT "" AFTER `unity`,
            ADD `has_attributes` tinyint(1) NOT NULL DEFAULT 0 AFTER `images_data`,
            ADD INDEX (`fingerprint_manufacturer`),
            ADD INDEX (`has_attributes`)'
    );
    Db::getInstance()->execute('
        CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'ximport_item_attribute` (
            `id_ximport_item_attribute`     int(10) unsigned NOT NULL AUTO_INCREMENT,
            `code`                          varchar(128) NOT NULL,
            `fingerprint_item`              varchar(32) NOT NULL,
            `fingerprint_attribute_group`   varchar(32) NOT NULL,
            `fingerprint_attribute`         varchar(32) NOT NULL,
            
            PRIMARY KEY (`id_ximport_item_attribute`),
            UNIQUE INDEX (`code`, `fingerprint_item`, `fingerprint_attribute_group`, `fingerprint_attribute`),
            INDEX (`code`),
            INDEX (`fingerprint_item`),
            INDEX (`fingerprint_attribute_group`),
            INDEX (`fingerprint_attribute`)
        ) ENGINE=InnoDB DEFAULT CHARSET=' . $dbCharset
    );
    Db::getInstance()->execute('
        CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'ximport_item_feature` (
            `id_ximport_item_feature`   int(10) unsigned NOT NULL AUTO_INCREMENT,
            `code`                      varchar(128) NOT NULL,
            `fingerprint_feature`       varchar(32) NOT NULL,
            `fingerprint_feature_value` varchar(32) NOT NULL,
            
            PRIMARY KEY (`id_ximport_item_feature`),
            UNIQUE INDEX (`code`, `fingerprint_feature`, `fingerprint_feature_value`),
            INDEX (`code`),
            INDEX (`fingerprint_feature`),
            INDEX (`fingerprint_feature_value`)
        ) ENGINE=InnoDB DEFAULT CHARSET=' . $dbCharset
    );

    Db::getInstance()->execute('
        CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'ximport_product_gpt` (
            `id_ximport_product`        int(10) unsigned NOT NULL,
            `name`                      varchar(128) NOT NULL DEFAULT "",
            `description`               longtext NOT NULL DEFAULT "",
            `description_short`         text NOT NULL DEFAULT "",
            `meta_title`                varchar(255) NOT NULL DEFAULT "",
            `meta_description`          varchar(255) NOT NULL DEFAULT "",
            
            PRIMARY KEY (`id_ximport_product`)
        ) ENGINE=InnoDB DEFAULT CHARSET=' . $dbCharset
    );
    Db::getInstance()->execute('
        CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'ximport_gpt` (
            `id_ximport_gpt`                        int(10) unsigned NOT NULL AUTO_INCREMENT,
            `name`                                  varchar(255) NOT NULL,
            `active`                                tinyint(1) NOT NULL,
            `priority`                              int(10) unsigned NOT NULL,
            `product_name`                          tinyint(1) NOT NULL DEFAULT 0,
            `product_name_prompt`                   text NULL,
            `product_name_words_limit`              int(10) unsigned NULL,
            `product_description`                   tinyint(1) NOT NULL DEFAULT 0,
            `product_description_prompt`            text NULL,
            `product_description_words_limit`       int(10) unsigned NULL,
            `product_description_short`             tinyint(1) NOT NULL DEFAULT 0,
            `product_description_short_prompt`      text NULL,
            `product_description_short_words_limit` int(10) unsigned NULL,
            `product_meta_title`                    tinyint(1) NOT NULL DEFAULT 0,
            `product_meta_title_prompt`             text NULL,
            `product_meta_title_words_limit`        int(10) unsigned NULL,
            `product_meta_description`              tinyint(1) NOT NULL DEFAULT 0,
            `product_meta_description_prompt`       text NULL,
            `product_meta_description_words_limit`  int(10) unsigned NULL,
            
            PRIMARY KEY (`id_ximport_gpt`),
            INDEX (`active`),
            INDEX (`priority`)
        ) ENGINE=InnoDB DEFAULT CHARSET=' . $dbCharset
    );
    Db::getInstance()->execute('
        CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'ximport_gpt_wholesaler` (
            `id_ximport_gpt`            int(10) unsigned NOT NULL,
            `wholesaler_code`           varchar(10) NOT NULL,
            
            PRIMARY KEY (`id_ximport_gpt`, `wholesaler_code`),
            INDEX (`wholesaler_code`)
        ) ENGINE=InnoDB DEFAULT CHARSET=' . $dbCharset
    );

    $configurationBackup = Db::getInstance()->executeS('
        SELECT `wholesaler`, `value`
        FROM `' . _DB_PREFIX_ . 'ximport_configuration_wholesaler_backup`'
    );

    foreach ($configurationBackup as $row) {
        $wholesalerConfiguration = new WholesalerConfiguration($row['wholesaler']);
        $configurationBackup = json_decode($row['value'], true);

        foreach ($configurationBackup as $name => $value) {
            $wholesalerConfiguration->{$name} = $value;
        }
    }

    $module->reinstallTabs();

    $module->unregisterHook('actionObjectCategoryDeleteBefore');
    $module->unregisterHook('actionObjectImageDeleteBefore');
    $module->unregisterHook('actionObjectProductDeleteBefore');

    $module->registerHook('actionObjectCategoryDeleteAfter');
    $module->registerHook('actionObjectManufacturerDeleteAfter');
    $module->registerHook('actionObjectProductDeleteAfter');
    $module->registerHook('actionObjectCombinationDeleteAfter');
    $module->registerHook('actionObjectImageDeleteAfter');
    $module->registerHook('actionAttributeGroupDelete');
    $module->registerHook('actionAttributeDelete');
    $module->registerHook('actionFeatureDelete');
    $module->registerHook('actionFeatureValueDelete');
    $module->registerHook('actionAdminPerformanceControllerAfter');
    $module->registerHook('actionClearSf2Cache');

    return true;
}
