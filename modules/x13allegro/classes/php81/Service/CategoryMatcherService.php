<?php

namespace x13allegro\Service;

use Db;
use DbQuery;
use Tools;
use Context;
use ReflectionClass;
use ReflectionProperty;
use x13allegro\Api\DataProvider\CategoriesProvider;

/**
 * CategoryMatcherService – fix5
 * - Provider first
 * - Fallback: cURL full tree (root + children by parent.id, with pagination)
 * - Token: try API methods/props, then DB column guess from xallegro_account
 * - Marketplace id only for root queries
 * - Strong logging + counts
 */
class CategoryMatcherService
{
    private $catProvider = null;
    private $idShop;
    private $allegroApi;
    private $marketplaceId = 'allegro-pl';
    private $activeAccountId = null;

    public function __construct($idShop = null, $allegroApi = null, $marketplaceId = 'allegro-pl')
    {
        $this->idShop = (int)($idShop ?: Context::getContext()->shop->id);
        $this->allegroApi = $allegroApi;
        $this->marketplaceId = (string)$marketplaceId ?: 'allegro-pl';
    }

    private function logLine($line)
    {
        $logFile = dirname(__FILE__, 4) . '/cache/debug_log.txt';
        file_put_contents($logFile, '[' . date('Y-m-d H:i:s') . '] ' . $line . "\n", FILE_APPEND);
    }

    private function lazyInitApi()
    {
        // reset log on each run of "Pobierz/Odśwież"
        $logFile = dirname(__FILE__, 4) . '/cache/debug_log.txt';
        if (file_exists($logFile) && filesize($logFile) > 0) {
            @unlink($logFile);
        }
        $this->logLine('--- Uruchomiono lazyInitApi ---');

        if ($this->allegroApi !== null) {
            $this->logLine('API już zainicjowane, wychodzę. Obiekt: ' . get_class($this->allegroApi));
            return;
        }

        try {
            if (!class_exists('XAllegroAccount') || !class_exists('\\x13allegro\\Api\\XAllegroApi')) {
                $this->logLine('BŁĄD: Brak klas XAllegroAccount lub XAllegroApi.');
                return;
            }

            $q = new DbQuery();
            $q->select('id_xallegro_account');
            $q->from('xallegro_account');
            $q->where('active = 1 AND sandbox = 0');
            $q->orderBy('id_xallegro_account ASC');
            $id_account = (int)Db::getInstance()->getValue($q, false);
            $this->activeAccountId = $id_account;
            $this->logLine('Znalezione ID konta: ' . var_export($id_account, true));

            if ($id_account > 0) {
                $this->logLine('Znaleziono ID konta: ' . $id_account . '. Próba utworzenia obiektów...');
                $acc = new \XAllegroAccount($id_account);
                if (isset($acc->id) && $acc->active) {
                    $this->allegroApi = new \x13allegro\Api\XAllegroApi($acc);
                    $this->logLine('SUKCES: Utworzono obiekt API typu: ' . get_class($this->allegroApi));
                } else {
                    $this->logLine('BŁĄD: Obiekt konta nie jest aktywny lub nie ma ID.');
                }
            } else {
                $this->logLine('BŁĄD KRYTYCZNY: Nie znaleziono żadnego aktywnego konta.');
            }
        } catch (\Throwable $e) {
            $this->logLine('WYJĄTEK PHP: ' . $e->getMessage());
        }
    }

    private function getCategoriesProvider()
    {
        $this->lazyInitApi();
        if ($this->catProvider === null && $this->allegroApi !== null) {
            try {
                $this->catProvider = new CategoriesProvider($this->allegroApi);
                if (function_exists('get_class_methods')) {
                    $methods = @get_class_methods($this->allegroApi);
                    if (is_array($methods)) {
                        $this->logLine('Metody XAllegroApi: ' . implode(', ', $methods));
                    }
                }
            } catch (\Throwable $e) {
                $this->logLine('KRYTYCZNY BŁĄD PODCZAS TWORZENIA CategoriesProvider: ' . $e->getMessage());
                $this->catProvider = null;
            }
        }
        return $this->catProvider;
    }

    private function getAllegroTreeFromLocalCache()
    {
        $moduleDir = dirname(__FILE__, 4);
        $cacheFile = $moduleDir . '/cache/json/kategorie_allegro.json';
        if (!is_readable($cacheFile)) {
            return [];
        }
        $data = json_decode(@file_get_contents($cacheFile), true);
        return is_array($data) ? $data : [];
    }

