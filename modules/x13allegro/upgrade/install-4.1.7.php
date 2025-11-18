<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once (dirname(__FILE__) . '/../x13allegro.php');

/**
 * @return bool
 */
function upgrade_module_4_1_7()
{
    XAllegroAutoLoader::getInstance()
        ->generateClassIndex()
        ->autoload();

    foreach (array(
         'PRICE_BASE' => 0,
         'ORDER_SEND_MAIL' => 0,
         'QUANITY_ALLEGRO_OOS' => 0
     ) as $key => $value
    ) {
        XAllegroConfiguration::updateValue($key, $value);
    }

    return true;
}
