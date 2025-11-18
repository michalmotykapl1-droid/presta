<?php

namespace x13allegro\Service;

use Db;
use DbQuery;
use Tools;
use Context;
use ReflectionClass;
use ReflectionProperty;

class CategoryAutoMapperService
{
    private $idShop;
    private $allegroApi;
    private $activeAccountId = null;
    private $marketplaceId = 'allegro-pl';
    private static $allegroIndex = null;

    private $debug = ['http'=>[], 'token'=>false, 'token_source'=>'', 'api_base'=>''];

    public function __construct($idShop = null, $allegroApi = null, $marketplaceId = 'allegro-pl')
    {
        $this->idShop = (int)($idShop ?: Context::getContext()->shop->id);
        $this->allegroApi = $allegroApi;
        $this->marketplaceId = (string)$marketplaceId ?: 'allegro-pl';
    }

    /* ================= LOG ================= */

    private function logLine($line)
    {
        $logFile = dirname(__FILE__, 4) . '/cache/debug_log.txt';
        @file_put_contents($logFile, '[' . date('Y-m-d H:i:s') . '] ' . $line . "\n", FILE_APPEND);
    }

    /* ================= DB HELPERS ================= */

    private function getActiveAccountRow()
    {
        $q = new DbQuery();
        $q->select('*');
        $q->from('xallegro_account');
        $q->where('active = 1');
        $q->orderBy('id_xallegro_account ASC');
        return Db::getInstance()->getRow($q);
    }

    private function getApiBaseFromRow($row)
    {
        $isSandbox = false;
        if (isset($row['sandbox'])) {
            $isSandbox = (bool)$row['sandbox'];
        } elseif (isset($row['is_sandbox'])) {
            $isSandbox = (bool)$row['is_sandbox'];
        }
        $base = $isSandbox ? 'https://api.allegro.pl.allegrosandbox.pl' : 'https://api.allegro.pl';
        $this->debug['api_base'] = $base;
        return $base;
    }

    private function getAuthBaseFromRow($row)
    {
        $isSandbox = false;
        if (isset($row['sandbox'])) {
            $isSandbox = (bool)$row['sandbox'];
        } elseif (isset($row['is_sandbox'])) {
            $isSandbox = (bool)$row['is_sandbox'];
        }
        return $isSandbox ? 'https://allegro.pl.allegrosandbox.pl' : 'https://allegro.pl';
    }

    private function saveTokenBackToDb($row, $accessToken, $expiresIn = null)
    {
        if (!$row || !is_array($row)) {
            return;
        }
        $table = _DB_PREFIX_.'xallegro_account';
        // try common column names
        $cols = Db::getInstance()->executeS('SHOW COLUMNS FROM `'.$table.'`');
        $colNames = array_map(function($c){ return $c['Field']; }, is_array($cols)?$cols:[]);

        if (in_array('access_token', $colNames)) {
            Db::getInstance()->update('xallegro_account', ['access_token' => pSQL($accessToken)], '`id_xallegro_account`='.(int)$row['id_xallegro_account']);
        }
        if ($expiresIn && in_array('token_valid_to', $colNames)) {
            $dt = date('Y-m-d H:i:s', time() + (int)$expiresIn - 60);
            Db::getInstance()->update('xallegro_account', ['token_valid_to' => pSQL($dt)], '`id_xallegro_account`='.(int)$row['id_xallegro_account']);
        }
    }

    /* ================= API INIT ================= */

    private function lazyInitApi()
    {
        if ($this->allegroApi !== null) return;
        try {
            if (!class_exists('XAllegroAccount') || !class_exists('\\x13allegro\\Api\\XAllegroApi')) {
                $this->logLine('BŁĄD: Brak XAllegroAccount / XAllegroApi');
                return;
            }
            $row = $this->getActiveAccountRow();
            if ($row && isset($row['id_xallegro_account'])) {
                $this->activeAccountId = (int)$row['id_xallegro_account'];
                $acc = new \XAllegroAccount($this->activeAccountId);
                if (isset($acc->id) && $acc->active) {
                    $this->allegroApi = new \x13allegro\Api\XAllegroApi($acc);
                }
            }
        } catch (\Throwable $e) {
            $this->logLine('INIT ERR: ' . $e->getMessage());
        }
    }

