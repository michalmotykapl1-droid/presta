<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once (dirname(__FILE__) . '/../x13allegro.php');

/**
 * @return bool
 */
function upgrade_module_4_1_12()
{
    XAllegroAutoLoader::getInstance()
        ->generateClassIndex()
        ->autoload();

    if (!XAllegroConfiguration::get('ORDER_CUSTOMER_MESSAGE_CONTACT')) {
        $contacts = Contact::getContacts(Context::getContext()->language->id);
        if (!empty($contacts) && isset($contacts[0]['id_contact'])) {
            XAllegroConfiguration::updateValue('ORDER_CUSTOMER_MESSAGE_CONTACT', $contacts[0]['id_contact']);
        }
    }

    return true;
}
