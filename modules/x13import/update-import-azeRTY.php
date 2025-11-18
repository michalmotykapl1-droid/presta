<?php

require_once (dirname(__FILE__) . '/../../config/config.inc.php');
require_once (dirname(__FILE__) . '/../../init.php');

try{
	$sql = '
	ALTER TABLE `'._DB_PREFIX_.'ximport_item` 
		ADD `additional_categories` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL AFTER `id_category`;';
	Db::getInstance()->execute($sql);
	
	$sql = '
	ALTER TABLE `'._DB_PREFIX_.'ximport_item` 
		ADD `minimal_quantity` INT(11) UNSIGNED NOT NULL DEFAULT \'1\' AFTER `quantity`';
	Db::getInstance()->execute($sql);
} 
catch(Exception $e) {
	//do nothing
}

//tworzenie tabeli z przypisanymi kategoriami
$sql = '
CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'ximport_category_additional` (
  `id_ximport_category` int(10) unsigned NOT NULL,
  `id_category` int(10) unsigned NOT NULL
) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8;';
Db::getInstance()->execute($sql);

//uzupełnianie dotychczasowych powiązań
$sql = '
	SELECT `id_ximport_category`, `id_category`
	FROM `'._DB_PREFIX_.'ximport_category`
';
$results = Db::getInstance()->executeS($sql);
foreach($results as $r) {
	$sql_values[] = '('.(int)$r['id_ximport_category'].', '.(int)$r['id_category'].')';
}
if(isset($sql_values)) {
	$sql = '
		INSERT IGNORE INTO `'._DB_PREFIX_.'ximport_category_additional`()
		VALUES '.implode(', ', $sql_values).'
	';
	Db::getInstance()->execute($sql);
}

