<?php
declare(strict_types=1);

namespace GeminiContent\Repository;

use Db;
use DbQuery;
use Tools;
use Context;

class GeminiProductRepository
{
    private Db $db;
    private int $idLang;
    private int $idShop;

    public function __construct(Db $db)
    {
        $this->db = $db;
        $context = Context::getContext();
        $this->idLang = (int) $context->language->id;
        $this->idShop = (int) $context->shop->id;
    }

    private function applyFilters(DbQuery $sql, array $filters): string
    {
        $where = "p.reference IS NOT NULL
            AND p.reference != ''
            AND p.reference NOT LIKE 'A_MAG_%'";

        if (!empty($filters['status'])) {
            switch ($filters['status']) {
                case 'missing_both':
                    $where .= " AND (gcl.description_generated = 0 OR gcl.id_product IS NULL)
                        AND (gcl.seo_generated = 0 OR gcl.id_product IS NULL)";
                    break;
                case 'missing_desc':
                    $where .= " AND (gcl.description_generated = 0 OR gcl.id_product IS NULL)
                        AND gcl.seo_generated = 1";
                    break;
                case 'missing_seo':
                    $where .= " AND gcl.description_generated = 1
                        AND (gcl.seo_generated = 0 OR gcl.id_product IS NULL)";
                    break;
                case 'all_unprocessed':
                    $where .= " AND (gcl.description_generated = 0 OR gcl.seo_generated = 0 OR gcl.id_product IS NULL)";
                    break;
            }
        }

        if (!empty($filters['sku_prefixes'])) {
            $raw = (string)$filters['sku_prefixes'];
            $parts = array_filter(array_map(function ($s) {
                return rtrim(trim($s), '_');
            }, explode(',', $raw)));

            if (!empty($parts)) {
                $likes = [];
                foreach ($parts as $p) {
                    $p = pSQL($p, true);
                    $likes[] = "p.reference LIKE '".$p."\\_%' ESCAPE '\\\\'";
                }
                $where .= ' AND ('.implode(' OR ', $likes).')';
            }
        }

        if (!empty($filters['id_product'])) {
            $where .= ' AND p.id_product = ' . (int) $filters['id_product'];
        }
        if (!empty($filters['name'])) {
            $where .= " AND pl.name LIKE '%" . pSQL($filters['name']) . "%'";
        }
        if (!empty($filters['reference'])) {
            $where .= " AND p.reference LIKE '%" . pSQL($filters['reference']) . "%'";
        }
        if (!empty($filters['ean13'])) {
            $where .= " AND p.ean13 LIKE '%" . pSQL($filters['ean13']) . "%'";
        }
        if (!empty($filters['category_name'])) {
            $where .= " AND cl.name LIKE '%" . pSQL($filters['category_name']) . "%'";
        }

        return $where;
    }

    public function countToProcess(array $filters = []): int
    {
        $sql = new DbQuery();
        $sql->select('COUNT(DISTINCT p.id_product)');
        $sql->from('product', 'p');
        $sql->innerJoin('product_shop', 'ps', 'p.id_product = ps.id_product AND ps.id_shop = ' . $this->idShop);
        $sql->leftJoin('manufacturer', 'm', 'm.id_manufacturer = p.id_manufacturer');
        $sql->leftJoin('geminicontent_log', 'gcl', 'p.id_product = gcl.id_product');
        $sql->leftJoin('product_lang', 'pl', 'p.id_product = pl.id_product AND pl.id_lang = ' . $this->idLang . ' AND pl.id_shop = ' . $this->idShop);
        $sql->leftJoin('category_lang', 'cl', 'ps.id_category_default = cl.id_category AND cl.id_lang = ' . $this->idLang . ' AND cl.id_shop = ' . $this->idShop);

        $where = $this->applyFilters($sql, $filters);
        $sql->where($where);

        return (int) $this->db->getValue($sql);
    }

    public function fetchToProcess(array $filters, int $limit, int $offset): array
    {
        $sql = new DbQuery();
        $sql->select(
            'p.id_product, p.reference, p.ean13, pl.name, cl.name AS category_name,' .
            ' IFNULL(gcl.description_generated, 0) as description_generated, IFNULL(gcl.seo_generated, 0) as seo_generated'
        );
        $sql->from('product', 'p');
        $sql->innerJoin('product_shop', 'ps', 'p.id_product = ps.id_product AND ps.id_shop = ' . $this->idShop);
        $sql->leftJoin('manufacturer', 'm', 'm.id_manufacturer = p.id_manufacturer');
        $sql->leftJoin('product_lang', 'pl', 'p.id_product = pl.id_product AND pl.id_lang = ' . $this->idLang . ' AND pl.id_shop = ' . $this->idShop);
        $sql->leftJoin('category_lang', 'cl', 'ps.id_category_default = cl.id_category AND cl.id_lang = ' . $this->idLang . ' AND cl.id_shop = ' . $this->idShop);
        $sql->leftJoin('geminicontent_log', 'gcl', 'p.id_product = gcl.id_product');

        $where = $this->applyFilters($sql, $filters);
        $sql->where($where);

        $sql->limit($limit, $offset);
        $orderBy  = Tools::getValue('productOrderby', 'id_product');
        $orderWay = Tools::getValue('productOrderway', 'DESC');
        $sql->orderBy(pSQL($orderBy) . ' ' . pSQL($orderWay));

        $result = $this->db->executeS($sql);
        return is_array($result) ? $result : [];
    }
    
