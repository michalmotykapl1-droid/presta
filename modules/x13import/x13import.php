<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once (dirname(__FILE__) . '/x13import.ion.php');

class x13import extends x13import\XImportModuleCore
{
    /** @var XImportConfiguration */
    public $config;

    /** @var bool */
    public $bootstrap;

    public function __construct()
    {
        $this->name = 'x13import';
        $this->tab = 'market_place';
        $this->version = '5.0.0';
        $this->author = 'X13.pl';
        $this->need_instance = 1;
        $this->ps_versions_compliancy = ['min' => '1.6.0.0', 'max' => '8.9.99'];
        $this->bootstrap = true;

        parent::__construct();

        if ($this->id && !$this->isHookRegistered('displayBackOfficeHeader')) {
            $this->registerHook('displayBackOfficeHeader');
        }

        $this->config = new XImportConfiguration($this);

        $this->displayName = $this->l('Integracja hurtowni');
        $this->description = $this->l('Import hurtowni XML / CSV / API - zaawansowana integracja hurtowni z Twoim sklepem PrestaShop. Wygodny import produktów, wraz z automatyczną aktualizacją danych.');
    }

    public function hookActionObjectCategoryDeleteAfter(array $data)
    {
        Db::getInstance()->update('ximport_category', array(
            'id_category' => 0
        ), 'id_category = ' . (int)$data['object']->id);

        Db::getInstance()->delete('ximport_category_additional', 'id_category = ' . (int)$data['object']->id);

        foreach ($data['object']->getSubCategories(Context::getContext()->language->id) as $category) {
            Db::getInstance()->update('ximport_category', array(
                'id_category' => 0
            ), 'id_category = ' . (int)$category['id_category']);

            Db::getInstance()->delete('ximport_category_additional', 'id_category = ' . (int)$category['id_category']);
        }
    }

    public function hookActionObjectManufacturerDeleteAfter(array $data)
    {
        \x13import\Importer\Manufacturer\ManufacturerRepository::deleteManufacturerIdAssociation((int)$data['object']->id);
    }

    public function hookActionObjectImageDeleteAfter(array $data)
    {
        Db::getInstance()->delete('ximport_image', 'id_image = ' . (int)$data['object']->id);
        Db::getInstance()->delete('ximport_image_attribute', 'id_image = ' . (int)$data['object']->id);
    }

    public function hookActionObjectProductDeleteAfter(array $data)
    {
        Db::getInstance()->delete('ximport_product', 'id_product = ' . (int)$data['object']->id);
        Db::getInstance()->delete('ximport_attachment', 'id_product = ' . (int)$data['object']->id);
        Db::getInstance()->delete('ximport_image', 'id_product = ' . (int)$data['object']->id);
        Db::getInstance()->delete('ximport_image_attribute', 'id_product = ' . (int)$data['object']->id);
        Db::getInstance()->delete('ximport_feature_value_custom', 'id_product = ' . (int)$data['object']->id);
    }

    public function hookActionObjectCombinationDeleteAfter(array $data)
    {
        Db::getInstance()->delete('ximport_product', 'id_product_attribute = ' . (int)$data['object']->id);
        Db::getInstance()->delete('ximport_image_attribute', 'id_product_attribute = ' . (int)$data['object']->id);
    }

    public function hookActionAttributeGroupDelete(array $data)
    {
        \x13import\Importer\Attribute\AttributeRepository::deleteAttributeGroupIdAssociation((int)$data['id_attribute_group']);
    }

    public function hookActionAttributeDelete(array $data)
    {
        \x13import\Importer\Attribute\AttributeRepository::deleteAttributeIdAssociation((int)$data['id_attribute']);
    }

    public function hookActionFeatureDelete(array $data)
    {
        \x13import\Importer\Feature\FeatureRepository::deleteFeatureIdAssociation((int)$data['id_feature']);
    }

    public function hookActionFeatureValueDelete(array $data)
    {
        \x13import\Importer\Feature\FeatureRepository::deleteFeatureValueIdAssociation((int)$data['id_feature_value']);
    }

    public function hookActionAdminPerformanceControllerAfter($params)
    {
        if (version_compare(_PS_VERSION_, '1.7.1.0', '<')) {
            $this->hookActionClearSf2Cache($params);
        }
    }

    public function hookActionClearSf2Cache($params)
    {
        XImportAutoLoader::getInstance()
            ->generateClassIndex()
            ->autoload();
    }

