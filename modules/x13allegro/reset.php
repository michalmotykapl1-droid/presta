<?php

require_once (dirname(__FILE__) . '/../../config/config.inc.php');
require_once (dirname(__FILE__) . '/x13allegro.php');

$start = microtime(true);
$error = false;

echo '<pre>';

if (Tools::getValue('token') != XAllegroConfiguration::get('SYNC_TOKEN')) {
    $error = 'Token error';
}
else if (!Module::isInstalled('x13allegro')) {
    $error = 'Module is not installed';
}

if (!$error) {
    $module = Module::getInstanceByName('x13allegro');

    if (!$module->active) {
        $error = 'Module is not active';
    }
}

if ($error) {
    echo $error;
    exit;
}

$id_account = (int)Tools::getValue('id_account');
$order_event = Tools::getValue('order_event');

$account = new XAllegroAccount($id_account);

if (!Validate::isLoadedObject($account)) {
    echo "INCORRECT ALLEGRO ACCOUNT \n";
}
else {
    echo "IdAccount $id_account \n\n";

    if (Tools::getIsset('order_event')) {
        $account->last_order_event = $order_event;
        $account->save();

        echo "SET LAST ORDER EVENT: $order_event \n";
    }
}

XAllegroConfiguration::updateValue('LOCK_SYNC', 0);
echo "CLEAR LOCK_SYNC \n";

exit;
