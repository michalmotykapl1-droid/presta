<?php
/**
 * AdminXAllegroCategorySuggestController
 *
 * AJAX controller: probes Allegro for categories based on EANs taken from a PrestaShop category.
 * Presta 8.2.1 (PHP 8.1)
 */
use PrestaShop\PrestaShop\Adapter\SymfonyContainer;
use X13Allegro\Service\CategoryMatcherService;

if (!defined('_PS_VERSION_')) {
    exit;
}

class AdminXAllegroCategorySuggestController extends ModuleAdminController
{
    public $ajax = true;

    public function __construct()
    {
        $this->bootstrap = true;
        $this->display = 'json';
        parent::__construct();
    }

    /**
     * AJAX endpoint: action=probe
     * Params:
     *  - id_category (int) required when mode=tree
     *  - query (string) optional when mode=search
     *  - mode ('tree'|'search') default 'tree'
     *  - use_ean (bool) default true
     *  - ean_limit (int) default 5
     *  - debug (bool) default false
     */
    public function ajaxProcessProbe()
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $mode = Tools::getValue('mode', 'tree');
            $useEan = (bool)Tools::getValue('use_ean', true);
            $eanLimit = (int)Tools::getValue('ean_limit', 5);
            $debug = (bool)Tools::getValue('debug', false);

            /** @var Module $module */
            $module = Module::getInstanceByName('x13allegro');
            if (!$module) {
                throw new \RuntimeException('Module x13allegro not found.');
            }

            $service = new CategoryMatcherService($module);

            $result = [];
            if ($mode === 'tree') {
                $idCategory = (int)Tools::getValue('id_category');
                if ($idCategory <= 0) {
                    throw new \InvalidArgumentException('Missing id_category.');
                }
                if ($useEan) {
                    $result = $service->suggestFromPrestaCategoryByEans($idCategory, $eanLimit, $debug);
                } else {
                    $result = $service->suggestFromPrestaCategoryByNames($idCategory, $eanLimit, $debug);
                }
            } else { // mode === 'search'
                $query = (string)Tools::getValue('query', '');
                if ($query === '') {
                    throw new \InvalidArgumentException('Missing query.');
                }
                if ($useEan) {
                    $result = $service->suggestFromSearchByEans($query, $eanLimit, $debug);
                } else {
                    $result = $service->suggestFromKeyword($query, $debug);
                }
            }

            die(Tools::jsonEncode(['ok' => true, 'data' => $result], JSON_UNESCAPED_UNICODE));
        } catch (\Throwable $e) {
            $payload = [
                'ok' => false,
                'error' => $e->getMessage(),
                'type' => get_class($e),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ];
            die(Tools::jsonEncode($payload, JSON_UNESCAPED_UNICODE));
        }
    }
}
