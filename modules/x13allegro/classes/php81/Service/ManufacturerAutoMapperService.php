<?php

namespace x13allegro\Service;

use Db;
use DbQuery;
use Tools;
use Context;
use ReflectionClass;
use ReflectionProperty;

/**
 * ManufacturerAutoMapperService
 * Automatyczne dopasowanie PRODUCENTÓW z PS do marek Allegro (słownik).
 */
class ManufacturerAutoMapperService
{
    private $idShop;
    private $allegroApi;
    private $activeAccountId = null;
    private $marketplaceId = 'allegro-pl';
    private $debug = ['http'=>[], 'token'=>false, 'token_source'=>'', 'api_base'=>''];

    public function __construct($idShop = null, $allegroApi = null, $marketplaceId = 'allegro-pl')
    {
        $this->idShop = (int)($idShop ?: Context::getContext()->shop->id);
        $this->allegroApi = $allegroApi;
        $this->marketplaceId = (string)$marketplaceId ?: 'allegro-pl';
    }

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
        if (isset($row['sandbox']))       { $isSandbox = (bool)$row['sandbox']; }
        elseif (isset($row['is_sandbox'])){ $isSandbox = (bool)$row['is_sandbox']; }
        $base = $isSandbox ? 'https://api.allegro.pl.allegrosandbox.pl' : 'https://api.allegro.pl';
        return $base;
    }

    private function getAuthBaseFromRow($row)
    {
        $isSandbox = false;
        if (isset($row['sandbox']))       { $isSandbox = (bool)$row['sandbox']; }
        elseif (isset($row['is_sandbox'])){ $isSandbox = (bool)$row['is_sandbox']; }
        return $isSandbox ? 'https://allegro.pl.allegrosandbox.pl' : 'https://allegro.pl';
    }

    private function saveTokenBackToDb($row, $accessToken, $expiresIn = null)
    {
        if (!$row || !is_array($row)) return;
        $table = _DB_PREFIX_.'xallegro_account';
        $cols = Db::getInstance()->executeS('SHOW COLUMNS FROM `'.$table.'`');
        $colNames = array_map(function($c){ return $c['Field']; }, is_array($cols)?$cols:[]);
        if (in_array('access_token',$colNames)) {
            Db::getInstance()->update('xallegro_account', ['access_token'=>pSQL($accessToken)], 'id_xallegro_account='.(int)$row['id_xallegro_account']);
        }
        if ($expiresIn && in_array('token_valid_to',$colNames)) {
            $dt = date('Y-m-d H:i:s', time()+(int)$expiresIn-60);
            Db::getInstance()->update('xallegro_account', ['token_valid_to'=>pSQL($dt)], 'id_xallegro_account='.(int)$row['id_xallegro_account']);
        }
    }

    private function lazyInitApi()
    {
        if ($this->allegroApi !== null) return;
        try {
            if (!class_exists('XAllegroAccount') || !class_exists('\\x13allegro\\Api\\XAllegroApi')) {
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
        } catch (\Throwable $e) {}
    }

    private function getBearerTokenFromApi()
    {
        $this->lazyInitApi();
        if ($this->allegroApi) {
            foreach (['getAccessToken','getToken','getOAuthToken','getOauthToken','getBearer','accessToken','token'] as $cand) {
                try {
                    if (method_exists($this->allegroApi, $cand)) {
                        $res = $this->allegroApi->{$cand}();
                        if (is_array($res) && isset($res['access_token'])) return (string)$res['access_token'];
                        if (is_string($res) && $res !== '') return $res;
                    }
                } catch (\Throwable $e) {}
            }
            try {
                $ref = new \ReflectionClass($this->allegroApi);
                foreach (['accessToken','token','access_token','bearer','jwt'] as $prop) {
                    if ($ref->hasProperty($prop)) {
                        $p=$ref->getProperty($prop); $p->setAccessible(true); $val=$p->getValue($this->allegroApi);
                        if (is_array($val) && isset($val['access_token'])) return (string)$val['access_token'];
                        if (is_string($val) && $val !== '') return $val;
                    }
                }
            } catch (\Throwable $e) {}
        }

        $row = $this->getActiveAccountRow();
        if ($row) {
            foreach (['access_token','token','bearer'] as $k) {
                if (!empty($row[$k]) && is_string($row[$k]) && strlen($row[$k]) > 20) {
                    return (string)$row[$k];
                }
            }
            $clientId=''; $clientSec=''; $refresh='';
            foreach(['client_id','app_id','clientid'] as $k){ if(!empty($row[$k])){$clientId=$row[$k]; break;}}
            foreach(['client_secret','app_secret','clientsecret'] as $k){ if(!empty($row[$k])){$clientSec=$row[$k]; break;}}
            foreach(['refresh_token','refreshtoken','token_refresh'] as $k){ if(!empty($row[$k])){$refresh=$row[$k]; break;}}
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
                $body = curl_exec($ch);
                $json = json_decode((string)$body, true);
                if (isset($json['access_token']) && is_string($json['access_token'])) {
                    $this->saveTokenBackToDb($row, $json['access_token'], isset($json['expires_in'])?$json['expires_in']:null);
                    return (string)$json['access_token'];
                }
            }
        }
        return null;
    }

    private function apiGet($path, array $query = [], &$http = 0)
    {
        $base = $this->getApiBaseFromRow($this->getActiveAccountRow());
        $url = $base . $path . (empty($query)?'':'?'.http_build_query($query));
        $headers = ['Accept: application/vnd.allegro.public.v1+json','User-Agent: x13allegro-brand-mapper/1.0'];
        $token = $this->getBearerTokenFromApi();
        if ($token) { $headers[] = 'Authorization: Bearer '.$token; }
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $body = curl_exec($ch);
        $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $json = json_decode((string)$body, true);
        return is_array($json)?$json:null;
    }

    private function normalize($s)
    {
        $s = Tools::strtolower(trim((string)$s));
        $s = @iconv('UTF-8','ASCII//TRANSLIT//IGNORE',$s);
        $map=['ą'=>'a','ć'=>'c','ę'=>'e','ł'=>'l','ń'=>'n','ó'=>'o','ś'=>'s','ż'=>'z','ź'=>'z'];
        $s = strtr($s,$map);
        return preg_replace('/\s+/', ' ', preg_replace('/[^a-z0-9 ]/',' ', $s));
    }

    private function getBrandCandidates($name)
    {
        $out = [];
        $http = 0;
        $j = $this->apiGet('/sale/brands', ['name'=>$name, 'limit'=>50], $http);
        if (is_array($j)) {
            $arr = $j['brands'] ?? $j['items'] ?? [];
            foreach ($arr as $b) {
                $out[] = ['id'=> (string)($b['id'] ?? ''), 'name'=> (string)($b['name'] ?? '')];
            }
        }
        if (empty($out)) {
            $p = $this->apiGet('/sale/products', ['phrase'=>$name], $http);
            $items = [];
            if (is_array($p)) {
                if (isset($p['products']) && is_array($p['products'])) $items = $p['products'];
                elseif (isset($p['items']) && is_array($p['items'])) $items = $p['items'];
            }
            foreach ($items as $it) {
                $brand = $it['brand'] ?? ($it['product']['brand'] ?? null);
                if (is_array($brand) && isset($brand['name'])) {
                    $out[] = ['id'=> (string)($brand['id'] ?? ''), 'name'=> (string)$brand['name']];
                }
            }
        }
        $uniq=[]; $res=[];
        foreach ($out as $x) {
            $k = Tools::strtolower($x['name']);
            if (!isset($uniq[$k])) { $uniq[$k]=true; $res[]=$x; }
        }
        return $res;
    }

    private function chooseBestBrand($psName, array $cands, $tags = [])
    {
        $best=null; $bestScore=-1.0;
        $nPS = $this->normalize($psName);
        $tagNorm = array_map([$this,'normalize'], (array)$tags);
        foreach ($cands as $c) {
            $nB = $this->normalize($c['name']);
            $score = 0.0;
            if ($nB === $nPS) $score += 1.0;
            if (in_array($nB, $tagNorm, true)) $score += 0.5;
            similar_text($nPS, $nB, $pct);
            $lev = levenshtein($nPS, $nB);
            $normLev = 1.0 - $lev / max(1, max(strlen($nPS), strlen($nB)));
            $score += 0.6*($pct/100.0) + 0.4*$normLev;
            if ($score > $bestScore) { $bestScore=$score; $best=$c; }
        }
        return [$best, $bestScore];
    }

    private function ensureMapTable()
    {
        $table = _DB_PREFIX_.'xallegro_manufacturer_map';
        Db::getInstance()->execute('CREATE TABLE IF NOT EXISTS `'.$table.'` (
            `id_manufacturer_ps` INT UNSIGNED NOT NULL,
            `allegro_brand_id` VARCHAR(64) NOT NULL,
            `allegro_brand_name` VARCHAR(255) NOT NULL,
            `tags` TEXT NULL,
            `updated_at` DATETIME NOT NULL,
            PRIMARY KEY (`id_manufacturer_ps`)
        ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;');
    }

    private function saveMap($idManufacturer, $brandId, $brandName, array $tags = [])
    {
        $this->ensureMapTable();
        $table = _DB_PREFIX_.'xallegro_manufacturer_map';
        Db::getInstance()->execute('REPLACE INTO `'.$table.'`
            (`id_manufacturer_ps`,`allegro_brand_id`,`allegro_brand_name`,`tags`,`updated_at`)
            VALUES ('.(int)$idManufacturer.', "'.pSQL($brandId).'", "'.pSQL($brandName).'", "'.pSQL(implode(',', $tags)).'", NOW())');
    }

    public function autoMapAll($threshold = 0.92, $save = false, $limit = 500)
    {
        $idLang = (int)Context::getContext()->language->id;
        $q = new DbQuery();
        $q->select('m.id_manufacturer, COALESCE(ml.name,m.name) AS name');
        $q->from('manufacturer','m');
        $q->leftJoin('manufacturer_lang','ml','ml.id_manufacturer=m.id_manufacturer AND ml.id_lang='.$idLang);
        $q->orderBy('ml.name ASC');
        $q->limit((int)$limit);
        $rows = Db::getInstance()->executeS($q);

        $done = []; $proposals = [];
        foreach ((array)$rows as $r) {
            $id = (int)$r['id_manufacturer'];
            $name = (string)$r['name'];
            if ($name === '') continue;

            $tags = [];
            if (Db::getInstance()->getValue('SHOW TABLES LIKE "'._DB_PREFIX_.'xallegro_manufacturer"')) {
                $trow = Db::getInstance()->getRow('SELECT `tags` FROM `'._DB_PREFIX_.'xallegro_manufacturer` WHERE `id_manufacturer`='.(int)$id);
                if (is_array($trow) && !empty($trow['tags'])) {
                    $tags = preg_split('/[,\n;]+/', (string)$trow['tags'], -1, PREG_SPLIT_NO_EMPTY);
                }
            }

            $cands = $this->getBrandCandidates($name);
            list($best, $score) = $this->chooseBestBrand($name, $cands, $tags);
            if ($best) {
                $proposals[] = ['id_manufacturer'=>$id, 'ps_name'=>$name, 'brand'=>$best, 'score'=>$score];
                if ($save && $score >= (float)$threshold) {
                    $this->saveMap($id, $best['id'], $best['name'], $tags);
                    $done[] = $id;
                }
            }
        }
        return ['success'=>true, 'auto_saved'=>count($done), 'threshold'=>$threshold, 'proposals'=>$proposals];
    }
}
