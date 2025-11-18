<?php

// module directory
$baseDir = _PS_MODULE_DIR_ . 'x13import/';

// default module settings
$moduleSettings = [
    'imgMaxSize' => 9,
    'imgMaxPixel' => 9000,
    'itemsQueryLimit' => 500,
    'itemsDownloadLimit' => 25,
    'phpMaxExecutionTime' => 600,
    'phpMemoryLimit' => '512M',
    'debug' => false
];

// include custom module settings
if (file_exists($baseDir . 'x13import.custom.php')) {
    $moduleSettings = array_merge($moduleSettings, include_once ($baseDir . 'x13import.custom.php'));
}

if (!defined('X13_IMPORT_DIR')) {
    define('X13_IMPORT_DIR', $baseDir);
    define('X13_IMPORT_CACHE_DIR', X13_IMPORT_DIR . 'cache/');
    define('X13_IMPORT_WHOLESALERS_DIR', X13_IMPORT_DIR . 'wholesalers/');
    define('X13_IMPORT_LOG_DIR', X13_IMPORT_DIR . 'log/');
    define('X13_IMPORT_TOOLS_DIR', X13_IMPORT_DIR . 'tools/');

    // images limits & settings
    define('X_IMPORT_IMG_MAX_SIZE', $moduleSettings['imgMaxSize']);
    define('X_IMPORT_IMG_MAX_PIXELS', $moduleSettings['imgMaxPixel']);
    define('X_IMPORT_IMG_TMP_PREFIX', 'PS_X_');

    // items limits
    define('X_IMPORT_ITEMS_LIMIT', $moduleSettings['itemsQueryLimit']);
    define('X_IMPORT_ITEMS_DOWNLOAD_LIMIT', $moduleSettings['itemsDownloadLimit']);

    // PHP minimum values
    define('X_IMPORT_TIMEOUT', $moduleSettings['phpMaxExecutionTime']);
    define('X_IMPORT_MEMORY_LIMIT', $moduleSettings['phpMemoryLimit']);

    // SQL minimum values
    define('X_IMPORT_DB_MAX_ALLOWED_PACKET', 15777216);
    define('X_IMPORT_DB_WAIT_TIMEOUT', 30);

    // module debug mode
    define('X_IMPORT_DEBUG', $moduleSettings['debug']);
}

if (!defined('X13_ION_IMPORT')) {
    if (PHP_VERSION_ID >= 80100) {
        $x13IonVer = 'php81';
	} else if (PHP_VERSION_ID >= 70100) {
        $x13IonVer = 'php71';
    } else if (PHP_VERSION_ID >= 70000) {
        $x13IonVer = 'php70';
    } else {
        $x13IonVer = 'php5';
    }

    $phpVersions = 'php5;php70;php71;php81';

    if (file_exists(X13_IMPORT_DIR . 'dev')) {
        $x13IonVer = 'php5';
        $phpVersions = 'php5';
    }

    define('X13_ION_IMPORT_VERSIONS', $phpVersions);
    define('X13_ION_IMPORT', $x13IonVer);
}

// Autoload classes
if (!class_exists('Psr4Autoloader')) {
    require_once(X13_IMPORT_TOOLS_DIR . 'Psr/Autoloader/Psr4Autoloader.php');
}
$loader = new Psr4Autoloader();
$loader->register();
$loader->addNamespace('x13import', $baseDir . 'classes/' . X13_ION_IMPORT . '/');
$loader->addNamespace('x13importMyCLabs', $baseDir . 'tools/MyCLabs');
$loader->addNamespace('Box\Spout', $baseDir . 'tools/Spout/');

// Autoload legacy classes
require_once($baseDir . 'classes/' . X13_ION_IMPORT . '/XImportAutoLoader.php');
XImportAutoLoader::getInstance()->autoload();