try {
	$sql = '
		ALTER TABLE `'._DB_PREFIX_.'ximport_item` 
			ADD `width`  DECIMAL(20,6) NOT NULL DEFAULT \'0\' AFTER `weight`, 
			ADD `height` DECIMAL(20,6) NOT NULL DEFAULT \'0\' AFTER `width`, 
			ADD `depth`  DECIMAL(20,6) NOT NULL DEFAULT \'0\' AFTER `height`, 
			ADD `condition` ENUM(\'new\',\'used\',\'refurbished\') NOT NULL DEFAULT \'new\' AFTER `depth`, 
			ADD `unity` VARCHAR(255) NOT NULL DEFAULT \'\' AFTER `condition`
	';
	Db::getInstance()->execute($sql);
} 
catch(Exception $e) {
	//do nothing
}

try {
	$sql = '
		ALTER TABLE `'._DB_PREFIX_.'ximport_tmp` 
			ADD `basic_price` DECIMAL(20,6) NOT NULL AFTER `quantity`
	';
	Db::getInstance()->execute($sql);
} 
catch(Exception $e) {
	//do nothing
}

try {
	$sql = '
		ALTER TABLE `'._DB_PREFIX_.'ximport_item` 
			ADD `online_only`         TINYINT(1) NOT NULL DEFAULT \'0\' AFTER `minimal_quantity`, 
			ADD `show_price`          TINYINT(1) NOT NULL DEFAULT \'1\' AFTER `online_only`, 
			ADD `available_for_order` TINYINT(1) NOT NULL DEFAULT \'1\' AFTER `show_price`
	';
	Db::getInstance()->execute($sql);
} 
catch(Exception $e) {
	//do nothing
}

//usuwanie "unsigned" z pola markup, tak aby można definiować ujemne marże
try {
	$sql = '
		ALTER TABLE `'._DB_PREFIX_.'ximport_item` 
			MODIFY COLUMN markup float NOT NULL;
	';
	Db::getInstance()->execute($sql);
} 
catch(Exception $e) {
	//do nothing
}

// dodanie pola dostawcy
try {
	$sql = '
		ALTER TABLE `'._DB_PREFIX_.'ximport_category` 
			ADD `supplier`  VARCHAR(64) NOT NULL DEFAULT \'\' AFTER `manufacturer`
	';
	Db::getInstance()->execute($sql);
} 
catch(Exception $e) {
	//do nothing
}


// dodanie pola dostawcy
try {
	$sql = '
		ALTER TABLE `'._DB_PREFIX_.'ximport_item` 
			ADD `supplier`  VARCHAR(64) NOT NULL DEFAULT \'\' AFTER `manufacturer`
	';
	Db::getInstance()->execute($sql);
} 
catch(Exception $e) {
	//do nothing
}


// dodanie nowych pól
try {
	$sql = '
		ALTER TABLE `'._DB_PREFIX_.'ximport_item` 
			ADD `active`  TINYINT(1) NOT NULL DEFAULT \'1\' AFTER `features`,
			ADD `on_sale`  TINYINT(1) NOT NULL DEFAULT \'0\' AFTER `active`,
			ADD `out_of_stock`  TINYINT(1) NOT NULL DEFAULT \'0\' AFTER `on_sale`,
			ADD `available_date`  DATE NULL DEFAULT NULL AFTER `out_of_stock`,
			ADD `meta_title` VARCHAR(255) NOT NULL DEFAULT \'\' AFTER `available_date`,
			ADD `meta_description` VARCHAR(255) NOT NULL DEFAULT \'\' AFTER `meta_title`,
			ADD `meta_keywords` VARCHAR(255) NOT NULL DEFAULT \'\' AFTER `meta_description`,
			ADD `available_now` VARCHAR(255) NOT NULL DEFAULT \'\' AFTER `meta_keywords`,
			ADD `available_later` VARCHAR(255) NOT NULL DEFAULT \'\' AFTER `available_now`,
			ADD `tags` VARCHAR(255) NOT NULL DEFAULT \'\' AFTER `available_later`
	';
	Db::getInstance()->execute($sql);
} 
catch(Exception $e) {
	//do nothing
}

// dodanie nowych kluczy
try {
    $sql = '
        ALTER TABLE `'._DB_PREFIX_.'ximport_item`,
            MODIFY `price` DECIMAL(20,6) NOT NULL DEFAULT 0,
            MODIFY `wholesale_price` DECIMAL(20,6) NOT NULL DEFAULT 0,
		    ADD KEY `manufacturer` (`manufacturer`);

		ALTER TABLE `'._DB_PREFIX_.'ximport_category` ADD KEY `category` (`id_ximport_category`, `id_category`);
        ALTER TABLE `'._DB_PREFIX_.'ximport_category_additional` ADD KEY `category` (`id_ximport_category`, `id_category`);
        ALTER TABLE `'._DB_PREFIX_.'ximport_image` ADD KEY `image` (`id_product`, `id_image`);
        ALTER TABLE `'._DB_PREFIX_.'ximport_image_attribute` ADD KEY `image` (`id_product`, `id_product_attribute`, `id_image`);'
    ;
    Db::getInstance()->execute($sql);
}
catch(Exception $e) {
    //do nothing
}

try {
    Db::getInstance()->execute('
        ALTER TABLE `'._DB_PREFIX_.'ximport_item` ADD INDEX `code` (`code`, `fingerprint`);
        ALTER TABLE `'._DB_PREFIX_.'ximport_product` ADD INDEX(`fingerprint`);'
    );
}
catch (Exception $e) {
    //do nothing
}

try {
    Db::getInstance()->execute('
        ALTER TABLE `'._DB_PREFIX_.'ximport_item` ADD `visibility` ENUM(\'both\', \'catalog\', \'search\', \'none\') NOT NULL DEFAULT \'both\' AFTER `active`;'
    );
}
catch (Exception $e) {
    //do nothing
}

try {
    Db::getInstance()->execute('
        CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'ximport_price` (
            `id_ximport_price`          int(10) unsigned NOT NULL AUTO_INCREMENT,
            `wholesaler`                varchar(64) NOT NULL,
            `range_from`                decimal(10,2) NULL DEFAULT "0.00",
            `range_to`                  decimal(10,2) NULL DEFAULT "0.00",
            `type`                      tinyint(1) NOT NULL,
            `markup`                    decimal(10,2) NULL DEFAULT "0.00",
            `override_category`         tinyint(1) NOT NULL,
            
            PRIMARY KEY (`id_ximport_price`),
            KEY (`wholesaler`)
        ) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
        
        ALTER TABLE `' . _DB_PREFIX_ . 'ximport_product` 
            ADD `exclude` tinyint(1) NOT NULL DEFAULT 0,
            ADD `exclude_price` tinyint(1) NOT NULL DEFAULT 0,
            ADD KEY (`exclude`),
            ADD KEY (`exclude_price`);'
    );
}
catch (Exception $e) {
    //do nothing
}

try {
    Db::getInstance()->execute('
        ALTER TABLE `' . _DB_PREFIX_ . 'ximport_item` 
            MODIFY `visibility` enum("both", "catalog", "search", "none", "nothing") NOT NULL DEFAULT "both";'
    );
}
catch (Exception $e) {
    //do nothing
}

try {
    Db::getInstance()->execute('
        UPDATE `' . _DB_PREFIX_ . 'ximport_product` xp
        JOIN `' . _DB_PREFIX_ . 'product` p
            ON (xp.`id_product` = p.`id_product`)
        JOIN `' . _DB_PREFIX_ . 'ximport_exclude` xe
            ON (p.`reference` LIKE CONCAT(xe.`reference`))
        SET xp.`exclude` = 1
        WHERE xe.`type` = 1;
        
        UPDATE `' . _DB_PREFIX_ . 'ximport_product` xp
        JOIN `' . _DB_PREFIX_ . 'product` p
            ON (xp.`id_product` = p.`id_product`)
        JOIN `' . _DB_PREFIX_ . 'ximport_exclude` xe
            ON (p.`reference` LIKE CONCAT(xe.`reference`))
        SET xp.`exclude_price` = 1
        WHERE xe.`type` = 2;'
    );
}
catch (Exception $e) {
    //do nothing
}

try {
    Db::getInstance()->execute('
        DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'ximport_exclude`;
        DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'ximport_threshold`;'
    );
}
catch (Exception $e) {
    //do nothing
}

// minimal_quantity
// UWAGA!!!
// Aby update wykonal sie poprawnie powyzszy update do minimal_quantity musi byc wykonywany zawsze na koncu
try {
    Db::getInstance()->execute('
        UPDATE `'._DB_PREFIX_.'product` p
        JOIN `'._DB_PREFIX_.'ximport_product` xp ON (p.`id_product` = xp.`id_product`)
        SET p.`minimal_quantity` = 1
        WHERE p.`minimal_quantity` = 0 AND xp.`id_product_attribute` = 0;

        UPDATE `'._DB_PREFIX_.'product_shop` p
        JOIN `'._DB_PREFIX_.'ximport_product` xp ON (p.`id_product` = xp.`id_product`)
        SET p.`minimal_quantity` = 1
        WHERE p.`minimal_quantity` = 0 AND xp.`id_product_attribute` = 0;

        UPDATE `'._DB_PREFIX_.'product_attribute` p
        JOIN `'._DB_PREFIX_.'ximport_product` xp ON (p.`id_product` = xp.`id_product` AND p.`id_product_attribute` = xp.`id_product_attribute`)
        SET p.`minimal_quantity` = 1
        WHERE p.`minimal_quantity` = 0;

        UPDATE `'._DB_PREFIX_.'product_attribute_shop` p
        JOIN `'._DB_PREFIX_.'ximport_product` xp ON (p.`id_product_attribute` = xp.`id_product_attribute`)
        SET p.`minimal_quantity` = 1
        WHERE p.`minimal_quantity` = 0;'
    );
}
catch(Exception $e) {
    //do nothing
}

echo 'ok';