    /** Pobiera bearer z API (metody/właściwości) lub z DB (xallegro_account) */
    private function getBearerTokenFromApi()
    {
        // 1) obiekt API
        if ($this->allegroApi) {
            $candidates = ['getAccessToken','getToken','getOAuthToken','getOauthToken','getBearer','accessToken','token'];
            foreach ($candidates as $cand) {
                try {
                    if (method_exists($this->allegroApi, $cand)) {
                        $res = $this->allegroApi->{$cand}();
                        if (is_array($res) && isset($res['access_token'])) return (string)$res['access_token'];
                        if (is_string($res) && $res !== '') return $res;
                    }
                } catch (\Throwable $e) { /* ignore */ }
            }
            try {
                $ref = new ReflectionClass($this->allegroApi);
                foreach (['accessToken','token','access_token','bearer','jwt'] as $prop) {
                    if ($ref->hasProperty($prop)) {
                        $p = $ref->getProperty($prop);
                        if ($p instanceof ReflectionProperty) {
                            $p->setAccessible(true);
                            $val = $p->getValue($this->allegroApi);
                            if (is_array($val) && isset($val['access_token'])) return (string)$val['access_token'];
                            if (is_string($val) && $val !== '') return $val;
                        }
                    }
                }
            } catch (\Throwable $e) { /* ignore */ }
        }

        // 2) baza danych (kolumny tokenowe w xallegro_account)
        try {
            $id = (int)$this->activeAccountId;
            if ($id > 0) {
                $row = Db::getInstance()->getRow('SELECT * FROM `' . _DB_PREFIX_ . 'xallegro_account` WHERE `id_xallegro_account`=' . (int)$id);
                if (is_array($row)) {
                    $best = null;
                    foreach ($row as $k => $v) {
                        if (!is_string($v)) continue;
                        $kl = Tools::strtolower($k);
                        if (strpos($kl, 'token') !== false || strpos($kl, 'access') !== false) {
                            // prefer coś, co wygląda jak JWT (dwie kropki) lub długość > 40
                            if ((substr_count($v, '.') >= 2) || strlen($v) > 40) {
                                $best = $v;
                                break;
                            }
                            if ($best === null) $best = $v;
                        }
                    }
                    if ($best) {
                        $this->logLine('Token znaleziony w DB w kolumnie podobnej do "token".');
                        return $best;
                    }
                }
            }
        } catch (\Throwable $e) {
            $this->logLine('Błąd pobierania tokenu z DB: ' . $e->getMessage());
        }

        return null;
    }