    public function install()
    {
        if (!parent::install()
            || !$this->registerHook('actionObjectCategoryDeleteAfter')
            || !$this->registerHook('actionObjectManufacturerDeleteAfter')
            || !$this->registerHook('actionObjectImageDeleteAfter')
            || !$this->registerHook('actionObjectProductDeleteAfter')
            || !$this->registerHook('actionObjectCombinationDeleteAfter')
            || !$this->registerHook('actionAttributeGroupDelete')
            || !$this->registerHook('actionAttributeDelete')
            || !$this->registerHook('actionFeatureDelete')
            || !$this->registerHook('actionFeatureValueDelete')
            || !$this->registerHook('actionAdminPerformanceControllerAfter')
            || !$this->registerHook('actionClearSf2Cache')
            || !$this->registerHook('displayBackOfficeHeader')
        ) {
            return false;
        }

        $this->reinstallTabs();

        $dbCharset = \x13import\Adapter\DbAdapter::getUtf8Collation();
        $sql = [];

        $sql[] = '
            CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'ximport_configuration` (
                `id_ximport_configuration`  int(10) unsigned NOT NULL AUTO_INCREMENT,
                `name`                      varchar(64) NOT NULL,
                `value`                     text NOT NULL,
                
                PRIMARY KEY (`id_ximport_configuration`),
                UNIQUE INDEX (`name`)
            ) ENGINE=InnoDB DEFAULT CHARSET=' . $dbCharset;

        $sql[] = '
            CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'ximport_configuration_wholesaler` (
                `wholesaler`                varchar(64) NOT NULL,
                `name`                      varchar(64) NOT NULL,
                `value`                     text NOT NULL,
                
