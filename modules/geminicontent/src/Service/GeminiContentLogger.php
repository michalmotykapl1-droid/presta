<?php
namespace GeminiContent\Service;

if (!defined('_PS_VERSION_')) {
    exit;
}

class GeminiContentLogger
{
    const LOG_FILE = 'debug.log';
    const LOG_PATH = _PS_MODULE_DIR_ . 'geminicontent/';

    public static function log($message, $level = 'INFO')
    {
        // Logowanie jest aktywne tylko w trybie deweloperskim PrestaShop
        if (!defined('_PS_MODE_DEV_') || _PS_MODE_DEV_ !== true) {
            return;
        }

        $filePath = self::LOG_PATH . self::LOG_FILE;
        $timestamp = date('Y-m-d H:i:s');
        
        // Pobranie informacji o miejscu wywołania logera
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $caller = $backtrace[1] ?? null;

        $logMessage = sprintf(
            "[%s] [%s] %s (in %s on line %d)" . PHP_EOL,
            $timestamp,
            $level,
            $message,
            $caller['file'] ?? 'unknown file',
            $caller['line'] ?? 'unknown line'
        );

        @file_put_contents($filePath, $logMessage, FILE_APPEND);
    }

    public static function debug($message)
    {
        self::log($message, 'DEBUG');
    }

    public static function info($message)
    {
        self::log($message, 'INFO');
    }

    public static function warning($message)
    {
        self::log($message, 'WARNING');
    }

    public static function error($message)
    {
        self::log($message, 'ERROR');
    }
}