    /* ================= TOKEN ================= */

    private function getBearerTokenFromApi()
    {
        $this->lazyInitApi();
        // 1) try from API object
        if ($this->allegroApi) {
            foreach (['getAccessToken','getToken','getOAuthToken','getOauthToken','getBearer','accessToken','token'] as $cand) {
                try {
                    if (method_exists($this->allegroApi, $cand)) {
                        $res = $this->allegroApi->{$cand}();
                        if (is_array($res) && isset($res['access_token'])) { $this->debug['token']=true; $this->debug['token_source']='api_object'; return (string)$res['access_token']; }
                        if (is_string($res) && $res !== '') { $this->debug['token']=true; $this->debug['token_source']='api_object'; return $res; }
                    }
                } catch (\Throwable $e) {}
            }
            try {
                $ref = new ReflectionClass($this->allegroApi);
                foreach (['accessToken','token','access_token','bearer','jwt'] as $prop) {
                    if ($ref->hasProperty($prop)) {
                        $p = $ref->getProperty($prop);
                        $p->setAccessible(true);
                        $val = $p->getValue($this->allegroApi);
                        if (is_array($val) && isset($val['access_token'])) { $this->debug['token']=true; $this->debug['token_source']='api_property'; return (string)$val['access_token']; }
                        if (is_string($val) && $val !== '') { $this->debug['token']=true; $this->debug['token_source']='api_property'; return $val; }
                    }
                }
            } catch (\Throwable $e) {}
        }

        // 2) try from DB row
        $row = $this->getActiveAccountRow();
        if ($row) {
            foreach (['access_token','token','bearer'] as $k) {
                if (!empty($row[$k]) && is_string($row[$k]) && strlen($row[$k]) > 20) {
                    $this->debug['token']=true; $this->debug['token_source']='db_access_token';
                    return (string)$row[$k];
                }
            }
            // 3) try refresh via OAuth if client/secret/refresh present
            $clientId  = '';
            $clientSec = '';
            $refresh   = '';
            foreach (['client_id','app_id','clientid'] as $k) { if (!empty($row[$k])) { $clientId = $row[$k]; break; } }
            foreach (['client_secret','app_secret','clientsecret'] as $k) { if (!empty($row[$k])) { $clientSec = $row[$k]; break; } }
            foreach (['refresh_token','refreshtoken','token_refresh'] as $k) { if (!empty($row[$k])) { $refresh = $row[$k]; break; } }

            if ($clientId && $clientSec && $refresh) {
                $authBase = $this->getAuthBaseFromRow($row);
                $url = $authBase . '/auth/oauth/token';
                $post = http_build_query(['grant_type' => 'refresh_token', 'refresh_token' => $refresh]);
                $headers = [
                    'Authorization: Basic ' . base64_encode($clientId . ':' . $clientSec),
                    'Content-Type: application/x-www-form-urlencoded',
                    'Accept: application/json'
                ];
                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
                curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                $body = curl_exec($ch);
                $errno = curl_errno($ch);
                $err  = curl_error($ch);
                $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                $this->debug['http'][] = ['POST /auth/oauth/token', $code, $errno ? ('cURL#'.$errno.':'.$err) : ''];
                if ($errno) { $this->logLine('OAuth error '.$errno.' '.$err); }
                $json = json_decode((string)$body, true);
                if (isset($json['access_token']) && is_string($json['access_token'])) {
                    $this->saveTokenBackToDb($row, $json['access_token'], isset($json['expires_in'])?$json['expires_in']:null);
                    $this->debug['token']=true; $this->debug['token_source']='oauth_refresh';
                    return (string)$json['access_token'];
                }
            }
        }

        $this->debug['token']=false; $this->debug['token_source']='none';
        return null;
    }

    /* ================= TREE CACHE ================= */

    private function getAllegroTreeFromLocalCache()
    {
        $moduleDir = dirname(__FILE__, 4);
        $cacheFile = $moduleDir . '/cache/json/kategorie_allegro.json';
        if (!is_readable($cacheFile)) { return []; }
        $data = json_decode(@file_get_contents($cacheFile), true);
        return is_array($data) ? $data : [];
    }

