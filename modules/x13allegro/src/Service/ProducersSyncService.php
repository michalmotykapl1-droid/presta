<?php
namespace PrestaShop\Modules\X13Allegro\Service;

if (!defined('_PS_VERSION_')) { exit; }

use Db;

/**
 * Works inside modules/x13allegro — no dependency on your GPSR module.
 * It scans Presta manufacturers, creates Responsible Producers in Allegro if missing,
 * and writes mapping rows into X13's producer mapping table (autodetected). If not found,
 * uses a safe local bridge table: ps_x13allegro_producer_map_bridge
 */
class ProducersSyncService
{
    public function __construct(private Db $db) {}

    /** Return array of accounts from X13 tables: [ ['label'=>..., 'access_token'=>..., 'is_sandbox'=>0/1, 'account_key'=>...], ... ] */
    public function discoverAccounts(): array
    {
        $prefix = _DB_PREFIX_;
        $tables = array_merge(
            $this->db->executeS('SHOW TABLES LIKE "'.pSQL($prefix).'%allegro%account%"'),
            $this->db->executeS('SHOW TABLES LIKE "'.pSQL($prefix).'%x13%allegro%"')
        );
        $flat = [];
        foreach ($tables as $row) foreach ($row as $t) $flat[$t] = $t;

        $out = [];
        foreach ($flat as $table) {
            try {
                $rows = $this->db->executeS('SELECT * FROM `'.$table.'` LIMIT 50');
                foreach ($rows as $r) {
                    $acc = [
                        'table' => $table, 'id'=>null, 'label'=>null,
                        'access_token'=>null, 'refresh_token'=>null, 'expires_at'=>null, 'is_sandbox'=>0,
                        'account_key'=>null,
                    ];
                    foreach (['id','id_account','id_allegro','id_x13account'] as $c) if (isset($r[$c])) { $acc['id'] = $r[$c]; break; }
                    foreach (['name','label','login','user_login','shop_name'] as $c) if (isset($r[$c]) && $r[$c]) { $acc['label'] = $r[$c]; break; }
                    if (!$acc['label']) $acc['label'] = $table.'#'.$acc['id'];
                    foreach ($r as $k=>$v) {
                        $lk = strtolower($k);
                        if (!$acc['access_token']  && strpos($lk,'access')!==false && strpos($lk,'token')!==false)  $acc['access_token'] = $v;
                        if (!$acc['refresh_token'] && strpos($lk,'refresh')!==false && strpos($lk,'token')!==false) $acc['refresh_token'] = $v;
                        if (!$acc['expires_at']    && (strpos($lk,'expire')!==false || strpos($lk,'valid')!==false) ) $acc['expires_at'] = $v;
                        if (strpos($lk,'sandbox')!==false || strpos($lk,'env')!==false) {
                            $acc['is_sandbox'] = (int)($v==1 || strtolower((string)$v)=='sandbox' || strtolower((string)$v)=='test');
                        }
                    }
                    if ($acc['access_token']) {
                        $acc['account_key'] = md5($acc['table'].'|'.$acc['id']);
                        $out[] = $acc;
                    }
                }
            } catch (\Exception $e) {}
        }
        return $out;
    }

