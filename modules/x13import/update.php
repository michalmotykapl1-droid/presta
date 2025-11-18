<?php

require_once (dirname(__FILE__) . '/../../config/config.inc.php');
require_once (dirname(__FILE__) . '/x13import.php');

use x13import\Adapter\DbAdapter;
use x13import\Component\Logger;
use x13import\Component\PHPOptions;
use x13import\Component\ProcessLock;
use x13import\Component\Token;
use x13import\Importer\ItemRepository;

// set correct recommended php ini options
$phpOptions = PHPOptions::getRecommendedOptions();
set_time_limit($phpOptions['maxExecutionTime']);
ini_set('memory_limit', $phpOptions['memoryLimit']);

$module = Module::getInstanceByName('x13import');
$updateStart = microtime(true);
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

if (XImportConfiguration::get('FINGERPRINT_REGENERATION_472')) {
    Logger::instance()->show()->error('update', 'Module need to regenerate items fingerprint. Exec cron.php first.');
    exit;
}

try {
    $processLock = new ProcessLock('update', $module);
} catch (Exception $e) {
    $error = $e->getMessage();
}

if ($error) {
    Logger::instance()->show()->error('update', $error);
    exit;
}

if (false !== ($lockTime = ProcessLock::isLocked())) {
    Logger::instance()->show()->error('update', "UPDATE LOCKED BY: $lockTime sec");

    if (!$processLock->isIgnoreLimits()) {
        exit;
    }
}

if (!ConfigurationCore::get('PS_ALLOW_HTML_IFRAME')) {
    Logger::instance()->show()->error('update', 'SET ALLOW IFRAMES IN HTML');

    $processLock->unLock();
    exit;
}
if (!Combination::isFeatureActive()) {
    Logger::instance()->show()->error('update', 'ENABLE PRODUCT COMBINATIONS');

    $processLock->unLock();
    exit;
}

Logger::instance()->blank('update START');
$processLock->lock();

// remove destroyed tmp files
foreach (scandir(_PS_TMP_IMG_DIR_) as $filename) {
	if (strpos($filename, X_IMPORT_IMG_TMP_PREFIX) === 0) {
		unlink(_PS_TMP_IMG_DIR_ . DIRECTORY_SEPARATOR . $filename);
	}
}

// remove destroyed image db entries
XImportCore::removeDestroyedImageIds();

$XImportCore = new XImportCore();
$XImportCore->beforeUpdate();

$itemLimits = (int) Tools::getValue('itemLimits', X_IMPORT_ITEMS_LIMIT);
$itemsDownloadLimit = (int) Tools::getValue('itemsDownloadLimit', X_IMPORT_ITEMS_DOWNLOAD_LIMIT);

$results = ItemRepository::getItemsForImport($itemLimits);

// brak produktow do zaimportowania, sprawdzam czy sa do zaimportowania jakies zdjecia lub zalaczniki
if (empty($results)) {
	$images =
    $attachments = [];

    if (XImportConfiguration::get('UPDATE_IMAGES')) {
        $images = ItemRepository::getItemsImagesForImport($itemsDownloadLimit);
    }

    if (XImportConfiguration::get('UPDATE_ATTACHMENTS') && empty($images)) {
        $attachments = ItemRepository::getItemsAttachmentsForImport($itemsDownloadLimit);
    }

    try {
        if (!empty($images)) {
            $XImportCore->addImages($images);
        }
        else if (!empty($attachments)) {
            $XImportCore->addAttachments($attachments);
        }
	}
	catch (Exception $ex) {
        Logger::instance()->exception($ex);

        $processLock->unLock();
		exit;
	}
}
else {
	// dodajemy produkty
	foreach ($results as $item) {
		try {
			Db::getInstance()->execute('START TRANSACTION');
			$XImportCore->doSingle($item);

            Db::getInstance()->execute('
                DELETE xi, xia, xif
                FROM `' . _DB_PREFIX_ . 'ximport_item` xi
                LEFT JOIN `' . _DB_PREFIX_ . 'ximport_item_attribute` xia
                    ON (xia.`fingerprint_item` = xi.`fingerprint`)
                LEFT JOIN `' . _DB_PREFIX_ . 'ximport_item_feature` xif
                    ON (xif.`code` = xi.`code`)
                WHERE xi.`id_ximport_item` = ' . (int)$item['id_ximport_item']
            );

			Db::getInstance()->execute('COMMIT');
		}
		catch (Exception $ex) {
            Db::getInstance()->execute('ROLLBACK');
            Logger::instance()->exception($ex);

            $processLock->unLock();
            exit;
		}
	}
}

if (XImportConfiguration::get('CLEAR_CACHE')) {
    try {
        Hook::exec('actionProductUpdate');
    } catch (Exception $ex) {
        Logger::instance()->exception($ex);
    }
}

$updateMemoryPeakUsage = convertMemory(memory_get_peak_usage());
$updateExecuteTime = number_format((microtime(true) - $updateStart), 2, '.', '');
Logger::instance()->show()->info("MEMORY PEAK USAGE: $updateMemoryPeakUsage; PROCESS TIME: $updateExecuteTime", 'update-complete');
Logger::instance()->blank('update END');

$processLock->unLock();

echo '</xmp>';
exit;
