<?php

require_once (dirname(__FILE__) . '/../../config/config.inc.php');
require_once (dirname(__FILE__) . '/x13import.php');

use x13import\Adapter\DbAdapter;
use x13import\Component\Logger;
use x13import\Component\PHPOptions;
use x13import\Component\ProcessLock;
use x13import\Component\Token;
use x13import\Wholesaler\WholesalerConfiguration;

// set correct recommended php ini options
$phpOptions = PHPOptions::getRecommendedOptions();
set_time_limit($phpOptions['maxExecutionTime']);
ini_set('memory_limit', $phpOptions['memoryLimit']);

$module = Module::getInstanceByName('x13import');
$cronStart = microtime(true);
$error = false;

echo '<xmp>';

if (!Module::getModuleIdByName($module->name)) {
    $error = 'Module is not installed';
}
if (!$error && !$module->active) {
    $error = 'Module is not active';
}

// check for proper DB variables
$dbVariables = DbAdapter::getGlobalVariables();
if (!$error) {
    if ($dbVariables['max_allowed_packet'] < X_IMPORT_DB_MAX_ALLOWED_PACKET) {
        $error = 'MySQL VARIABLE "max_allowed_packet" (' . $dbVariables['max_allowed_packet'] . ') is too low for the module to function properly. Min ' . X_IMPORT_DB_MAX_ALLOWED_PACKET . ' needed';
    }
    else if ($dbVariables['wait_timeout'] < X_IMPORT_DB_WAIT_TIMEOUT) {
        $error = 'MySQL VARIABLE "wait_timeout" (' . $dbVariables['wait_timeout'] . ') is too low for the module to function properly. Min ' . X_IMPORT_DB_WAIT_TIMEOUT . ' needed';
    }
}

if (!$error && !Token::isTokenValid(Tools::getValue('token'))) {
    $error = 'Token error';
}

try {
    $processLock = new ProcessLock('cron', $module);
} catch (Exception $e) {
    $error = $e->getMessage();
}

if ($error) {
    Logger::instance()->show()->error('cron', $error);
    exit;
}

if (false !== ($lockTime = ProcessLock::isLocked())) {
    Logger::instance()->show()->error('cron', "CRON LOCKED BY: $lockTime sec");

    if (!$processLock->isIgnoreLimits()) {
        exit;
    }
}

Logger::instance()->blank('cron START');

Logger::instance()->show()->info(phpversion(), 'PHP_VERSION');
Logger::instance()->show()->info($module->version, 'MODULE_VERSION');


$processLock->lock();
$allowAfterCron = true;

XImportCore::truncateItemTables();

foreach (XImportWholesalers::getWholesalers() as $wholesaler) {
    $wholesalerTimeStart = microtime(true);
    $wholesalerConfiguration = new WholesalerConfiguration($wholesaler['name']);

    if ($wholesalerConfiguration->IMPORT_DISABLE_WHOLESALER) {
        continue;
    }

    $itemsCount = 0;

    try {
        /** @var XImportWholesalers $class */
        $class = new $wholesaler['className'];

        if (method_exists($class, 'downloadFiles')) {
            $class->downloadFiles();
        }

        $class->importProducts();
        $itemsCount = $class->countProducts();

        Logger::instance()->show()->wholesaler($wholesaler['name'], "AVAILABLE ITEMS: $itemsCount; IMPORTED ITEMS: {$class->countProductsImported()}");
    }
    catch (Exception $exception) {
        Logger::instance()->show()->wholesaler($wholesaler['name'], $exception->getMessage());
    }

    if ($itemsCount == 0) {
        if (isset($class->_DOWNLOAD_BEFORE_IMPORT) && $class->_DOWNLOAD_BEFORE_IMPORT == true) {
            echo 'FILE DOWNLOADED: ' . $wholesaler['className'] . ' -- MEMORY USAGE: ' . convertMemory(memory_get_usage()) . ' -- EXECUTE TIME: ' . number_format((microtime(true) - $wholesalerTimeStart), 2, '.', '') . "\n";
        }
        else {
            Logger::instance()->show()->wholesaler($wholesaler['name'], "There are no products to import");
        }

        $processLock->unLock(false);
        exit;
    }
    else {
        $wholesalerMemoryUsage = convertMemory(memory_get_usage());
        $wholesalerExecuteTime = number_format((microtime(true) - $wholesalerTimeStart), 2, '.', '');
        Logger::instance()->show()->wholesaler($wholesaler['name'], "MEMORY USAGE: $wholesalerMemoryUsage; PROCESS TIME: $wholesalerExecuteTime");
    }

    if ($wholesalerConfiguration->IMPORT_RESTRICTION_ENABLED && $wholesalerConfiguration->IMPORT_RESTRICTION_CRON_BLOCK) {
        if (XImportCore::checkAfterCronBlock($class->_code, (int)$wholesalerConfiguration->IMPORT_RESTRICTION_CRON_BLOCK)) {
            Logger::instance()->show()->wholesaler($wholesaler['name'], "There are no more than {$wholesalerConfiguration->IMPORT_RESTRICTION_CRON_BLOCK} items available");

            $allowAfterCron &= false;
        }
    }
    
    unset($class);
}

try {
    Hook::exec('actionBeforeX13ImportAfterCron');
} catch (Exception $ex) {
    Logger::instance()->exception($ex);
}

if (XImportConfiguration::get('FINGERPRINT_REGENERATION_472')) {
    Logger::instance()->show()->blank('fingerprint regeneration START');
    if (XImportCore::fingerprintRegenerate()) {
        Logger::instance()->show()->blank('fingerprint regeneration END');
    }
}

if ($allowAfterCron) {
    Logger::instance()->blank('after cron START');
    $afterCronTimeStart = microtime(true);
    XImportCore::afterCron();

    $afterCronMemoryUsage = convertMemory(memory_get_usage());
    $afterCronExecuteTime = number_format((microtime(true) - $afterCronTimeStart), 2, '.', '');
    Logger::instance()->show()->info("MEMORY USAGE: $afterCronMemoryUsage; PROCESS TIME: $afterCronExecuteTime", 'products-update');
    Logger::instance()->blank('after cron END');

    try {
        if (XImportConfiguration::get('CLEAR_CACHE')) {
            Hook::exec('actionProductUpdate');
            Logger::instance()->info('Hook actionProductUpdate completed');
        }

        if (XImportConfiguration::get('SEARCH_INDEX')) {
            Search::indexation();
            Logger::instance()->info('Search indexation completed');
        }
    } catch (Exception $ex) {
        Logger::instance()->exception($ex);
    }
}

$cronMemoryPeakUsage = convertMemory(memory_get_peak_usage());
$cronExecuteTime = number_format((microtime(true) - $cronStart), 2, '.', '');
Logger::instance()->show()->info("MEMORY PEAK USAGE: $cronMemoryPeakUsage; PROCESS TIME: $cronExecuteTime", 'cron-complete');
Logger::instance()->blank('cron END');
Logger::instance()->clearLogs();

$processLock->unLock();

echo '</xmp>';
exit;
