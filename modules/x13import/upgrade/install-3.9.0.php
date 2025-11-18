<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once (dirname(__FILE__) . '/../x13import.php');

/**
 * @param $module x13import
 * @return bool
 */
function upgrade_module_3_9_0($module)
{
    $module->reinstallTabs();

    return true;
}
