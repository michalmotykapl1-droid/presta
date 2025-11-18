<?php
/**
 * 2007-2023 PrestaShop
 *
 * ProductPro Weight Correction Service
 * Contains core logic for product weight correction and discrepancy detection.
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class ProductProWeightCorrectionService
{
    private $context;
    private $moduleInstance;
    protected $_logFile;

    public function __construct(Module $moduleInstance)
    {
        $this->context = Context::getContext();
        $this->moduleInstance = $moduleInstance;
        // Zmieniono nazwę pliku logu, aby uniknąć pokrywania się z poprzednim
        $this->_logFile = dirname(__FILE__) . '/productproweight_correction_debug_log.txt';
    }

    protected function logDebug($msg, $method = '')
    {
        $log_message_prefix = '[' . date('Y-m-d H:i:s').' (WEIGHT)] ';
        $log_message = $log_message_prefix . $method . ' > ' . $msg . "\n";
        @file_put_contents($this->_logFile, $log_message, FILE_APPEND);
    }

    private function l($string, $specific = 'productproweightservice')
    {
        return $this->moduleInstance->l($string, $specific);
    }

    /**
     * Retrieves products with zero weight.
     *
     * @return array List of products without weight.
     */
    public function getProductsWithoutWeight()
    {
        $this->logDebug('Rozpoczęto pobieranie produktów bez wagi.', __METHOD__);
        $idLang = (int)$this->context->language->id;
        $idShop = (int)$this->context->shop->id;
        $sql = (new DbQuery())->select('p.id_product, pl.name, p.ean13 AS ean, p.reference AS sku')->from('product', 'p')->innerJoin('product_shop', 'ps', 'p.id_product = ps.id_product AND ps.id_shop = ' . $idShop)->innerJoin('product_lang', 'pl', 'p.id_product = pl.id_product AND pl.id_lang = ' . $idLang . ' AND pl.id_shop = ' . $idShop)->where('p.weight = 0');
        $rows = Db::getInstance()->executeS($sql);
        $this->logDebug('Znaleziono ' . count($rows) . ' produktów z wagą 0.', __METHOD__);
        if ($rows) {
            foreach ($rows as &$row) {
                $row['suggested'] = $this->parseWeightFromText($row['name']);
                $this->logDebug('Dla produktu ID ' . $row['id_product'] . ' (bez wagi) sugerowana waga: ' . ($row['suggested'] ?? 'null'), __METHOD__);
            }
        }
        return $rows ?: [];
    }

    /**
     * Analyzes text for weights.
     * If it finds multiple, it chooses the one closest to the reference weight or the largest if no reference.
     *
     * @param string $text Product name to analyze.
     * @param float|null $referenceWeight Optional reference weight (current product weight) for comparison.
     * @return float|null Extracted weight in kg.
     */
    private function parseWeightFromText($text, $referenceWeight = null)
    {
        $this->logDebug('Analiza tekstu: "' . $text . '" z wagą referencyjną: ' . ($referenceWeight ?? 'brak'), __METHOD__);
        if (preg_match_all('/([\d.,]+)\s*(kg|g|l|ml)/i', $text, $matches, PREG_SET_ORDER)) {
            $foundWeights = [];
            foreach ($matches as $match) {
                $num = (float)str_replace(',', '.', $match[1]);
                $unit = strtolower($match[2]);
                $weightInKg = 0;
                switch ($unit) {
                    case 'g':  $weightInKg = round($num / 1000, 3); break;
                    case 'kg': $weightInKg = round($num, 3); break;
                    case 'ml': $weightInKg = round($num / 1000, 3); break;
                    case 'l':  $weightInKg = round($num, 3); break;
                }
                if ($weightInKg > 0) {
                    $foundWeights[] = $weightInKg;
                    $this->logDebug('Znaleziono wagę: ' . $num . $unit . ' -> ' . $weightInKg . 'kg', __METHOD__);
                }
            }

            $foundWeights = array_unique($foundWeights);
            $this->logDebug('Unikalne znalezione wagi: ' . implode(', ', $foundWeights), __METHOD__);

            if (empty($foundWeights)) {
                $this->logDebug('Brak wag w tekście.', __METHOD__);
                return null;
            }

            if (count($foundWeights) === 1) {
                $result = array_shift($foundWeights);
                $this->logDebug('Znaleziono jedną wagę: ' . $result . 'kg', __METHOD__);
                return $result;
            }

            if ($referenceWeight !== null) {
                $closestWeight = null;
                $minDifference = null;

                foreach ($foundWeights as $weight) {
                    $difference = abs($referenceWeight - $weight);
                    if ($minDifference === null || $difference < $minDifference) {
                        $minDifference = $difference;
                        $closestWeight = $weight;
                    }
                }
                $this->logDebug('Wiele wag, waga referencyjna ' . $referenceWeight . 'kg, najbliższa waga: ' . $closestWeight . 'kg', __METHOD__);
                return $closestWeight;
            } else {
                $result = max($foundWeights);
                $this->logDebug('Wiele wag, brak wagi referencyjnej, zwrócono największą: ' . $result . 'kg', __METHOD__);
                return $result;
            }
        }

        $this->logDebug('Brak dopasowań wag w tekście.', __METHOD__);
        return null;
    }

    /**
     * Saves suggested weights for products without weight.
     *
     * @return array Operation result.
     */
    public function saveSuggestedWeights()
    {
        $this->logDebug('Rozpoczęto zapisywanie sugerowanych wag.', __METHOD__);
        $products = $this->getProductsWithoutWeight();
        $updated_count = 0;
        foreach ($products as $product) {
            if ($product['suggested'] !== null) {
                $idProduct = (int)$product['id_product'];
                Db::getInstance()->update('product', ['weight' => (float)$product['suggested']], 'id_product = '.$idProduct);
                $updated_count++;
                $this->logDebug('Zapisano sugerowaną wagę: ' . $product['suggested'] . 'kg dla produktu ID ' . $idProduct, __METHOD__);
            }
        }
        if ($updated_count > 0) {
            $this->logDebug('Zakończono zapisywanie sugerowanych wag. Zaktualizowano ' . $updated_count . ' produktów.', __METHOD__);
            return ['success' => true, 'message' => $this->l('Zapisano pomyślnie wagę dla ') . $updated_count . $this->l(' produktów.')];
        } else {
            $this->logDebug('Nie znaleziono wag do zapisania.', __METHOD__);
            return ['success' => false, 'message' => $this->l('Nie znaleziono wag do zapisania.')];
        }
    }

    /**
     * Saves a single product weight.
     *
     * @param int $id_product Product ID.
     * @param float $weight New weight.
     * @return array Operation result.
     */
    public function saveSingleWeight($id_product, $weight)
    {
        $this->logDebug('Rozpoczęto zapisywanie pojedynczej wagi: ID=' . $id_product . ', Waga=' . $weight, __METHOD__);
        $idProduct = (int)$id_product;
        if ($idProduct > 0 && $weight >= 0) {
            $result = Db::getInstance()->update('product', ['weight' => (float)$weight], 'id_product = '.$idProduct);
            if ($result) {
                $this->logDebug('Waga dla produktu ID ' . $idProduct . ' została zaktualizowana na ' . $weight . 'kg.', __METHOD__);
                return ['success' => true, 'message' => $this->l('Waga dla produktu ID ') . $idProduct . $this->l(' została zaktualizowana.')];
            } else {
                $this->logDebug('Wystąpił błąd podczas aktualizacji wagi dla produktu ID ' . $idProduct . '.', __METHOD__);
                return ['success' => false, 'message' => $this->l('Wystąpił błąd podczas aktualizacji wagi dla produktu ID ') . $idProduct . '.'];
            }
        }
        $this->logDebug('Nieprawidłowe dane produktu lub wagi dla ID ' . $id_product . ', waga ' . $weight . '.', __METHOD__);
        return ['success' => false, 'message' => $this->l('Nieprawidłowe dane produktu lub wagi.')];
    }

    /**
     * Retrieves products with weight discrepancies.
     *
     * @return array List of products with discrepancies.
     */
    public function getProductsWithWeightDiscrepancy()
    {
        $this->logDebug('Rozpoczęto pobieranie produktów z rozbieżnością wag.', __METHOD__);
        $idLang = (int)$this->context->language->id;
        $idShop = (int)$this->context->shop->id;

        $sql = (new DbQuery())
            ->select('p.id_product, pl.name, p.weight AS current_weight')
            ->from('product', 'p')
            ->innerJoin('product_shop', 'ps', 'p.id_product = ps.id_product AND ps.id_shop = ' . $idShop)
            ->innerJoin('product_lang', 'pl', 'p.id_product = pl.id_product AND pl.id_lang = ' . $idLang . ' AND pl.id_shop = ' . $idShop)
            ->where('p.weight > 0');

        $products = Db::getInstance()->executeS($sql);
        $this->logDebug('Znaleziono ' . count($products) . ' produktów z wagą > 0.', __METHOD__);

        $discrepancy_products = [];

        if ($products) {
            foreach ($products as $product) {
                $current_weight = (float)$product['current_weight'];
                $suggested_weight = $this->parseWeightFromText($product['name'], $current_weight);

                $this->logDebug(
                    'Przetwarzany produkt do korety: ID=' . $product['id_product'] .
                    ', Nazwa="' . $product['name'] .
                    '", Waga aktualna=' . $current_weight . 'kg' .
                    ', Waga sugerowana=' . ($suggested_weight ?? 'null') . 'kg' .
                    ', Różnica=' . (($suggested_weight !== null) ? ($suggested_weight - $current_weight) : 'N/A'),
                    __METHOD__
                );

                if ($suggested_weight !== null && abs($suggested_weight - $current_weight) > 0.0001) {
                    $discrepancy_products[] = [
                        'id_product' => (int)$product['id_product'],
                        'name' => $product['name'],
                        'current_weight' => $current_weight,
                        'suggested_weight' => $suggested_weight,
                        'difference' => $suggested_weight - $current_weight,
                    ];
                    $this->logDebug('Znaleziono rozbieżność wagi dla produktu ID ' . $product['id_product'] . ': obecna=' . $current_weight . 'kg, sugerowana=' . $suggested_weight . 'kg. Dodano do listy rozbieżności.', __METHOD__);
                } else {
                    $this->logDebug('Brak znaczącej rozbieżności dla produktu ID ' . $product['id_product'] . '. Nie dodano do listy rozbieżności.', __METHOD__);
                }
            }
        }
        $this->logDebug('Zakończono pobieranie produktów z rozbieżnością wag. Znaleziono ' . count($discrepancy_products) . ' rozbieżności.', __METHOD__);
        return $discrepancy_products;
    }

    /**
     * Saves all suggested weight corrections.
     *
     * @return array Operation result.
     */
    public function saveAllWeightCorrections()
    {
        $this->logDebug('Rozpoczęto zapisywanie wszystkich sugerowanych korekt wag.', __METHOD__);
        $products = $this->getProductsWithWeightDiscrepancy();
        $updated_count = 0;

        foreach ($products as $product) {
            if (isset($product['suggested_weight']) && $product['suggested_weight'] !== null) {
                $idProduct = (int)$product['id_product'];
                $newWeight = (float)$product['suggested_weight'];
                
                $this->saveSingleWeight($idProduct, $newWeight);
                $updated_count++;
                $this->logDebug('Zapisano korektę wagi: ID=' . $idProduct . ', Nowa waga=' . $newWeight . 'kg.', __METHOD__);
            }
        }

        if ($updated_count > 0) {
            $this->logDebug('Zakończono zapisywanie wszystkich korekt wag. Zaktualizowano ' . $updated_count . ' produktów.', __METHOD__);
            return ['success' => true, 'message' => $this->l('Zapisano pomyślnie wagę dla ') . $updated_count . $this->l(' produktów.')];
        } else {
            $this->logDebug('Nie znaleziono produktów do skorygowania lub wszystkie sugestie zostały już zastosowane.', __METHOD__);
            return ['success' => false, 'message' => $this->l('Nie znaleziono produktów do skorygowania lub wszystkie sugestie zostały już zastosowane.')];
        }
    }
}
