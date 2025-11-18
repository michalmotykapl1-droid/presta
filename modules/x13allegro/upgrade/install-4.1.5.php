<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once (dirname(__FILE__) . '/../x13allegro.php');

/**
 * @return bool
 */
function upgrade_module_4_1_5()
{
    XAllegroAutoLoader::getInstance()
        ->generateClassIndex()
        ->autoload();

    XAllegroConfiguration::updateValue('IMAGES_MAIN_TYPE', XAllegroConfiguration::get('IMAGES_TYPE'));

    if (Db::getInstance()->getValue('
        SELECT COUNT(*) FROM information_schema.columns
        WHERE table_schema = "' . _DB_NAME_ . '"
            AND table_name = "' . _DB_PREFIX_ . 'xallegro_auction"
            AND column_name = "id_allegro_account"'
    )) {
        $xExists = Db::getInstance()->getValue('
            SELECT COUNT(*) FROM information_schema.columns
            WHERE table_schema = "' . _DB_NAME_ . '"
                AND table_name = "' . _DB_PREFIX_ . 'xallegro_auction"
                AND column_name = "id_xallegro_account"'
        );

        Db::getInstance()->execute('
            UPDATE `' . _DB_PREFIX_ . 'xallegro_auction`
                SET `id_xallegro_account` = `id_allegro_account`'
        );

        Db::getInstance()->execute('
            ALTER TABLE `' . _DB_PREFIX_ . 'xallegro_auction`' .
                (!$xExists ? 'ADD `id_xallegro_account` int(10) unsigned NOT NULL AFTER `id_xallegro_auction`,' : '') . '
                DROP INDEX `id_allegro_account`,
                DROP PRIMARY KEY,
                DROP `id_allegro_account`,
                ADD PRIMARY KEY (`id_xallegro_auction`, `id_xallegro_account`)'
        );
    }

    Db::getInstance()->execute('
        ALTER TABLE `' . _DB_PREFIX_ . 'xallegro_form`
            ADD `id_xallegro_account` int(10) unsigned NOT NULL AFTER `id_allegro_form`'
    );

    $sp = XAllegroConfiguration::get('PAYU_STATUS_SELECTED');

    Db::getInstance()->execute('
        INSERT IGNORE INTO `' . _DB_PREFIX_ . 'xallegro_status` (`id_order_state`, `id_allegro_state`, `allegro_name`, `position`)
        VALUES
            (' . $sp . ', "tt", "PayU - Karta kredytowa", 0),
            (' . $sp . ', "ap", "Android Pay", 0)'
    );

    return true;
}
