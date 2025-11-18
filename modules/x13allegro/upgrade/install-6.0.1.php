<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once (dirname(__FILE__) . '/../x13allegro.php');

use x13allegro\Api\Adapter\Delivery\DeliveryMethods;

/**
 * @return bool
 */
function upgrade_module_6_0_1()
{
    Db::getInstance()->execute('
        ALTER TABLE `' . _DB_PREFIX_ . 'xallegro_form`
        MODIFY `id_allegro_shipment` char(64) NOT NULL'
    );

    // START form - migration ------------------------------------------------------------------------------------------
    $forms = Db::getInstance()->executeS('
        SELECT `id_xallegro_form`, `id_allegro_shipment` 
        FROM `' . _DB_PREFIX_ . 'xallegro_form`'
    );

    if ($forms) {
        foreach ($forms as $form) {
            $newId = DeliveryMethods::map($form['id_allegro_shipment']);
            if ($newId) {
                Db::getInstance()->execute('
                    UPDATE `' . _DB_PREFIX_ . 'xallegro_form`
                    SET `id_allegro_shipment` = "' . pSQL($newId) . '"
                    WHERE `id_xallegro_form` = ' . (int)$form['id_xallegro_form']
                );
            }
        }
    }
    // END form - migration --------------------------------------------------------------------------------------------

    return true;
}