    private function buildIndexFromTree(array $tree)
    {
        $idx = [];
        $walk = function($node, $parentId = null) use (&$idx, &$walk) {
            if (!is_array($node)) return;
            $id = isset($node['id']) ? (string)$node['id'] : '';
            $name = isset($node['name']) ? (string)$node['name'] : (isset($node['label']) ? (string)$node['label'] : '');
            if ($id !== '') {
                if (!isset($idx[$id])) {
                    $idx[$id] = ['id'=>$id, 'name'=>$name, 'parent'=>$parentId, 'children'=>[]];
                }
                if (!empty($node['children'])) {
                    foreach ($node['children'] as $ch) {
                        $idx[$id]['children'][] = isset($ch['id']) ? (string)$ch['id'] : null;
                        $walk($ch, $id);
                    }
                }
            }
        };
        foreach ($tree as $root) { $walk($root, null); }
        return $idx;
    }

    private function getAllegroIndex()
    {
        if (self::$allegroIndex !== null) return self::$allegroIndex;
        self::$allegroIndex = $this->buildIndexFromTree($this->getAllegroTreeFromLocalCache());
        return self::$allegroIndex;
    }

    private function buildPathById($catId)
    {
        $idx = $this->getAllegroIndex();
        $id = (string)$catId;
        if (!isset($idx[$id])) return '';
        $parts = [];
        while ($id && isset($idx[$id])) {
            $parts[] = $idx[$id]['name'] ?: $id;
            $id = $idx[$id]['parent'];
        }
        $parts = array_reverse($parts);
        if (!empty($parts) && Tools::strtolower($parts[0]) === 'allegro') array_shift($parts);
        return implode(' > ', $parts);
    }

    private function isLeaf($catId)
    {
        $idx = $this->getAllegroIndex();
        $id = (string)$catId;
        return isset($idx[$id]) ? empty($idx[$id]['children']) : false;
    }

    /* ================= HTTP GET (API) ================= */

    private function curlGet($path, array $query = [], &$httpCode = 0)
    {
        $row = $this->getActiveAccountRow();
        $base = $this->getApiBaseFromRow($row);
        $url = $base . $path;
        if (!empty($query)) $url .= '?' . http_build_query($query);

        $headers = ['Accept: application/vnd.allegro.public.v1+json','User-Agent: x13allegro-automap/1.0'];
        $token = $this->getBearerTokenFromApi();
        if ($token) $headers[] = 'Authorization: Bearer '.$token;

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

        $this->debug['http'][] = ['GET '.$path, $httpCode, $errno ? ('cURL#'.$errno.':'.$err) : ''];
        if ($errno) { $this->logLine('cURL ['.$path.'] '.$errno.' '.$err); return null; }
        $json = json_decode((string)$body, true);
        if (!is_array($json)) { $this->logLine('Bad JSON ['.$path.'] '.substr((string)$body,0,200)); return null; }
        return $json;
    }

    /* ================= PRESTA DATA ================= */

    private function getProductsInCategory($idCategory, $limit = 200)
    {
        $idLang = (int)Context::getContext()->language->id;
        $q = new DbQuery();
        $q->select('p.id_product, p.ean13, pl.name');
        $q->from('category_product', 'cp');
        $q->innerJoin('product', 'p', 'p.id_product = cp.id_product');
        $q->leftJoin('product_lang', 'pl', 'pl.id_product = p.id_product AND pl.id_lang='.$idLang);
        $q->where('cp.id_category='.(int)$idCategory);
        $q->where('p.active = 1');
        $q->groupBy('p.id_product');
        $q->limit((int)$limit);
        $rows = Db::getInstance()->executeS($q);
        return is_array($rows) ? $rows : [];
    }

    private function getPsCategoryName($idCategory)
    {
        $q = new DbQuery();
        $q->select('cl.name');
        $q->from('category', 'c');
        $q->leftJoin('category_lang', 'cl', 'cl.id_category=c.id_category AND cl.id_lang='.(int)Context::getContext()->language->id);
        $q->where('c.id_category='.(int)$idCategory);
        $row = Db::getInstance()->getRow($q);
        return $row ? (string)$row['name'] : '';
    }

