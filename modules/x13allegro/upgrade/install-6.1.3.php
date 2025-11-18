<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once (dirname(__FILE__) . '/../x13allegro.php');

/**
 * @return bool
 */
function upgrade_module_6_1_3()
{
    XAllegroAutoLoader::getInstance()
        ->generateClassIndex()
        ->autoload();

    Db::getInstance()->execute('ALTER TABLE `' . _DB_PREFIX_ . 'xallegro_form` ADD `id_transaction` CHAR(16)');

    if ((int) XAllegroConfiguration::get('PRICE_UPDATE_CHUNK') < 200) {
        XAllegroConfiguration::updateValue('PRICE_UPDATE_CHUNK', 200);
    }

    if ((int) XAllegroConfiguration::get('QUANITY_ALLEGRO_UPDATE_CHUNK') < 200) {
        XAllegroConfiguration::updateValue('QUANITY_ALLEGRO_UPDATE_CHUNK', 200);
    }

    return true;
}
