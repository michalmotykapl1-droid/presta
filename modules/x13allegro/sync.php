<?php

require_once (dirname(__FILE__) . '/../../config/config.inc.php');
require_once (dirname(__FILE__) . '/x13allegro.php');

use x13allegro\Api\Model\Offers\Enum\EventType as OfferEventType;
use x13allegro\Api\Model\Order\Enum\EventType as OrderEventType;
use x13allegro\Component\Logger\Log;
use x13allegro\Component\Logger\LogDatabase;
use x13allegro\Component\Logger\LogEnv;
use x13allegro\Component\Logger\LogMailer;
use x13allegro\Component\Logger\LogType;
use x13allegro\Component\PHPOptions;
use x13allegro\Component\ProcessLock;
use x13allegro\SyncManager\Offer\OfferProcessManager;
use x13allegro\SyncManager\Offer\OfferEventListener;
use x13allegro\SyncManager\Offer\Updater\PriceUpdater;
use x13allegro\SyncManager\Offer\Updater\PublicationStatusActive;
use x13allegro\SyncManager\Offer\Updater\PublicationStatusEnd;
use x13allegro\SyncManager\Offer\Updater\QuantityUpdater;
use x13allegro\SyncManager\Offer\Updater\PriorityQuantityUpdater; // Dodano
use x13allegro\SyncManager\Order\OrderEventListener;

// set correct recommended php ini options
$phpOptions = PHPOptions::getRecommendedOptions();
set_time_limit($phpOptions['maxExecutionTime']);
ini_set('memory_limit', $phpOptions['memoryLimit']);

$start = microtime(true);
$error = false;
$log = Log::instance()->env(LogEnv::SYNC());
$module = Module::getInstanceByName('x13allegro');

echo '<pre>';

if (!Module::getModuleIdByName($module->name)) {
    $error = 'Module is not installed';
} else if (!$module->active) {
    $error = 'Module is not active';
} else if (Tools::getValue('token') != XAllegroConfiguration::get('SYNC_TOKEN')) {
    $error = 'Token error';
}

if ($error) {
    $log->error(LogType::CRON_BOOTSTRAP(), $error);
    exit;
}

try {
    $processLock = new ProcessLock('sync', $module);
} catch (Exception $e) {
    $error = (string)$e;
    exit;
}

if (false !== ($lockTime = ProcessLock::isLocked())) {
    $log->error(LogType::CRON_MAX_ONE_INSTANCE(), "LOCKED BY $lockTime sec");

    if (!$processLock->isNoLock()) {
        exit;
    }
}

$log->blank('sync-start');
$processLock->lock();

// disable PrestaShop Cache during sync process
if (version_compare(_PS_VERSION_, '1.6.1.0', '>=')) {
    Db::getInstance()->disableCache();
}
if (method_exists('ObjectModel', 'disableCache')) {
    ObjectModel::disableCache();
}

// check if configuration is valid
$module->config->checkAndInstallDependencies();

$log->config([
    'module' => X13_ALLEGRO_VERSION,
    'prestashop' => _PS_VERSION_,
    'php' => phpversion()
]);
$log->config(XAllegroConfiguration::getMultiple([
    'QUANITY_CHECK',
    'QUANITY_ALLEGRO_UPDATE',
    'QUANITY_ALLEGRO_ALWAYS_MAX',
    'QUANITY_ALLEGRO_VALUE_MAX',
    'CLOSE_AUCTION_TRESHOLD',
    'PRODUCT_ASSOC_CLOSE_UNACTIVE',
    'PRODUCT_ASSOC_CLOSE_UNACTIVE_DB',
    'PRODUCT_ASSOC_CLOSE_DELETED'
]));
$log->config(XAllegroConfiguration::getMultiple([
    'QUANTITY_AUTO_RENEW',
    'QUANTITY_AUTO_RENEW_THRESHOLD',
    'PRODUCT_ASSOC_RENEW_ONLY_ACTIVE',
    'PRODUCT_ASSOC_RENEW_ACTIVE',
    'PRODUCT_ASSOC_RENEW_ACTIVE_DB'
]));
$log->config(XAllegroConfiguration::getMultiple([
    'IMPORT_ORDERS',
    'ORDER_IMPORT_UNASSOC_PRODUCTS',
    'ORDER_IMPORT_UNASSOC_SUMMARY',
    'QUANITY_SHOP_UPDATE',
    'ORDER_ALLEGRO_SEND_SHIPPING'
]));
$log->config(XAllegroConfiguration::getMultiple([
    'UPDATE_OFFERS_CHUNK',
    'IMPORT_ORDERS_CHUNK',
    'DELETE_ARCHIVED_OFFERS',
    'QUANITY_ALLEGRO_OOS',
    'QUANITY_ALLEGRO_HOOK_SKIP'
]));

