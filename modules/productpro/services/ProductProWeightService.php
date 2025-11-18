<?php
/**
 * 2007-2023 PrestaShop
 *
 * ProductPro Weight Service
 * Contains core logic for product weight management.
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class ProductProWeightService
{
    private $context;
    private $moduleInstance;
    protected $_logFile; // Add as a class property

    public function __construct(Module $moduleInstance)
    {
        $this->context = Context::getContext();
        $this->moduleInstance = $moduleInstance;
        // Set in the constructor:
        $this->_logFile = dirname(__FILE__) . '/productproweight_debug_log.txt';
    }

    // Add a method for debug logging:
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

    public function renderWeightSelector(array $params)
    {
        $this->logDebug('Rozpoczęto renderowanie selektora wag.', __METHOD__);
        if (!isset($params['product'])) {
            $this->logDebug('Brak produktu w parametrach renderWeightSelector.', __METHOD__);
            return '';
        }

        $idLang = (int) $this->context->language->id;
        $idShop = (int) $this->context->shop->id;
        $idProduct = (int) ($params['product']['id_product'] ?? $params['product']->id ?? 0);

        if (!$idProduct) {
            $this->logDebug('Brak ID produktu w parametrach renderWeightSelector.', __METHOD__);
            return '';
        }

        $product = new Product($idProduct, false, $idLang, $idShop);

        if (!Validate::isLoadedObject($product) || empty($product->name)) {
            $this->logDebug('Produkt ID ' . $idProduct . ' nie został załadowany lub nazwa jest pusta.', __METHOD__);
            return '';
        }
        $this->logDebug('Przetwarzanie produktu: ID=' . $idProduct . ', Nazwa=' . $product->name, __METHOD__);

        $id_manufacturer = (int)$product->id_manufacturer;

        if ($id_manufacturer === 0) {
            $this->logDebug('Brak ID producenta dla produktu ID ' . $idProduct . '.', __METHOD__);
            return '';
        }
        
        $baseName = trim(preg_replace('/\s+\d+[\.,]?\d*\s*(kg|g|l|ml).*/i', '', $product->name));
        $this->logDebug('Nazwa bazowa produktu: ' . $baseName, __METHOD__);

        $sql = (new DbQuery())
            ->select('p.id_product, p.weight, pl.name')
            ->from('product', 'p')
            ->innerJoin('product_lang', 'pl', 'p.id_product = pl.id_product AND pl.id_lang = ' . $idLang . ' AND pl.id_shop = ' . $idShop)
            ->innerJoin('product_shop', 'ps', 'p.id_product = ps.id_product AND ps.id_shop = ' . $idShop)
            ->where('p.id_manufacturer = ' . $id_manufacturer)
            ->where('pl.name LIKE "' . pSQL($baseName) . '%"')
            ->where('ps.active = 1');

        $rows = Db::getInstance()->executeS($sql);
        $this->logDebug('Znaleziono ' . count($rows) . ' wariantów dla nazwy bazowej: ' . $baseName, __METHOD__);

        if (empty($rows) || count($rows) < 2) {
            $this->logDebug('Brak wystarczającej liczby wariantów dla produktu ID ' . $idProduct . '.', __METHOD__);
            return '';
        }

        $variants = [];
        $unique_weights = []; // Added to track unique weights
        foreach ($rows as $row) {
            $weightInGrams = (float)$row['weight'] * 1000;
            $roundedGrams = round($weightInGrams / 100) * 100;
            
            if ($roundedGrams === 0 && $weightInGrams > 0) {
                $roundedGrams = (int)$weightInGrams;
            }

            $variants[] = [
                'id'            => (int)$row['id_product'],
                'display_grams' => $roundedGrams,
                'link'          => $this->context->link->getProductLink((int)$row['id_product']),
                'name'          => $row['name'],
            ];
            $unique_weights[$roundedGrams] = true; // Store unique weights
            $this->logDebug('Dodano wariant: ID=' . $row['id_product'] . ', Waga=' . $roundedGrams . 'g, Nazwa=' . $row['name'], __METHOD__);
        }

        // Check if all variants have the same weight
        if (count($unique_weights) <= 1) {
            $this->logDebug('Wszystkie znalezione warianty mają tę samą wagę. Pomijam selektor wag.', __METHOD__);
            return ''; // If all variants have the same weight, don't display the weight selector
        }

        usort($variants, function($a, $b) {
            return $a['display_grams'] <=> $b['display_grams'];
        });
        $this->logDebug('Posortowano warianty.', __METHOD__);

        $this->context->smarty->assign([
            'variants'  => $variants,
            'currentId' => $idProduct,
            // Pass product data as an array for the template
            'product_data' => [
                'id_product' => $product->id,
                'url' => $this->context->link->getProductLink($product),
                'name' => $product->name,
            ],
        ]);
        $this->logDebug('Przypisano zmienne Smarty dla selektora wag.', __METHOD__);

        return $this->moduleInstance->display($this->moduleInstance->getLocalPath(), 'views/templates/hook/product_weights_selector.tpl');
    }
    
    
    public function renderFlavorSelector(array $params)
    {
        $this->logDebug('Rozpoczęto renderowanie selektora typów.', __METHOD__);

        if (!isset($params['product'])) {
            $this->logDebug('Brak produktu w parametrach renderFlavorSelector.', __METHOD__);
            return '';
        }

        $idLang = (int) $this->context->language->id;
        $idShop = (int) $this->context->shop->id;
        $idProduct = (int) ($params['product']['id_product'] ?? ($params['product']->id ?? 0));

        if (!$idProduct) {
            $this->logDebug('Brak ID produktu w parametrach renderFlavorSelector.', __METHOD__);
            return '';
        }

        $product = new Product($idProduct, false, $idLang, $idShop);
        if (!Validate::isLoadedObject($product)) {
            $this->logDebug('Produkt ID ' . $idProduct . ' nie został poprawnie załadowany.', __METHOD__);
            return '';
        }

        $this->logDebug('Przetwarzanie produktu: ID=' . $idProduct . ', Nazwa=' . $product->name, __METHOD__);

        // Funkcja pomocnicza do czyszczenia nazwy na potrzeby logiki (nie wyświetlania)
        $cleanName = function ($name) use ($product) {
            $name = trim($name);
            // usuń wagę z końca (np. "500 g", "1 kg", "500 ml")
            $name = preg_replace('/\s+\d+[\.,]?\d*\s*(kg|g|l|ml).*/iu', '', $name);

            // usuń nazwę producenta z początku, jeśli występuje
            if ($product->id_manufacturer) {
                $manufacturer = new Manufacturer((int) $product->id_manufacturer, $this->context->language->id);
                if (Validate::isLoadedObject($manufacturer) && !empty($manufacturer->name)) {
                    $pattern = '/^' . preg_quote($manufacturer->name, '/') . '\s+/iu';
                    $name = preg_replace($pattern, '', $name);
                }
            }

            return trim($name);
        };

        $currentProductCleanName = $cleanName($product->name);
        if ($currentProductCleanName === '') {
            $this->logDebug('Oczyszczona nazwa bieżącego produktu jest pusta.', __METHOD__);
            return '';
        }

        // Bazowy "typ" produktu = pierwsze słowo po oczyszczeniu
        $extractBaseToken = function ($name) {
            $parts = preg_split('/\s+/', trim($name));
            return isset($parts[0]) ? Tools::strtolower($parts[0]) : '';
        };

        $currentBaseToken = $extractBaseToken($currentProductCleanName);
        if ($currentBaseToken === '') {
            $this->logDebug('Nie udało się wyznaczyć bazowego tokenu dla produktu ID ' . $idProduct, __METHOD__);
            return '';
        }
        $this->logDebug('Bazowy token bieżącego produktu: ' . $currentBaseToken, __METHOD__);

        // Waga bazowa w gramach (zaokrąglona do 100 g)
        $productWeightInGrams = (float) $product->weight * 1000;
        $roundedCurrentProductWeight = ($productWeightInGrams > 0)
            ? (int) (round($productWeightInGrams / 100) * 100)
            : 0;
        if ($roundedCurrentProductWeight === 0 && $productWeightInGrams > 0) {
            $roundedCurrentProductWeight = (int) $productWeightInGrams;
        }
        $this->logDebug('Zaokrąglona waga bieżącego produktu (g): ' . $roundedCurrentProductWeight, __METHOD__);

        // Kategoria domyślna produktu
        $idCategory = (int) $product->id_category_default;
        if ($idCategory <= 0) {
            $this->logDebug('Brak poprawnej kategorii domyślnej dla produktu ID ' . $idProduct, __METHOD__);
            return '';
        }

        // Kandydaci: inne produkty z tej samej kategorii
        $sql = (new DbQuery())
            ->select('p.id_product, pl.name, p.weight')
            ->from('product', 'p')
            ->innerJoin(
                'product_lang',
                'pl',
                'p.id_product = pl.id_product AND pl.id_lang = ' . $idLang . ' AND pl.id_shop = ' . $idShop
            )
            ->innerJoin(
                'product_shop',
                'ps',
                'p.id_product = ps.id_product AND ps.id_shop = ' . $idShop
            )
            ->innerJoin(
                'category_product',
                'cp',
                'cp.id_product = p.id_product'
            )
            ->where('cp.id_category = ' . (int) $idCategory)
            ->where('p.id_product != ' . (int) $idProduct)
            ->where('ps.active = 1');

        $rows = Db::getInstance()->executeS($sql);
        $this->logDebug('Znaleziono ' . count($rows) . ' potencjalnych wariantów typów.', __METHOD__);

        if (empty($rows)) {
            $this->logDebug('Brak wariantów typów dla produktu ID ' . $idProduct . '.', __METHOD__);
            return '';
        }

        $variants = [];
        $seenDisplayNames = [];

        foreach ($rows as $row) {
            $rowId = (int) $row['id_product'];

            // Waga kandydata
            $rowWeightInGrams = (float) $row['weight'] * 1000;
            $roundedRowWeight = ($rowWeightInGrams > 0)
                ? (int) (round($rowWeightInGrams / 100) * 100)
                : 0;
            if ($roundedRowWeight === 0 && $rowWeightInGrams > 0) {
                $roundedRowWeight = (int) $rowWeightInGrams;
            }

            if ($roundedCurrentProductWeight > 0 && $roundedRowWeight !== $roundedCurrentProductWeight) {
                $this->logDebug(
                    'Wariant ID ' . $rowId . ' pominięty z powodu innej wagi: ' . $roundedRowWeight . ' g.',
                    __METHOD__
                );
                continue;
            }

            $fullName = trim($row['name']);
            if ($fullName === '') {
                continue;
            }

            $cleanedRowName = $cleanName($fullName);
            if ($cleanedRowName === '') {
                continue;
            }

            $rowBaseToken = $extractBaseToken($cleanedRowName);
            if ($rowBaseToken === '' || $rowBaseToken !== $currentBaseToken) {
                // Inny typ (np. Orzechy vs Kasza) – pomijamy
                continue;
            }

            if (Tools::strtolower($cleanedRowName) === Tools::strtolower($currentProductCleanName)) {
                // Taka sama logiczna nazwa jak bieżący produkt
                continue;
            }

            $displayNameLower = Tools::strtolower($fullName);
            if (isset($seenDisplayNames[$displayNameLower])) {
                // Duplikat nazwy
                continue;
            }

            // Obiekt produktu – do cen, kategorii, obrazka
            $rowProduct = new Product($rowId, false, $idLang, $idShop);
            if (!Validate::isLoadedObject($rowProduct)) {
                continue;
            }

            // Ceny
            $priceWithReduction = (float) $rowProduct->getPrice(true);
            $priceWithoutReduction = (float) $rowProduct->getPriceWithoutReduct(true);
            $hasDiscount = $priceWithoutReduction > 0 && $priceWithoutReduction > $priceWithReduction + 0.0001;

            $priceFormatted = Tools::displayPrice($priceWithReduction);
            $priceWithoutReductionFormatted = $hasDiscount ? Tools::displayPrice($priceWithoutReduction) : '';

            // Kategorie – Wyprzedaż, jeśli przypisany do kategorii 45 lub 180
            $rowCategories = Product::getProductCategories($rowId);
            $isSale = in_array(45, $rowCategories) || in_array(180, $rowCategories);

            // Obrazek – miniaturka typu side_product_default
            $cover = Product::getCover($rowId);
            $imageUrl = '';
            if ($cover && isset($cover['id_image'])) {
                $imageUrl = $this->context->link->getImageLink(
                    $rowProduct->link_rewrite,
                    (int) $cover['id_image'],
                    'side_product_default'
                );
            } else {
                $noPic = $this->context->link->getNoPictureImage($idLang);
                if (is_array($noPic) && isset($noPic['link'])) {
                    $imageUrl = $noPic['link'];
                }
            }

            $variants[] = [
                'id'                      => $rowId,
                'display_name'            => $fullName,
                'link'                    => $this->context->link->getProductLink($rowProduct),
                'image_url'               => $imageUrl,
                'price'                   => $priceFormatted,
                'price_without_reduction' => $priceWithoutReductionFormatted,
                'has_discount'            => $hasDiscount,
                'is_sale'                 => $isSale,
            ];

            $seenDisplayNames[$displayNameLower] = true;
        }

        if (empty($variants)) {
            $this->logDebug('Brak wariantów typów po przefiltrowaniu.', __METHOD__);
            return '';
        }

        usort($variants, function ($a, $b) {
            return strcoll($a['display_name'], $b['display_name']);
        });
        $this->logDebug('Posortowano warianty typów.', __METHOD__);

        $this->context->smarty->assign([
            'type_variants' => $variants,
            'currentId'     => $idProduct,
            'currentName'   => $product->name,
            'product_data'  => [
                'id_product' => $product->id,
                'url'        => $this->context->link->getProductLink($product),
                'name'       => $product->name,
            ],
        ]);
        $this->logDebug('Przypisano zmienne Smarty dla selektora typów.', __METHOD__);

        return $this->moduleInstance->display(
            $this->moduleInstance->getLocalPath(),
            'views/templates/hook/product_types_selector.tpl'
        );
    }


}
