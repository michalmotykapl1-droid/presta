<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once (dirname(__FILE__) . '/../x13allegro.php');

/**
 * @param $module x13allegro
 * @return bool
 */
function upgrade_module_7_4_0($module)
{
    XAllegroAutoLoader::getInstance()
        ->generateClassIndex()
        ->autoload();

    XAllegroConfiguration::updateValue('AUCTION_PRICE_CUSTOMER_GROUP', Configuration::get('PS_UNIDENTIFIED_GROUP'));
    XAllegroConfiguration::updateValue('PRODUCTIZATION_NAME', 'prestashop');
    XAllegroConfiguration::updateValue('REGISTER_CUSTOMER_GROUP_DEFAULT', XAllegroConfiguration::get('REGISTER_CUSTOMER_GROUP'));
    XAllegroConfiguration::updateValue('REGISTER_CUSTOMER_GROUP', json_encode([XAllegroConfiguration::get('REGISTER_CUSTOMER_GROUP') => true]));

    // new "global-options" in ConfigurationAccount for all Accounts
    foreach (XAllegroAccount::getAllIds(false) as $row) {
        $config = new XAllegroConfigurationAccount($row['id_xallegro_account']);
        $config->updateValue('AUCTION_PRICE_CUSTOMER_GROUP', XAllegroConfigurationAccount::GLOBAL_OPTION);
    }

    $module->registerHook('actionSetInvoice');
    $module->registerHook('actionX13AllegroOrderInvoiceList');
    $module->registerHook('actionX13AllegroOrderInvoiceUpload');
    $module->registerHook('actionObjectGroupDeleteAfter');

    return true;
}