    /* ================= RESOLVERS ================= */

    private function resolveCategoryByEan($ean)
    {
        $ean = preg_replace('/\D+/', '', (string)$ean);
        if ($ean === '' || strlen($ean) < 8) return null;

        $http=0;
        $j1 = $this->curlGet('/sale/products', ['phrase'=>$ean, 'mode'=>'GTIN'], $http);
        $items = [];
        if (is_array($j1)) {
            if (isset($j1['products']) && is_array($j1['products'])) $items = $j1['products'];
            elseif (isset($j1['items']) && is_array($j1['items'])) $items = $j1['items'];
            elseif (isset($j1['elements']) && is_array($j1['elements'])) $items = $j1['elements'];
        }
        if (empty($items)) {
            $j2 = $this->curlGet('/sale/products', ['ean'=>$ean], $http);
            if (is_array($j2)) {
                if (isset($j2['products']) && is_array($j2['products'])) $items = $j2['products'];
                elseif (isset($j2['items']) && is_array($j2['items'])) $items = $j2['items'];
            }
        }
        if (empty($items)) return null;

        foreach ($items as $p) {
            if (isset($p['category']['id'])) return (string)$p['category']['id'];
            if (isset($p['primaryCategory']['id'])) return (string)$p['primaryCategory']['id'];
            if (isset($p['product']['category']['id'])) return (string)$p['product']['category']['id'];
            $pid = isset($p['id']) ? (string)$p['id'] : (isset($p['product']['id']) ? (string)$p['product']['id'] : '');
            if ($pid) {
                $pj = $this->curlGet('/sale/products/'.rawurlencode($pid), [], $http);
                if (isset($pj['category']['id'])) return (string)$pj['category']['id'];
                if (isset($pj['primaryCategory']['id'])) return (string)$pj['primaryCategory']['id'];
            }
        }
        return null;
    }

    private function resolveCategoryByName($name)
    {
        $name = trim((string)$name);
        if ($name === '') return null;
        $http=0;
        $j = $this->curlGet('/sale/products', ['phrase'=>$name], $http);
        $items = [];
        if (is_array($j)) {
            if (isset($j['products']) && is_array($j['products'])) $items = $j['products'];
            elseif (isset($j['items']) && is_array($j['items'])) $items = $j['items'];
            elseif (isset($j['elements']) && is_array($j['elements'])) $items = $j['elements'];
        }
        if (empty($items)) return null;
        foreach ($items as $p) {
            if (isset($p['category']['id'])) return (string)$p['category']['id'];
            if (isset($p['primaryCategory']['id'])) return (string)$p['primaryCategory']['id'];
            if (isset($p['product']['category']['id'])) return (string)$p['product']['category']['id'];
        }
        return null;
    }