    /** cURL GET helper */
    private function curlGet($path, array $query = [], &$httpCode = 0)
    {
        $base = 'https://api.allegro.pl';
        // marketplace.id tylko gdy nie pytamy po parent.id (dla pewności)
        if (!isset($query['parent.id'])) {
            $query['marketplace.id'] = $this->marketplaceId;
        }
        $url = $base . $path . '?' . http_build_query($query);
        $headers = [
            'Accept: application/vnd.allegro.public.v1+json',
            'User-Agent: x13allegro-category-matcher/1.0',
        ];
        $token = $this->getBearerTokenFromApi();
        if ($token) {
            $headers[] = 'Authorization: Bearer ' . $token;
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $body = curl_exec($ch);
        $errno = curl_errno($ch);
        $err  = curl_error($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($errno) {
            $this->logLine('cURL error [' . $path . ']: ' . $errno . ' ' . $err);
            return null;
        }
        $this->logLine('cURL GET ' . $url . ' -> HTTP ' . $httpCode);
        $json = json_decode((string)$body, true);
        if (!is_array($json)) {
            $this->logLine('Nieprawidłowy JSON z Allegro dla ' . $path . '. Body (200 znaków): ' . substr((string)$body, 0, 200));
            return null;
        }
        return $json;
    }

    /** Pobiera listę kategorii (jednego poziomu) dla danego parenta (lub root, gdy $parentId === null) */
    private function fetchLevel(?string $parentId, $limit = 200): array
    {
        $http = 0;
        $items = [];
        $offset = 0;

        for ($i = 0; $i < 2000; $i++) { // bezpiecznik
            $query = ['limit' => $limit, 'offset' => $offset];
            if ($parentId !== null) {
                $query['parent.id'] = $parentId;
            }
            $resp = $this->curlGet('/sale/categories', $query, $http);
            if (!is_array($resp)) {
                $this->logLine('fetchLevel: brak odpowiedzi (parent=' . ($parentId ?: 'ROOT') . ')');
                break;
            }
            $batch = [];
            if (isset($resp['categories']) && is_array($resp['categories'])) {
                $batch = $resp['categories'];
            } elseif (isset($resp['items']) && is_array($resp['items'])) {
                $batch = $resp['items'];
            }
            $this->logLine('fetchLevel: parent=' . ($parentId ?: 'ROOT') . ' offset=' . $offset . ' -> ' . count($batch) . ' szt.');
            if (empty($batch)) {
                break;
            }
            foreach ($batch as $it) {
                $items[] = [
                    'id'     => (string)($it['id'] ?? ''),
                    'name'   => (string)($it['name'] ?? ''),
                    'leaf'   => isset($it['leaf']) ? (bool)$it['leaf'] : null,
                    'parent' => isset($it['parent']['id']) ? (string)$it['parent']['id'] : null,
                ];
            }
            if (count($batch) < $limit) {
                break;
            }
            $offset += $limit;
        }
        return $items;
    }

    /** Buduje pełne drzewo, pobierając dzieci każdej kategorii aż do liści */
    private function fetchFullTree(): array
    {
        $this->logLine('Start pobierania pełnego drzewa przez cURL (root + rekursja parent.id)...');

        $rootLevel = $this->fetchLevel(null, 200);
        if (empty($rootLevel)) {
            $this->logLine('Root level pusty.');
            return [];
        }

        $visited = [];
        $makeNode = function ($raw) {
            return [
                'id' => (string)$raw['id'],
                'name' => (string)$raw['name'],
                'children' => []
            ];
        };

        // map id -> node reference
        $nodes = [];
        $roots = [];

        foreach ($rootLevel as $cat) {
            if ($cat['id'] === '') continue;
            $nodes[$cat['id']] = $makeNode($cat);
            $roots[] = &$nodes[$cat['id']];
        }

        // BFS
        $queue = array_map(function ($c) { return $c['id']; }, $rootLevel);

        $iterations = 0;
        while (!empty($queue)) {
            $iterations++;
            if ($iterations > 200000) {
                $this->logLine('Przerwano: zbyt wiele iteracji.');
                break;
            }
            $parentId = array_shift($queue);
            if (isset($visited[$parentId])) {
                continue;
            }
            $visited[$parentId] = true;

            $children = $this->fetchLevel($parentId, 200);
            if (empty($children)) {
                continue;
            }

            foreach ($children as $ch) {
                $cid = $ch['id'];
                if ($cid === '') continue;
                if (!isset($nodes[$cid])) {
                    $nodes[$cid] = $makeNode($ch);
                }
                if (!isset($nodes[$parentId])) {
                    $nodes[$parentId] = ['id'=>$parentId,'name'=>'','children'=>[]];
                }
                $nodes[$parentId]['children'][] = &$nodes[$cid];

                // dociągamy dopóki nie ma pewności, że to liść
                if ($ch['leaf'] !== true) {
                    $queue[] = $cid;
                }
            }
        }

        $this->logLine('Zakończono pobieranie drzewa. Węzłów: ' . count($nodes));
        return [[ 'id' => 'allegro-pl', 'name' => 'Allegro', 'children' => $roots ]];
    }

    public function updateAndCacheAllegroTree()
    {
        try {
            $tree = [];
            $prov = $this->getCategoriesProvider();

            // 1) Provider modułowy
            if ($prov) {
                try {
                    if (method_exists($prov, 'getMarketplaceTree')) {
                        $tree = (array)$prov->getMarketplaceTree($this->marketplaceId);
                        $this->logLine('Provider.getMarketplaceTree -> ' . (empty($tree) ? 'PUSTO' : 'DANE'));
                    } elseif (method_exists($prov, 'getTree')) {
                        $tree = (array)$prov->getTree();
                        $this->logLine('Provider.getTree -> ' . (empty($tree) ? 'PUSTO' : 'DANE'));
                    }
                } catch (\Throwable $e) {
                    $this->logLine('Provider wyjątek: ' . $e->getMessage());
                    $tree = [];
                }
            }

            // 2) Fallback: pełne drzewo przez cURL (rekursja)
            if (empty($tree)) {
                $tree = $this->fetchFullTree();
            }

            // 3) Fallback: lokalny cache
            if (empty($tree)) {
                $this->logLine('Fallback: używam lokalnego cache json.');
                $tree = $this->getAllegroTreeFromLocalCache();
            }

            if (empty($tree)) {
                return ['success' => false, 'message' => 'API Allegro zwróciło puste drzewo kategorii. (root/children puste)'];
            }

            // zapis
            $moduleDir = dirname(__FILE__, 4);
            $cacheDir  = $moduleDir . '/cache/json';
            if (!is_dir($cacheDir)) { @mkdir($cacheDir, 0775, true); }
            if (!is_dir($cacheDir) || !is_writable($cacheDir)) {
                return ['success' => false, 'message' => 'Brak praw do zapisu: ' . $cacheDir];
            }
            $cacheFile = $cacheDir . '/kategorie_allegro.json';
            @file_put_contents($cacheFile, json_encode($tree, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));

            // policz węzły
            $count = 0;
            $stack = $tree;
            while (!empty($stack)) {
                $n = array_pop($stack);
                $count++;
                if (!empty($n['children'])) {
                    foreach ($n['children'] as $ch) { $stack[] = $ch; }
                }
            }
            return ['success' => true, 'count' => $count];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Legacy "ZAPROPONUJ" wrapper -> returns structure expected by old UI.
     * - NO auto-save by default. Save only if cm_save=1 (or save=1) is sent.
     */
    public function suggestForCategory($id_category, $use_products = true, $limit = 30, $use_ean = true, $debug = false, $ean_limit = 80)
    {
        try {
            if (!class_exists('\\x13allegro\\Service\\CategoryAutoMapperService')) {
                @require_once _PS_MODULE_DIR_ . 'x13allegro/classes/php81/Service/CategoryAutoMapperService.php';
            }
        } catch (\Throwable $e) {}

        if (!class_exists('\\x13allegro\\Service\\CategoryAutoMapperService')) {
            return ['items'=>[], 'debug'=>['error'=>'CategoryAutoMapperService missing']];
        }

        $mapper = new \x13allegro\Service\CategoryAutoMapperService($this->idShop, $this->allegroApi, $this->marketplaceId);

        $sampleEans  = $use_ean ? max(1, (int)$ean_limit) : 0;
        $sampleNames = max(1, (int)$limit);
        $doSave = (bool)\Tools::getValue('cm_save', (bool)\Tools::getValue('save', false));

        $res = $mapper->autoMapPsCategory((int)$id_category, $sampleEans, $sampleNames, $doSave);

        $items = [];
        if (is_array($res) && !empty($res['allegroCategoryId'])) {
            $score = 0;
            if (($res['method'] ?? '') === 'ean') { $score = 95; }
            elseif (($res['method'] ?? '') === 'name') { $score = 70; }
            else { $score = 50; }

            $examples = [];
            if (!empty($res['stats']['ean'])) {
                foreach (array_slice($res['stats']['ean'], 0, 3) as $e) { $examples[] = $e['ean']; }
            } elseif (!empty($res['stats']['name'])) {
                foreach (array_slice($res['stats']['name'], 0, 3) as $n) { $examples[] = $n['name']; }
            }

            $items[] = [
                'id'            => (string)$res['allegroCategoryId'],
                'category_id'   => (string)$res['allegroCategoryId'],
                'path'          => (string)($res['allegroCategoryPath'] ?? ''),
                'score'         => (int)$score,
                'source'        => (string)($res['method'] ?? ''),
                'examples'      => $examples,
            ];
        }

        return [
            'items' => $items,
            'debug' => $debug ? $res : [],
        ];
    }


    /**
     * Legacy save action from controller expects signature:
     * saveMapping($id_category_ps, $allegro_cat_id, $confidence, $source, $allegro_cat_path)
     * We forward to CategoryAutoMapperService::saveMapping($id_category_ps, $allegro_cat_id, $path, $method, $confidence)
     */
    public function saveMapping($id_category_ps, $allegro_cat_id, $confidence = null, $source = '', $allegro_cat_path = '')
    {
        try {
            if (!class_exists('\\x13allegro\\Service\\CategoryAutoMapperService')) {
                @require_once _PS_MODULE_DIR_ . 'x13allegro/classes/php81/Service/CategoryAutoMapperService.php';
            }
        } catch (\Throwable $e) {}

        if (!class_exists('\\x13allegro\\Service\\CategoryAutoMapperService')) {
            return false;
        }

        $mapper = new \x13allegro\Service\CategoryAutoMapperService($this->idShop, $this->allegroApi, $this->marketplaceId);

        $path   = (string)$allegro_cat_path;
        $method = (string)$source;
        $conf   = is_null($confidence) ? null : (float)$confidence;

        $mapper->saveMapping((int)$id_category_ps, (string)$allegro_cat_id, $path, $method, $conf);
        return true;
    }

}
