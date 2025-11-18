<?php

$baseDir = _PS_MODULE_DIR_.'x13allegro/';

if (!defined('X13_ALLEGRO_DIR')) {
    define('X13_ALLEGRO_DIR', $baseDir);
    define('X13_ALLEGRO_BACKUPS_DIR', X13_ALLEGRO_DIR . 'backups/');
    define('X13_ALLEGRO_CACHE_DIR', X13_ALLEGRO_DIR . 'cache/');
    define('X13_ALLEGRO_CACHE_JSON_DIR', X13_ALLEGRO_DIR . 'cache/json/');
    define('X13_ALLEGRO_CACHE_JWT_DIR', X13_ALLEGRO_DIR . 'cache/jwt/');
    define('X13_ALLEGRO_LOG_DIR', X13_ALLEGRO_DIR . 'log/');
    define('X13_ALLEGRO_MAIL_DIR', X13_ALLEGRO_DIR . 'mails/');
    define('X13_ALLEGRO_TOOLS_DIR', X13_ALLEGRO_DIR . 'tools/');
    define('X13_ALLEGRO_JSON_DIR', X13_ALLEGRO_DIR . 'json/');
    define('X13_ALLEGRO_ATTACHMENT_DIR', _PS_DOWNLOAD_DIR_ . 'xallegro/');
    define('X13_ALLEGRO_IMG', _PS_IMG_DIR_ . 'xallegro/');
    define('X13_ALLEGRO_IMG_TEMPLATE', X13_ALLEGRO_IMG . 'template/');
    define('X13_ALLEGRO_IMG_ADDITIONAL', X13_ALLEGRO_IMG . 'product/');

    define('X13_ALLEGRO_IMG_TEMPLATE_URL', 'img/xallegro/template/');
    define('X13_ALLEGRO_IMG_ADDITIONAL_URL', 'img/xallegro/product/');

    define('X13_ALLEGRO_IMPORT_ORDERS_CHUNK', 50);
    define('X13_ALLEGRO_UPDATE_OFFERS_CHUNK', 200);

    define('X13_ALLEGRO_LOG_STACK_DAYS', 31);
    define('X13_ALLEGRO_CURL_TIMEOUT', 20);
    define('X13_ALLEGRO_CURL_IMAGE_TIMEOUT', 360);
    define('X13_ALLEGRO_CURL_ATTACHMENT_TIMEOUT', 360);
    define('X13_ALLEGRO_TIMEOUT', 1800);
    define('X13_ALLEGRO_MEMORY_LIMIT', '512M');

    if (!defined('X13_ALLEGRO_TEMPLATE_IMAGES_NB')) {
        define('X13_ALLEGRO_TEMPLATE_IMAGES_NB', 5);
    }
}

if (!defined('X13_ION_ALLEGRO')) {
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

    if (file_exists(X13_ALLEGRO_DIR . 'dev')) {
        $x13IonVer = 'php5';
        $phpVersions = 'php5';
    }

    define('X13_ION_ALLEGRO_VERSIONS', $phpVersions);
    define('X13_ION_ALLEGRO', $x13IonVer);
}

// Autoload classes
if (!class_exists('Psr4Autoloader')) {
    require_once(X13_ALLEGRO_TOOLS_DIR . 'Psr/Autoloader/Psr4Autoloader.php');
}
$loader = new Psr4Autoloader();
$loader->register();
$loader->addNamespace('x13allegro', $baseDir . 'classes/' . X13_ION_ALLEGRO . '/');
$loader->addNamespace('JsonMapper', $baseDir . 'tools/JsonMapper/');
$loader->addNamespace('DeepCopy', $baseDir . 'tools/DeepCopy/');
$loader->addNamespace('MyFirebase', $baseDir . 'tools/Firebase/');
$loader->addNamespace('MyCLabs', $baseDir . 'tools/MyCLabs/');
$loader->addNamespace('UUID', $baseDir . 'tools/UUID/');

// Autoload legacy classes
require_once($baseDir . 'classes/' . X13_ION_ALLEGRO . '/XAllegroAutoLoader.php');
XAllegroAutoLoader::getInstance()->autoload();
