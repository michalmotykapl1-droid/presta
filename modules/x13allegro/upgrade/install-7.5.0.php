<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once (__DIR__ . '/../x13allegro.php');

/**
 * @param $module x13allegro
 * @return bool
 */
function upgrade_module_7_5_0($module)
{
    XAllegroAutoLoader::getInstance()
        ->generateClassIndex()
        ->autoload();

    Db::getInstance()->execute('
        ALTER TABLE `' . _DB_PREFIX_ . 'xallegro_account`
            DROP `user_code`,
            DROP `device_code`,
            DROP `expire_refresh`,
            DROP `expire_authorization`'
    );

    $module->registerHook('actionAdminX13GPSRResponsibleManufacturerFormModifier');
    $module->registerHook('actionAdminX13GPSRResponsibleManufacturerControllerSaveAfter');
    $module->registerHook('actionObjectXGpsrResponsibleManufacturerDeleteAfter');
    $module->registerHook('actionAdminX13GPSRResponsiblePersonFormModifier');
    $module->registerHook('actionAdminX13GPSRResponsiblePersonControllerSaveAfter');
    $module->registerHook('actionObjectXGpsrResponsiblePersonDeleteAfter');

    $module->reinstallTabs();

    if (!is_dir(X13_ALLEGRO_ATTACHMENT_DIR)) {
        mkdir(X13_ALLEGRO_ATTACHMENT_DIR, 0775, true);
    }

    return true;
}