                PRIMARY KEY (`wholesaler`, `name`)
            ) ENGINE=InnoDB DEFAULT CHARSET=' . $dbCharset;

        $sql[] = '
            CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'ximport_price` (
                `id_ximport_price`          int(10) unsigned NOT NULL AUTO_INCREMENT,
                `wholesaler`                varchar(64) NOT NULL,
                `range_from`                decimal(10,2) NULL DEFAULT "0.00",
                `range_to`                  decimal(10,2) NULL DEFAULT "0.00",
                `type`                      tinyint(1) NOT NULL,
                `markup`                    decimal(10,2) NULL DEFAULT "0.00",
                `override_category`         tinyint(1) NOT NULL,
                `active`                    tinyint(1) NOT NULL,
                
                PRIMARY KEY (`id_ximport_price`),
                INDEX (`wholesaler`),
                INDEX (`active`)
            ) ENGINE=InnoDB DEFAULT CHARSET=' . $dbCharset;

        $sql[] = '
            CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'ximport_category` (
                `id_ximport_category`       int(10) unsigned NOT NULL AUTO_INCREMENT,
                `id_category`               int(10) unsigned NOT NULL,
                `path`                      varchar(255) NOT NULL,
                `title_pattern`             varchar(255) NOT NULL DEFAULT "",
                `import`                    tinyint(1) NOT NULL,
                `markup`                    decimal(10,2) NOT NULL DEFAULT "0.00",
                `markup_type`               enum("percent", "amount") NOT NULL DEFAULT "percent",
                `wholesaler_code`           varchar(10) NOT NULL,
                `wholesaler_name`           varchar(50) NOT NULL,
                
                PRIMARY KEY (`id_ximport_category`),
                INDEX (`id_category`),
                INDEX (`import`),
                INDEX (`wholesaler_code`)
            ) ENGINE=InnoDB DEFAULT CHARSET=' . $dbCharset;

        $sql[] = '
            CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'ximport_category_additional` (
                `id_ximport_category`       int(10) unsigned NOT NULL,
                `id_category`               int(10) unsigned NOT NULL,
                
                UNIQUE INDEX (`id_ximport_category`, `id_category`)
            ) ENGINE=InnoDB DEFAULT CHARSET=' . $dbCharset;

        $sql[] = '
            CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'ximport_image` (
                `id_ximport_image`          int(10) unsigned NOT NULL AUTO_INCREMENT,
                `code`                      varchar(128) NOT NULL,
                `id_product`                int(10) unsigned NOT NULL,
                `id_image`                  int(10) unsigned NOT NULL,
                `fingerprint`               varchar(32) NOT NULL COMMENT "md5 - wartosci (adres, base64)",
                
                PRIMARY KEY (`id_ximport_image`),
                INDEX (`code`),
                INDEX (`id_product`),
                INDEX (`id_image`),
                INDEX (`fingerprint`)
            ) ENGINE=InnoDB DEFAULT CHARSET=' . $dbCharset;

        $sql[] = '
            CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'ximport_image_attribute` (
                `id_ximport_image_attribute` int(10) unsigned NOT NULL AUTO_INCREMENT,
                `id_product`                int(10) unsigned NOT NULL,
                `id_product_attribute`      int(10) unsigned NOT NULL,
                `id_image`                  int(10) unsigned NOT NULL,
                
                PRIMARY KEY (`id_ximport_image_attribute`),
                INDEX (`id_product`, `id_product_attribute`),
                INDEX (`id_image`)
            ) ENGINE=InnoDB DEFAULT CHARSET=' . $dbCharset;

        $sql[] = '
            CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'ximport_attachment` (
                `id_ximport_attachment`     int(10) unsigned NOT NULL AUTO_INCREMENT,
                `code`                      varchar(128) NOT NULL,
                `id_product`                int(10) unsigned NOT NULL,
                `id_attachment`             int(10) unsigned NOT NULL,
                `fingerprint`               varchar(32) NOT NULL,
                
                PRIMARY KEY (`id_ximport_attachment`),
                INDEX (`code`),
                INDEX (`id_product`),
                INDEX (`id_attachment`),
                INDEX (`fingerprint`)
            ) ENGINE=InnoDB DEFAULT CHARSET=' . $dbCharset;

        $sql[] = '
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
            ) ENGINE=InnoDB DEFAULT CHARSET=' . $dbCharset;

        $sql[] = '
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
            ) ENGINE=InnoDB DEFAULT CHARSET=' . $dbCharset;

        $sql[] = '
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
            ) ENGINE=InnoDB DEFAULT CHARSET=' . $dbCharset;

        $sql[] = '
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
            ) ENGINE=InnoDB DEFAULT CHARSET=' . $dbCharset;

        $sql[] = '
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
            ) ENGINE=InnoDB DEFAULT CHARSET=' . $dbCharset;

        $sql[] = '
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
            ) ENGINE=InnoDB DEFAULT CHARSET=' . $dbCharset;

        $sql[] = '
            CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'ximport_item` (
                `id_ximport_item`           int(10) unsigned NOT NULL AUTO_INCREMENT,
                `code`                      varchar(128) NOT NULL,
                `wholesaler_code`           varchar(10) NOT NULL,
                `reference`                 varchar(32) NOT NULL DEFAULT "",
                `isbn`                      varchar(32) NOT NULL DEFAULT "",
                `ean13`                     varchar(32) NOT NULL DEFAULT "",
                `mpn`                       varchar(40) NOT NULL DEFAULT "",
                `upc`                       varchar(12) NOT NULL DEFAULT "",
                `id_category`               int(10) unsigned NOT NULL,
                `additional_categories`     varchar(255) NOT NULL DEFAULT "",
                `name`                      varchar(128) NOT NULL,
                `link_rewrite`              varchar(128) NOT NULL,
                `carriers`                  varchar(255) NOT NULL DEFAULT "",
                `description`               longtext NOT NULL DEFAULT "",
                `description_short`         text NOT NULL DEFAULT "",
                `tax`                       int(10) unsigned NOT NULL DEFAULT 0,
                `price`                     decimal(20,6) NOT NULL DEFAULT "0.00",
                `unit_price`                decimal(20,6) NOT NULL DEFAULT "0.00",
                `wholesale_price`           decimal(20,6) NOT NULL DEFAULT "0.00",
                `additional_shipping_cost`  decimal(20,6) NOT NULL DEFAULT "0.00",
                `weight`                    decimal(20,6) NOT NULL DEFAULT "0.00",
                `width`                     decimal(20,6) NOT NULL DEFAULT "0.00",
                `height`                    decimal(20,6) NOT NULL DEFAULT "0.00",
                `depth`                     decimal(20,6) NOT NULL DEFAULT "0.00",
                `condition`                 enum("new", "used", "refurbished") NOT NULL DEFAULT "new",
                `show_condition`            tinyint(1) NOT NULL DEFAULT 0,
                `unity`                     varchar(255) NOT NULL DEFAULT "",
                `fingerprint_manufacturer`  varchar(32) NOT NULL DEFAULT "",
                `supplier`                  varchar(64) NOT NULL DEFAULT "",
                `quantity`                  int(10) unsigned NOT NULL DEFAULT 0,
                `minimal_quantity`          int(10) unsigned NOT NULL DEFAULT 1,
                `online_only`               tinyint(1) NOT NULL DEFAULT 0,
                `show_price`                tinyint(1) NOT NULL DEFAULT 1,
                `available_for_order`       tinyint(1) NOT NULL DEFAULT 1,
                `active`                    tinyint(1) NOT NULL DEFAULT 1,
                `visibility`                enum("both", "catalog", "search", "none", "nothing") NOT NULL DEFAULT "both",
                `visibility_num`            tinyint(1) NOT NULL DEFAULT 1,
                `on_sale`                   tinyint(1) NOT NULL DEFAULT 0,
                `is_virtual`                tinyint(1) NOT NULL DEFAULT 0,
                `out_of_stock`              tinyint(1) NOT NULL DEFAULT 0,
                `location`                  varchar(64) NOT NULL DEFAULT "",
                `low_stock_threshold`       int(10) unsigned NULL,
                `low_stock_alert`           tinyint(1) NOT NULL DEFAULT 0,
                `available_date`            date NULL,
                `meta_title`                varchar(255) NOT NULL DEFAULT "",
                `meta_description`          varchar(255) NOT NULL DEFAULT "",
                `meta_keywords`             varchar(255) NOT NULL DEFAULT "",
                `available_now`             varchar(255) NOT NULL DEFAULT "",
                `available_later`           varchar(255) NOT NULL DEFAULT "",
                `delivery_in_stock`         varchar(255) NOT NULL DEFAULT "",
                `delivery_out_stock`        varchar(255) NOT NULL DEFAULT "",
                `tags`                      varchar(255) NOT NULL DEFAULT "",
                `accessories`               text NOT NULL DEFAULT "",
                `fingerprint`               varchar(32) NOT NULL,
                `images_data`               text NOT NULL DEFAULT "",
                `has_attributes`            tinyint(1) NOT NULL DEFAULT 0,
                `force_product_id`          int(10) unsigned NULL,
                `auction_id`                bigint(20) NOT NULL DEFAULT 0,
                `external_data`             text NOT NULL DEFAULT "",
                
                PRIMARY KEY (`id_ximport_item`),
                UNIQUE INDEX (`fingerprint`),
                INDEX (`code`),
                INDEX (`wholesaler_code`),
                INDEX (`fingerprint_manufacturer`),
                INDEX (`supplier`),
                INDEX (`has_attributes`)
            ) ENGINE=InnoDB DEFAULT CHARSET=' . $dbCharset;

        $sql[] = '
            CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'ximport_item_image` (
                `id_ximport_item_image`     int(10) unsigned NOT NULL AUTO_INCREMENT,
                `code`                      varchar(128) NOT NULL,
                `type`                      varchar(10) NOT NULL,
                `value`                     mediumtext NOT NULL,
                `value_md5`                 varchar(32) NOT NULL,
                `fingerprint`               varchar(32) NOT NULL,
                
                PRIMARY KEY (`id_ximport_item_image`),
                UNIQUE INDEX (`value_md5`, `fingerprint`),
                INDEX (`code`),
                INDEX (`value_md5`),
                INDEX (`fingerprint`)
            ) ENGINE=InnoDB DEFAULT CHARSET=' . $dbCharset;

        $sql[] = '
            CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'ximport_item_attachment` (
                `id_ximport_item_attachment` int(10) unsigned NOT NULL AUTO_INCREMENT,
                `code`                      varchar(128) NOT NULL,
                `type`                      varchar(10) NOT NULL,
                `value`                     mediumtext NOT NULL,
                `value_md5`                 varchar(32) NOT NULL,
                `fingerprint`               varchar(32) NULL,
                
                PRIMARY KEY (`id_ximport_item_attachment`),
                UNIQUE INDEX (`code`, `value_md5`),
                INDEX (`code`),
                INDEX (`value_md5`)
            ) ENGINE=InnoDB DEFAULT CHARSET=' . $dbCharset;

        $sql[] = '
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
            ) ENGINE=InnoDB DEFAULT CHARSET=' . $dbCharset;

        $sql[] = '
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
            ) ENGINE=InnoDB DEFAULT CHARSET=' . $dbCharset;

        $sql[] = '
            CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'ximport_product` (
                `id_ximport_product`        int(10) unsigned NOT NULL AUTO_INCREMENT,
                `code`                      varchar(128) NOT NULL,
                `id_product`                int(10) unsigned NOT NULL,
                `id_product_attribute`      int(10) unsigned NOT NULL,
                `fingerprint`               varchar(32) NOT NULL,
                `exclude`                   tinyint(1) NOT NULL DEFAULT 0,
                `exclude_price`             tinyint(1) NOT NULL DEFAULT 0,
                `exclude_quantity`          tinyint(1) NOT NULL DEFAULT 0,
                
                PRIMARY KEY (`id_ximport_product`),
                UNIQUE INDEX (`id_product`, `id_product_attribute`),
                INDEX (`code`),
                INDEX (`fingerprint`),
                INDEX (`exclude`),
                INDEX (`exclude_price`),
                INDEX (`exclude_quantity`)
            ) ENGINE=InnoDB DEFAULT CHARSET=' . $dbCharset;

        $sql[] = '
            CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'ximport_product_gpt` (
                `id_ximport_product`        int(10) unsigned NOT NULL,
                `name`                      varchar(128) NOT NULL DEFAULT "",
                `description`               longtext NOT NULL DEFAULT "",
                `description_short`         text NOT NULL DEFAULT "",
                `meta_title`                varchar(255) NOT NULL DEFAULT "",
                `meta_description`          varchar(255) NOT NULL DEFAULT "",
                
                PRIMARY KEY (`id_ximport_product`)
            ) ENGINE=InnoDB DEFAULT CHARSET=' . $dbCharset;

        $sql[] = '
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
            ) ENGINE=InnoDB DEFAULT CHARSET=' . $dbCharset;

        $sql[] = '
            CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'ximport_gpt_wholesaler` (
                `id_ximport_gpt`            int(10) unsigned NOT NULL,
                `wholesaler_code`           varchar(10) NOT NULL,
                
                PRIMARY KEY (`id_ximport_gpt`, `wholesaler_code`),
                INDEX (`wholesaler_code`)
            ) ENGINE=InnoDB DEFAULT CHARSET=' . $dbCharset;

        $sql[] = '
            CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'ximport_tmp` (
                `id_product`                int(10) unsigned NOT NULL,
                `quantity`                  int(10) unsigned NOT NULL DEFAULT 0,
                `min_out_of_stock`          tinyint(1) unsigned NOT NULL DEFAULT 0,
                `max_out_of_stock`          tinyint(1) unsigned NOT NULL DEFAULT 0,
                `product_unit_price`        decimal(20,6) NOT NULL DEFAULT "0.00",
                `basic_price`               decimal(20,6) NOT NULL DEFAULT "0.00",
                `basic_unit_price`          decimal(20,6) NOT NULL DEFAULT "0.00",
                `basic_wholesale_price`     decimal(20,6) NOT NULL DEFAULT "0.00",
                `basic_weight`              decimal(20,6) NOT NULL DEFAULT "0.00",
                
                PRIMARY KEY `id_product` (`id_product`)
            ) ENGINE=InnoDB DEFAULT CHARSET=' . $dbCharset;

        foreach ($sql as $query) {
            Db::getInstance()->execute($query);
        }

        // clear static cache
        // fix for module reinstall
        XImportConfiguration::clearStaticCache();

        foreach (XImportConfiguration::getDefaultConfiguration() as $key => $value) {
            XImportConfiguration::updateValue($key, $value);
        }

        return true;
    }

    public function uninstall()
    {
        if (!parent::uninstall()) {
            return false;
        }

        $sql = [];
        $sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'ximport_attachment`';
        $sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'ximport_category`';
        $sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'ximport_category_additional`';
        $sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'ximport_configuration`';
        $sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'ximport_configuration_wholesaler`';
        $sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'ximport_attribute_group`';
        $sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'ximport_attribute`';
        $sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'ximport_feature`';
        $sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'ximport_feature_value`';
        $sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'ximport_feature_value_custom`';
        $sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'ximport_manufacturer`';
        $sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'ximport_price`';
        $sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'ximport_image`';
        $sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'ximport_image_attribute`';
        $sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'ximport_item`';
        $sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'ximport_item_attachment`';
        $sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'ximport_item_attribute`';
        $sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'ximport_item_feature`';
        $sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'ximport_item_image`';
        $sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'ximport_product`';
        $sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'ximport_product_gpt`';
        $sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'ximport_gpt`';
        $sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'ximport_gpt_wholesaler`';
        $sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'ximport_tmp`';

        foreach ($sql as $query) {
            Db::getInstance()->execute($query);
        }

        foreach (Tab::getCollectionFromModule($this->name) as $tab) {
            $tab->delete();
        }

        return true;
    }

    public function reinstallTabs()
    {
        return $this->config->reinstallTabs();
    }

    /**
     * @param string $message
     * @param string $className
     * @param bool $prefix
     * @return string
     */
    public function renderAdminMessage($message, $className = 'warning', $prefix = true)
    {
        return str_replace($this->displayName, '<span class="badge badge-' . $className . '"><b>' . $this->displayName . '</b></span>', ($prefix ? $this->displayName . ' ' : '') . $message);
    }
}

function convertMemory($memory)
{
    $unit = array('B', 'KB', 'MB', 'GB');
    return @round($memory / pow(1024, ($i = floor(log($memory, 1024)))), 2) . (isset($unit[(int)$i]) ? ' ' . $unit[(int)$i] : '');
}