    private function normalize($s)
    {
        $s = Tools::strtolower(trim((string)$s));
        $s = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s);
        $map = ['ą'=>'a','ć'=>'c','ę'=>'e','ł'=>'l','ń'=>'n','ó'=>'o','ś'=>'s','ż'=>'z','ź'=>'z'];
        $s = strtr($s, $map);
        $s = preg_replace('/[^a-z0-9 ]/', ' ', $s);
        return trim(preg_replace('/\s+/', ' ', $s));
    }

    private function similarity($a, $b)
    {
        $a = $this->normalize($a);
        $b = $this->normalize($b);
        if ($a === $b) return 100.0;
        if ($a === '' || $b === '') return 0.0;
        similar_text($a, $b, $pct);
        $lev = levenshtein($a, $b);
        $normLev = 100.0 * (1.0 - $lev / max(1, max(strlen($a), strlen($b))));
        return (0.6 * $pct) + (0.4 * $normLev);
    }

    private function resolveCategoryByPsCategoryName($psName)
    {
        $psName = (string)$psName;
        if ($psName === '') return null;
        $idx = $this->getAllegroIndex();
        $bestId = null; $best = -1.0;
        foreach ($idx as $id => $node) {
            $score = $this->similarity($psName, $node['name']);
            if ($score > $best) { $best = $score; $bestId = $id; }
        }
        return $bestId ?: null;
    }

    /* ================= MAIN ================= */

    public function autoMapPsCategory($idCategory, $sampleEans = 80, $sampleNames = 30, $save = false)
    {
        $rows = $this->getProductsInCategory((int)$idCategory, max($sampleEans, $sampleNames));
        $out = [
            'success' => false,
            'allegroCategoryId' => null,
            'allegroCategoryPath' => '',
            'method' => '',
            'stats' => ['ean'=>[], 'name'=>[]],
            'debug' => $this->debug,
        ];
        if (empty($rows)) { $out['debug']['note']='Brak produktów w tej kategorii.'; return $out; }

        $score = [];
        $seenEan = [];
        foreach ($rows as $r) {
            $ean = preg_replace('/\D+/', '', (string)$r['ean13']);
            if ($ean === '' || strlen($ean) < 8) continue;
            if (isset($seenEan[$ean])) continue;
            $seenEan[$ean] = true;
            $cat = $this->resolveCategoryByEan($ean);
            if ($cat) {
                if (!isset($score[$cat])) $score[$cat] = 0;
                $score[$cat] += 3;
                $out['stats']['ean'][] = ['ean'=>$ean, 'cat'=>$cat];
            }
            if (count($seenEan) >= $sampleEans) break;
        }
        $bestId = null;
        if (!empty($score)) {
            arsort($score);
            $bestId = key($score);
            $out['method'] = 'ean';
        } else {
            $seenNames = 0;
            foreach ($rows as $r) {
                $name = (string)$r['name'];
                if ($name === '') continue;
                $cat = $this->resolveCategoryByName($name);
                if ($cat) {
                    if (!isset($score[$cat])) $score[$cat] = 0;
                    $score[$cat] += 1;
                    $out['stats']['name'][] = ['name'=>$name, 'cat'=>$cat];
                }
                $seenNames++;
                if ($seenNames >= $sampleNames) break;
            }
            if (!empty($score)) {
                arsort($score);
                $bestId = key($score);
                $out['method'] = 'name';
            } else {
                $psName = $this->getPsCategoryName((int)$idCategory);
                $bestId = $this->resolveCategoryByPsCategoryName($psName);
                $out['method'] = 'ps_category_name';
            }
        }

        if (!$bestId) { return $out; }

        if (!$this->isLeaf($bestId)) {
            $idx = $this->getAllegroIndex();
            if (isset($idx[$bestId]) && !empty($idx[$bestId]['children'])) {
                $bestLeaf = null; $bestSim = -1.0;
                $psName = $this->getPsCategoryName((int)$idCategory);
                foreach ($idx[$bestId]['children'] as $childId) {
                    if (!$childId) continue;
                    $nm = isset($idx[$childId]['name']) ? $idx[$childId]['name'] : '';
                    $sim = $this->similarity($psName, $nm);
                    if ($sim > $bestSim) { $bestSim = $sim; $bestLeaf = $childId; }
                }
                if ($bestLeaf) $bestId = $bestLeaf;
            }
        }

        $out['success'] = true;
        $out['allegroCategoryId'] = $bestId;
        $out['allegroCategoryPath'] = $this->buildPathById($bestId);

        if ($save) { $this->saveMapping((int)$idCategory, $bestId, $out['allegroCategoryPath'], $out['method']); }

        return $out;
    }

    /* ================= SAVE ================= */

    private function tableExists($table)
    {
        try { return (bool)Db::getInstance()->getValue('SHOW TABLES LIKE \'' . pSQL($table) . '\''); }
        catch (\Throwable $e) { return false; }
    }

    private function columnExists($table, $column)
    {
        try {
            $row = Db::getInstance()->getRow('SHOW COLUMNS FROM `'.pSQL($table).'` LIKE \'' . pSQL($column) . '\'');
            return is_array($row) && !empty($row);
        } catch (\Throwable $e) { return false; }
    }

    private function ensureMapTable()
    {
        $table = _DB_PREFIX_.'xallegro_category_map';
        if (!$this->tableExists($table)) {
            $sql = 'CREATE TABLE `' . pSQL($table) . '` (
                `id_category_ps` INT UNSIGNED NOT NULL,
                `allegro_category_id` VARCHAR(64) NOT NULL,
                `allegro_category_path` VARCHAR(1024) NOT NULL,
                `method` VARCHAR(32) NOT NULL,
                `confidence` DECIMAL(6,2) NULL,
                `updated_at` DATETIME NOT NULL,
                PRIMARY KEY (`id_category_ps`)
            ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;';
            Db::getInstance()->execute($sql);
        }
    }

    private function saveMappingToMapTable($idCategoryPs, $allegroCatId, $path, $method, $confidence = null)
    {
        $this->ensureMapTable();
        $table = _DB_PREFIX_.'xallegro_category_map';
        $sql = 'REPLACE INTO `'.pSQL($table).'`
                (`id_category_ps`,`allegro_category_id`,`allegro_category_path`,`method`,`confidence`,`updated_at`)
                VALUES ('.(int)$idCategoryPs.', \''.pSQL($allegroCatId).'\', \''.pSQL($path).'\', \''.pSQL($method).'\', '.($confidence===null?'NULL':(float)$confidence).', NOW())';
        Db::getInstance()->execute($sql);
    }

    private function saveMappingToModuleTable($idCategoryPs, $allegroCatId, $path)
{
    // --- POPRAWKA #1 (SQL Syntax Error) ---
    // Zmieniono sposób budowania zapytania SELECT, aby uniknąć potencjalnego błędu w klasie DbQuery.
    $table = _DB_PREFIX_ . 'xallegro_category';
    $where_clause = '';

    if (ctype_digit((string)$allegroCatId)) {
        $where_clause = '`id_allegro_category` = ' . (int)$allegroCatId;
    } else {
        $where_clause = "`id_allegro_category` = '" . pSQL((string)$allegroCatId) . "'";
    }

    $sql = 'SELECT `id_xallegro_category`, `id_categories` FROM `' . $table . '` WHERE ' . $where_clause;
    $row = Db::getInstance()->getRow($sql);
    
    // --- POPRAWKA #2 (Zapis do właściwych kolumn) ---
    // Wyodrębniamy ostatni człon ścieżki jako nazwę i zapisujemy pełną ścieżkę do kolumny `path`.
    $pathParts = explode(' > ', $path);
    $leafName = end($pathParts);

    if ($row) {
        $idsTxt = isset($row['id_categories']) ? (string)$row['id_categories'] : '';
        $ids = preg_split('/[,\s]+/', trim($idsTxt), -1, PREG_SPLIT_NO_EMPTY);
        if (!in_array((string)$idCategoryPs, $ids, true)) {
            $ids[] = (string)$idCategoryPs;
        }
        $newIdsTxt = implode(',', $ids);
        Db::getInstance()->update('xallegro_category', [
            'id_categories' => pSQL($newIdsTxt),
            'name' => pSQL($leafName), // Zapisz tylko nazwę końcową
            'path' => pSQL($path), // Zapisz pełną ścieżkę
            'active' => 1,
        ], 'id_xallegro_category='.(int)$row['id_xallegro_category']);
    } else {
        Db::getInstance()->insert('xallegro_category', [
            'id_allegro_category' => pSQL((string)$allegroCatId),
            'id_categories' => pSQL((string)$idCategoryPs),
            'name' => pSQL($leafName), // Zapisz tylko nazwę końcową
            'path' => pSQL($path), // Zapisz pełną ścieżkę
            'active' => 1,
        ]);
    }
}


    public function saveMapping($idCategoryPs, $allegroCatId, $path, $method, $confidence = null)
    {
        $mapTable = _DB_PREFIX_.'xallegro_category_map';
        if ($this->tableExists($mapTable) && $this->columnExists($mapTable, 'id_category_ps')) {
            $this->saveMappingToMapTable($idCategoryPs, $allegroCatId, $path, $method, $confidence);
        } else {
            $this->saveMappingToModuleTable($idCategoryPs, $allegroCatId, $path);
        }
    }
}