    public function fetchAllForExport(?int $limit = null, array $filters = []): array
    {
        $sql = new DbQuery();
        $sql->select(
            'p.id_product, p.reference, p.ean13, pl.name, pl.description, ' .
            'pl.meta_title, pl.meta_description, pl.link_rewrite, ' .
            '(SELECT GROUP_CONCAT(t.name SEPARATOR ", ") FROM `' . _DB_PREFIX_ . 'product_tag` pt ' .
            'INNER JOIN `' . _DB_PREFIX_ . 'tag` t ON (pt.id_tag = t.id_tag AND t.id_lang = ' . (int)$this->idLang . ') ' .
            'WHERE pt.id_product = p.id_product) AS tags, ' .
            'IFNULL(GROUP_CONCAT(DISTINCT clpath.name ORDER BY ctree.nleft SEPARATOR " > "), "") AS categories, ' .
            'pl.description_short AS description_short, ' .
            'COALESCE((SELECT name FROM `' . _DB_PREFIX_ . 'manufacturer` WHERE id_manufacturer = p.id_manufacturer), ' .
            '(SELECT fvl.value FROM `' . _DB_PREFIX_ . 'feature_product` fp ' .
            ' JOIN `' . _DB_PREFIX_ . 'feature_lang` fl ON fl.id_feature = fp.id_feature AND fl.id_lang = ' . (int)$this->idLang . ' ' .
            ' JOIN `' . _DB_PREFIX_ . 'feature_value_lang` fvl ON fvl.id_feature_value = fp.id_feature_value AND fvl.id_lang = ' . (int)$this->idLang . ' ' .
            ' WHERE fp.id_product = p.id_product AND fl.name IN (\'Producent\', \'Marka\', \'Brand\', \'Manufacturer\') LIMIT 1)) AS manufacturer_name, ' .
            '(SELECT GROUP_CONCAT(CONCAT(fl.name, ": ", fvl.value) SEPARATOR " | ") FROM `' . _DB_PREFIX_ . 'feature_product` fp ' .
            'JOIN `' . _DB_PREFIX_ . 'feature_lang` fl ON fl.id_feature = fp.id_feature AND fl.id_lang = ' . (int)$this->idLang . ' ' .
            'JOIN `' . _DB_PREFIX_ . 'feature_value_lang` fvl ON fvl.id_feature_value = fp.id_feature_value AND fvl.id_lang = ' . (int)$this->idLang . ' ' .
            'WHERE fp.id_product = p.id_product) AS features_concat, ' .
            "'' AS description_ai, " .
            "'' AS meta_title_ai, " .
            "'' AS meta_description_ai, " .
            "'' AS link_rewrite_ai, " .
            "'' AS tags_ai, " .
            "'' AS description_old_ai, " .
            "'' AS sklad_ai, " .
            "'' AS wartosci_ai"
        );
        $sql->from('product', 'p');
        $sql->innerJoin('product_shop', 'ps', 'p.id_product = ps.id_product AND ps.id_shop = ' . (int)$this->idShop);
        $sql->leftJoin('product_lang', 'pl', 'p.id_product = pl.id_product
            AND pl.id_lang = ' . (int)$this->idLang . ' AND pl.id_shop = ' . (int)$this->idShop);
        $sql->leftJoin('geminicontent_log', 'gcl', 'p.id_product = gcl.id_product');

        // NOWE JOINY: ścieżka kategorii od domyślnej w górę
        $sql->leftJoin('category', 'cdef', 'cdef.id_category = ps.id_category_default');
        $sql->leftJoin(
            'category', 'ctree',
            'ctree.nleft <= cdef.nleft AND ctree.nright >= cdef.nright
             AND ctree.level_depth > 1'
        );
        $sql->leftJoin(
            'category_lang', 'clpath',
            'clpath.id_category = ctree.id_category
             AND clpath.id_lang = ' . (int)$this->idLang . '
             AND clpath.id_shop = ' . (int)$this->idShop
        );

        if (!empty($filters['category_name'])) {
            $sql->leftJoin('category_lang', 'cl',
                'ps.id_category_default = cl.id_category
                 AND cl.id_lang = ' . (int)$this->idLang . '
                 AND cl.id_shop = ' . (int)$this->idShop
            );
        }

        $where = $this->applyFilters($sql, $filters);
        $sql->where($where);
        
        $sql->groupBy('p.id_product');
        $sql->orderBy('p.id_product ASC');

        if ($limit !== null && $limit > 0) {
            $sql->limit($limit);
        }
        
        $this->db->execute('SET SESSION group_concat_max_len = 8192');

        $result = $this->db->executeS($sql);
        return is_array($result) ? $result : [];
    }
    
    public function fetchLoggedProducts(): array
    {
        $sql = new DbQuery();
        $sql->select(
            'p.id_product, p.reference, pl.name, gcl.description_generated, gcl.seo_generated, gcl.description_date_add, gcl.seo_date_add'
        );
        $sql->from('geminicontent_log', 'gcl');
        $sql->innerJoin('product', 'p', 'p.id_product = gcl.id_product');
        $sql->innerJoin('product_shop', 'ps', 'p.id_product = ps.id_product AND ps.id_shop = ' . $this->idShop);
        $sql->leftJoin('manufacturer', 'm', 'm.id_manufacturer = p.id_manufacturer');
        $sql->leftJoin('product_lang', 'pl', 'p.id_product = pl.id_product AND pl.id_lang = ' . $this->idLang . ' AND pl.id_shop = ' . $this->idShop);
        $sql->where('ps.active = 1');
        $sql->orderBy('gcl.last_update DESC');

        $result = $this->db->executeS($sql);
        return is_array($result) ? $result : [];
    }
}