    /** Find X13 mapping table or create a local bridge */
    public function resolveMappingTable(): array
    {
        $prefix = _DB_PREFIX_;
        $candidates = [
            $prefix.'x13allegro_producer_map',
            $prefix.'xallegro_producer_map',
            $prefix.'allegro_producer_map',
            $prefix.'x13allegro_producer_link',
            $prefix.'xallegro_producer_link',
        ];
        foreach ($candidates as $tbl) {
            $ex = (bool)$this->db->getValue('SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name="'.pSQL($tbl).'"');
            if ($ex) {
                // Inspect columns
                $cols = $this->db->executeS('SHOW COLUMNS FROM `'.$tbl.'`');
                $haveMan = false; $haveAcc = false; $haveId = false;
                foreach ($cols as $c) {
                    $name = strtolower($c['Field']);
                    if ($name === 'id_manufacturer') $haveMan = true;
                    if ($name === 'allegro_producer_id' || $name === 'producer_id' || $name === 'responsible_producer_id') $haveId = $name;
                    if ($name === 'account_key' || $name === 'id_account' || $name === 'account_id') $haveAcc = $name;
                }
                if ($haveMan && $haveId) {
                    return ['table'=>$tbl, 'col_man'=>'id_manufacturer', 'col_id'=>$haveId, 'col_acc'=>$haveAcc ?: 'account_key'];
                }
            }
        }
        // Fallback bridge
        $bridge = $prefix.'x13allegro_producer_map_bridge';
        $this->db->execute('CREATE TABLE IF NOT EXISTS `'.$bridge.'` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `id_manufacturer` INT UNSIGNED NOT NULL,
            `account_key` VARCHAR(191) NOT NULL,
            `allegro_producer_id` VARCHAR(64) NOT NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uniq_map` (`id_manufacturer`,`account_key`)
        ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8mb4;');
        return ['table'=>$bridge, 'col_man'=>'id_manufacturer', 'col_id'=>'allegro_producer_id', 'col_acc'=>'account_key'];
    }

    /** Main sync: creates producers if missing, then writes mapping */
    public function syncAllManufacturers(string $accountKey, bool $dryRun=false): array
    {
        // Find selected account
        $acc = null;
        foreach ($this->discoverAccounts() as $a) if ($a['account_key'] === $accountKey) { $acc = $a; break; }
        if (!$acc) { throw new \RuntimeException('Account not found'); }

        $api = new AllegroApiLight($acc['access_token'], (bool)$acc['is_sandbox']);

        // Allegro existing producers
        $existing = $this->fetchAllAllegroProducers($api); // map name_norm => id

        // Manufacturers
        $mans = $this->db->executeS('SELECT id_manufacturer, name FROM '._DB_PREFIX_.'manufacturer ORDER BY name ASC');

        $mapTbl = $this->resolveMappingTable();
        $rows = [];
        foreach ($mans as $m) {
            $name = trim($m['name']);
            if ($name==='') continue;
            $norm = $this->norm($name);
            $id = $existing[$norm] ?? null;

            if (!$id) {
                if ($dryRun) {
                    $rows[] = ['m'=>$name, 'action'=>'CREATE', 'id'=>null];
                } else {
                    $payload = [
                        'name' => $name,
                        'producerData' => [
                            // Minimal structure; no GPSR dependency.
                            'contact' => new \stdClass(),
                            'address' => new \stdClass(),
                        ],
                    ];
                    $res = $api->createResponsibleProducer($payload);
                    if ($res['status']>=200 && $res['status']<300 && !empty($res['body']['id'])) {
                        $id = $res['body']['id'];
                        $existing[$norm] = $id;
                        $rows[] = ['m'=>$name, 'action'=>'CREATED', 'id'=>$id];
                    } else {
                        $err = isset($res['body']['errors']) ? json_encode($res['body']['errors']) : ($res['error'] ?: ('HTTP '.$res['status']));
                        $rows[] = ['m'=>$name, 'action'=>'ERR', 'msg'=>$err];
                        continue;
                    }
                }
            } else {
                $rows[] = ['m'=>$name, 'action'=>'EXISTS', 'id'=>$id];
            }

            // write mapping row
            if (!$dryRun && $id) {
                $this->upsertMapRow($mapTbl, (int)$m['id_manufacturer'], $accountKey, $id);
            }
        }
        return ['table'=>$mapTbl, 'rows'=>$rows];
    }

    protected function upsertMapRow(array $mapTbl, int $idManufacturer, string $accKey, string $allegroId): void
    {
        $tbl = $mapTbl['table']; $colM=$mapTbl['col_man']; $colA=$mapTbl['col_acc']; $colId=$mapTbl['col_id'];
        // Try generic SQL with ON DUPLICATE KEY; if table has no unique index, fallback to delete/insert
        try {
            $this->db->execute('
                INSERT INTO `'.$tbl.'` (`'.$colM.'`,`'.$colA.'`,`'.$colId.'`)
                VALUES ('.(int)$idManufacturer.', "'.pSQL($accKey).'", "'.pSQL($allegroId).'")
                ON DUPLICATE KEY UPDATE `'.$colId.'`=VALUES(`'.$colId.'`)
            ');
        } catch (\Exception $e) {
            $this->db->execute('DELETE FROM `'.$tbl.'` WHERE `'.$colM.'`='.(int)$idManufacturer.' AND `'.$colA.'`="'.pSQL($accKey).'"');
            $this->db->execute('INSERT INTO `'.$tbl.'` (`'.$colM.'`,`'.$colA.'`,`'.$colId.'`) VALUES ('.(int)$idManufacturer.', "'.pSQL($accKey).'", "'.pSQL($allegroId).'")');
        }
    }

    protected function fetchAllAllegroProducers(AllegroApiLight $api): array
    {
        $map = [];
        $offset = 0; $limit = 100;
        for ($i=0;$i<50;$i++) {
            $res = $api->listResponsibleProducers($limit, $offset);
            if ($res['status']<200 || $res['status']>=300) break;
            $body = $res['body'] ?: [];
            $items = $body['responsibleProducers'] ?? [];
            foreach ($items as $it) {
                if (!empty($it['name']) && !empty($it['id'])) {
                    $map[$this->norm($it['name'])] = $it['id'];
                }
            }
            if (count($items) < $limit) break;
            $offset += $limit;
        }
        return $map;
    }

    protected function norm(string $s): string
    { return strtolower(trim(preg_replace('~\s+~',' ', $s))); }
}
