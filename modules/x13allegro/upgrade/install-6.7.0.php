<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once (dirname(__FILE__) . '/../x13allegro.php');

/**
 * @return bool
 */
function upgrade_module_6_7_0()
{
    XAllegroAutoLoader::getInstance()
        ->generateClassIndex()
        ->autoload();

    foreach (XAllegroAccount::getAll() as $account) {
        $accountConfiguration = new XAllegroConfigurationAccount((int) $account->id);

        if (!XAllegroConfiguration::get('AUCTION_CHECK_BADGES')) {
            $accountConfiguration->updateValue('AUCTION_CHECK_BADGES', XAllegroConfigurationAccount::GLOBAL_OPTION);
        }
    }

    return true;
}