// check offers processes
$log->blank('process-manager-start');
(new OfferProcessManager())->checkProcessesStatus();
$log->blank('process-manager-end');

// Offers can be active on many accounts at the same time
// so we need to listen events separately for all accounts
$accounts = (new PrestaShopCollection(XAllegroAccount::class))
    ->where('active', '=', 1)
    ->getResults();

$log->blank('offer-event-start');
foreach ($accounts as $account) {
    try {
        $listener = new OfferEventListener($account);
        $listener->listen([
            OfferEventType::ACTIVATED,
            OfferEventType::ENDED,
            OfferEventType::ARCHIVED,
            OfferEventType::PRICE_CHANGED,
            OfferEventType::VISIBILITY_CHANGED
        ], (int)XAllegroConfiguration::get('UPDATE_OFFERS_CHUNK'));
    }
    catch (Exception $ex) {}
}
$log->blank('offer-event-end');

$log->blank('order-event-start');
foreach ($accounts as $account) {
    try {
        $listener = new OrderEventListener($account);
        $listener->listen([
            OrderEventType::FILLED_IN,
            OrderEventType::READY_FOR_PROCESSING,
            OrderEventType::AUTO_CANCELLED,
            OrderEventType::BUYER_CANCELLED,
            OrderEventType::FULFILLMENT_STATUS_CHANGED
        ], (int)XAllegroConfiguration::get('IMPORT_ORDERS_CHUNK'));
    }
    catch (Exception $ex) {}
}
$log->blank('order-event-end');

// fix Context
$context = Context::getContext();
$context->customer = null;
$context->cart = null;
$context->currency = Currency::getDefaultCurrency();

try {
    // updating Offer status on Allegro
    $log->blank('publication-status-start');
    (new PublicationStatusEnd())->updateFromCron();
    (new PublicationStatusActive())->updateFromCron();
    $log->blank('publication-status-end');

    // --- START: Nowa logika priorytetowej aktualizacji ---
    $log->blank('priority-quantities-start');
    try {
        $priorityUpdaterPath = dirname(__FILE__) . '/classes/php81/SyncManager/Offer/Updater/PriorityQuantityUpdater.php';
        if (file_exists($priorityUpdaterPath)) {
            require_once($priorityUpdaterPath);
            (new PriorityQuantityUpdater())->run();
        } else {
            // Fallback for older structures if needed, or just require the main one.
            require_once (dirname(__FILE__) . '/classes/SyncManager/Offer/Updater/PriorityQuantityUpdater.php');
            (new PriorityQuantityUpdater())->run();
        }
    } catch (Exception $ex) {
        $log->exception($ex);
    }
    $log->blank('priority-quantities-end');
    // --- END: Nowa logika priorytetowej aktualizacji ---
    
    // updating Offer stock on Allegro
    $log->blank('quantities-start');
    (new QuantityUpdater())->updateFromCron();
    $log->blank('quantities-end');

    // updating Offer prices on Allegro
    $log->blank('prices-start');
    (new PriceUpdater())->updateFromCron();
    $log->blank('prices-end');

    // send Order tracking number to Allegro
    $log->blank('shipping-start');
    XAllegroSyncShipping::syncShipping();
    $log->blank('shipping-end');
}
catch (Exception $ex) {
    $log->exception($ex);
}

XAllegroAuction::deleteArchivedAuctions((int)XAllegroConfiguration::get('DELETE_ARCHIVED_OFFERS'));
XAllegroAttachment::deleteManuallyUploadedAttachments();

LogDatabase::clearLogs();
LogMailer::sendLogs();

$processLock->unLock();
$log->blank('sync-end');

echo sprintf('exec time (sec): %.2f', microtime(true) - $start);
echo '</pre>';
exit;