<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once (dirname(__FILE__) . '/../x13allegro.php');

/**
 * @return bool
 */
function upgrade_module_6_6_0()
{
    XAllegroAutoLoader::getInstance()
        ->generateClassIndex()
        ->autoload();

    Db::getInstance()->execute('
        ALTER TABLE `' . _DB_PREFIX_ . 'xallegro_auction`
        ADD `fees` decimal(10,2) NOT NULL DEFAULT 0 AFTER `price_buy_now`'
    );

    XAllegroConfiguration::updateValue('PRODUCTIZATION_DESCRIPTION', 'prestashop');
    XAllegroConfiguration::updateValue('PRODUCTIZATION_IMAGES', 'prestashop');
    XAllegroConfiguration::updateValue('PARAMETERS_GLOBAL_CONDITION', 1);

    foreach (XAllegroAccount::getAll() as $account) {
        $accountConfiguration = new XAllegroConfigurationAccount((int) $account->id);

        if (!XAllegroConfiguration::get('AUCTION_CALCULATE_FEES')) {
            $accountConfiguration->updateValue('AUCTION_CALCULATE_FEES', XAllegroConfigurationAccount::GLOBAL_OPTION);
        }
    }

    return true;
}
