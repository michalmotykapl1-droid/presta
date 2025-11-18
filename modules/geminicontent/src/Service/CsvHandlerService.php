<?php
declare(strict_types=1);

namespace GeminiContent\Service;

use GeminiContent\Repository\GeminiProductRepository;
use GeminiContent\Service\GeminiClientService;
use Db;
use Product;
use Validate;
use Tag;
use Configuration;
use Exception;
use Context;
use Category;
use Language;

class CsvHandlerService
{
    private GeminiProductRepository $productRepo;
    private GeminiClientService $geminiClient;
    private Context $context;

    public function __construct()
    {
        $this->productRepo = new GeminiProductRepository(Db::getInstance());
        $this->geminiClient = new GeminiClientService();
        $this->context = Context::getContext();
    }

    public function exportProducts(array $options): void
    {
        $limit = null;
        if (($options['export_range'] ?? '') === 'limit') {
            $limit = (int)($options['export_limit'] ?? 0);
            if ($limit <= 0) {
                throw new Exception('Liczba produktów do eksportu musi być większa od zera.');
            }
        }

        $filters = [];
        if (!empty($options['export_status']) && $options['export_status'] !== 'all') {
            $filters['status'] = $options['export_status'];
        }
        if (!empty($options['sku_prefixes'])) { 
            $filters['sku_prefixes'] = (string)$options['sku_prefixes']; 
        }

        $products = $this->productRepo->fetchAllForExport($limit, $filters); 
        if (empty($products)) {
            throw new Exception('Brak produktów do wyeksportowania dla wybranego zakresu i filtrów.');
        }

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=gemini-export-' . date('Y-m-d') . '.csv');
        
        $output = fopen('php://output', 'w');
        fputs($output, "\xEF\xBB\xBF"); 
        
        $header = ['id_product','reference','ean13','name','description','description_short','meta_title','meta_description','link_rewrite','tags','categories','product_type','manufacturer_name','features_concat','ingredients_src','nutrition_src','description_ai','meta_title_ai','meta_description_ai','link_rewrite_ai','tags_ai','description_old_ai','sklad_ai','wartosci_ai'];
        fputcsv($output, $header, ';');
        
        foreach ($products as $product) {
            $descriptionHtml = (string)$product['description'];
            $descriptionHtml = preg_replace('/<(?=\s*[\d,\.])/u', '&lt;', $descriptionHtml);

            $lineBreakTags = ['<br>', '<br/>', '<br />', '</p>', '</li>', '</ul>', '<h2>', '</h3>', '<h4>'];
            $descriptionWithNewlines = str_ireplace($lineBreakTags, PHP_EOL, $descriptionHtml);
            $plainText = strip_tags($descriptionWithNewlines);
            
            $plainText = html_entity_decode($plainText, ENT_QUOTES | ENT_HTML5, 'UTF-8');
$cleanedText = preg_replace('/(\r\n|\n\r|\r|\n){2,}/', PHP_EOL . PHP_EOL, $plainText);
            $product['description'] = trim($cleanedText);

            // Build row explicitly in the same order as $header to avoid key-order issues
            $ean = isset($product['ean13']) ? (string)$product['ean13'] : '';
            // Protect EAN from Excel auto-format (leading zeros) by prefixing a space
            if ($ean !== '') { $ean = ' ' . $ean; } // Excel safety

            // Compute helpers
            $ptype = $this->detectProductType(
                (string)($product['name'] ?? ''),
                (string)($product['categories'] ?? ''),
                (string)($product['features_concat'] ?? '')
            );

            // ZMIANA: Przekazujemy do funkcji oryginalny HTML ($descriptionHtml) zamiast oczyszczonego tekstu.
            // Pozwoli to na znacznie dokładniejszą analizę struktury opisu przez funkcję `extractIngredientsAndNutrition`.
            list($ingredientsSrc, $nutritionSrc) = $this->extractIngredientsAndNutrition(
                (string)($product['features_concat'] ?? ''),
                $descriptionHtml 
            );

            $row = [
                (string)$product['id_product'],
                (string)$product['reference'],
                $ean,
                (string)$product['name'],
                (string)$product['description'],
                (string)($product['description_short'] ?? ''),
                (string)$product['meta_title'],
                (string)$product['meta_description'],
                (string)$product['link_rewrite'],
                (string)$product['tags'],
                (string)($product['categories'] ?? ''),
                (string)$ptype,
                (string)($product['manufacturer_name'] ?? ''),
                (string)($product['features_concat'] ?? ''),
                (string)$ingredientsSrc,
                (string)$nutritionSrc,
                '', '', '', '', '', '', '', ''
            ];

            fputcsv($output, $row, ';');
        }
        
        fclose($output);
        exit();
    }

    public function processCsvWithAI(string $csvContent, string $prompt): string
    {
        $response = $this->geminiClient->sendPrompt($prompt . "\n\n" . $csvContent);

        if (!$response['success']) {
            throw new Exception($response['message'] ?? 'Nieznany błąd API podczas przetwarzania CSV.');
        }
        
        $processedCsvContent = $response['message'];

        $tempFilePath = tempnam(sys_get_temp_dir(), 'gemini_processed_csv_');
        if ($tempFilePath === false) {
             throw new Exception('Nie można utworzyć pliku tymczasowego.');
        }
        file_put_contents($tempFilePath, $processedCsvContent);

        return $tempFilePath;
    }
    
    public function prepareImportPreview(string $uploadedFilePath): string
    {
        ini_set('auto_detect_line_endings', '1');

        if (($handle = fopen($uploadedFilePath, 'rb')) === false) {
            throw new Exception('Nie można otworzyć pliku CSV.');
        }

        $rawHeaderLine = fgets($handle);
        if ($rawHeaderLine === false) {
            fclose($handle);
            throw new Exception('Nie można odczytać nagłówka z pliku CSV.');
        }

        $charsToRemove = "\xEF\xBB\xBF" . "\xFE\xFF" . "\xFF\xFE" . "\x00\x00\xFE\xFF" . "\xFF\xFE\x00\x00" . " \t\n\r\0\x0B";

        $rawHeaderLine = ltrim($rawHeaderLine, $charsToRemove);
        $header = explode(';', trim($rawHeaderLine));
        $header = array_map('trim', $header);
        $header = array_map(function($colName) {
            return preg_replace('/[[:cntrl:]]/u', '', $colName);
        }, $header);

        $requiredColumns = ['id_product', 'name'];
        foreach ($requiredColumns as $col) {
            if (!in_array($col, $header)) {
                fclose($handle);
                throw new Exception(sprintf('Brak wymaganej kolumny w pliku CSV: %s. Dostępne kolumny: %s', $col, implode(', ', $header)));
            }
        }
        
        $aiColumns = ['description_ai', 'meta_title_ai', 'meta_description_ai', 'link_rewrite_ai', 'tags_ai', 'description_old_ai', 'sklad_ai', 'wartosci_ai'];
        $rowsWithChanges = [];

        $colCount = count($header);

        while (($data = fgetcsv($handle, 0, ';')) !== false) {
            $data = array_pad($data, $colCount, '');
            $rowData = array_combine($header, $data);
            
            // === GEMINI PATCH: sanitize _ai fields and validate against *_src ===
            $aiColumnsList = ['description_ai','meta_title_ai','meta_description_ai','link_rewrite_ai','tags_ai','description_old_ai','sklad_ai','wartosci_ai'];
            foreach ($aiColumnsList as $c) {
                if (isset($rowData[$c])) {
                    $rowData[$c] = str_replace(';', ',', (string)$rowData[$c]);
                    $rowData[$c] = preg_replace("/\r/", "", (string)$rowData[$c]);
                }
            }
            $errors = [];
            $norm = function($s) {
                $s = (string)$s;
                $s = trim($s);
                $s = preg_replace('/\s+/u',' ', $s);
                $s = mb_strtolower($s, 'UTF-8');
                return $s;
            };
            $normPipe = function($s) use ($norm) {
                $s = (string)$s;
                $s = str_replace(';', ',', $s);
                $s = preg_replace('/\s*\|\s*/u','|',$s);
                $s = trim($s);
                return $s;
            };
            // skład 1:1
            if (!empty(trim($rowData['ingredients_src'] ?? ''))) {
                $src = $normPipe($rowData['ingredients_src']);
                $ai  = $normPipe($rowData['sklad_ai'] ?? '');
                if ($ai === '') {
                    $errors[] = 'brak_sklad_ai_przy_src';
                } elseif ($norm($src) !== $norm($ai)) {
                    $errors[] = 'sklad_ai≠ingredients_src';
                }
            }
            // wartości 1:1
            if (!empty(trim($rowData['nutrition_src'] ?? ''))) {
                $src = $normPipe($rowData['nutrition_src']);
                $ai  = $normPipe($rowData['wartosci_ai'] ?? '');
                if ($ai === '') {
                    $errors[] = 'brak_wartosci_ai_przy_src';
                } elseif ($norm($src) !== $norm($ai)) {
                    $errors[] = 'wartosci_ai≠nutrition_src';
                }
            }
            // przecieki cech do opisu
            $desc = mb_strtolower((string)($rowData['description_ai'] ?? ''), 'UTF-8');
            $features = (string)($rowData['features_concat'] ?? '');
            $leakHits = 0;
            if ($features !== '') {
                $tokens = preg_split('/[;,|\n]+/u', $features);
                foreach ($tokens as $t) {
                    $t = trim($t);
                    if ($t === '') continue;
                    $key = explode(':',$t)[0];
                    $key = trim(mb_strtolower($key,'UTF-8'));
                    if ($key !== '' && preg_match('/\\b'.preg_quote($key,'/').'\\b/u', $desc)) {
                        $leakHits++;
                        if ($leakHits >= 2) break;
                    }
                }
            }
            if ($leakHits >= 2 || preg_match('/\\bbez\\s+/u', $desc) || preg_match('/rodzaj produktu/u', $desc)) {
                $errors[] = 'opis_zawiera_cechy';
            }
            // walidacje SEO
            $mt = (string)($rowData['meta_title_ai'] ?? '');
            if ($mt !== '' && mb_strlen($mt,'UTF-8') > 60) {
                $errors[] = 'meta_title_ai_>60';
            }
            $md = (string)($rowData['meta_description_ai'] ?? '');
            if ($md !== '' and (mb_strlen($md,'UTF-8') < 90 or mb_strlen($md,'UTF-8') > 180)) {
                $errors[] = 'meta_description_ai_out_of_range';
            }
            $lr = (string)($rowData['link_rewrite_ai'] ?? '');
            if ($lr !== '' && !preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $lr)) {
                $errors[] = 'link_rewrite_ai_invalid';
            }
            if (!empty($errors)) {
                $rowData['_errors'] = $errors;
            }
            // === /GEMINI PATCH ===
            // === GEMINI PATCH (usage/warnings/storage sections) ===
            $srcText = (string)($rowData['description'] ?? '');
            $srcText .= "\n" . (string)($rowData['features_concat'] ?? '');

            $needsUsage = preg_match('/(spos[oó]b\s+u[zż]ycia|zalecane\s+spo[zż]ycie|dawkowanie)/iu', $srcText) === 1;
            $needsWarn  = preg_match('/(uwaga|ostrze[zż]enia|przeciwwskazania)/iu', $srcText) === 1;
            $needsStore = preg_match('/(przechowywan)/iu', $srcText) === 1;

            $descAI = (string)($rowData['description_ai'] ?? '');

            if ($needsUsage && !preg_match('/<h3>\\s*(jak\\s+stosowa[cć]|spos[oó]b\\s+u[zż]ycia|zalecane\\s+spo[zż]ycie|dawkowanie)\\s*<\\/h3>/iu', $descAI)) {
                $errors[] = 'brak_sekcji_sposob_uzycia';
            }
            if ($needsWarn && !preg_match('/<h3>\\s*(uwagi|ostrze[zż]enia|przeciwwskazania)\\s*<\\/h3>/iu', $descAI)) {
                $errors[] = 'brak_sekcji_uwagi';
            }
            if ($needsStore && !preg_match('/<h3>\\s*przechowywanie\\s*<\\/h3>/iu', $descAI)) {
                $errors[] = 'brak_sekcji_przechowywanie';
            }

            // Zakaz sekcji Składniki/Wartości w opisie
            if (preg_match('/<h3>\\s*składniki\\s*<\\/h3>/iu', $descAI) || preg_match('/<h3>\\s*warto[sś]ci(\\s+od[zż]ywcze|\\s+aktywne)?\\s*<\\/h3>/iu', $descAI)) {
                $errors[] = 'opis_zawiera_sklad_lub_wartosci';
            }
            
            // === GEMINI PATCH (anti-generic and no-pipes in description) ===
            $descAI_raw = (string)($rowData['description_ai'] ?? '');
            $descAI_low = mb_strtolower($descAI_raw, 'UTF-8');

            // 1) Zakaz separatorów '|' w opisie
            if (strpos($descAI_raw, '|') !== false) {
                $errors[] = 'opis_zawiera_separator_pionowy';
            }

            // 2) Zakazane frazy/metatekst
            $bannedPatterns = [
                '/neutralny\\s*,?\\s*rzeczowy\\s*opis/iu',
                '/bez\\s+obietnic\\s+medycznych/iu',
                '/opis\\s+ma\\s+by[ćc]/iu',
                '/w\\s+ramach\\s+wytycznych/iu',
                '/przejrzysta\\s+komunikacja\\s+producenta/iu',
                '/[śs]wiadom[aey]\\s+rutyn[aey]/iu',
            ];
            foreach ($bannedPatterns as $pat) {
                if (preg_match($pat, $descAI_raw)) {
                    $errors[] = 'opis_metatekst';
                    break;
                }
            }

            // 3) Nadmierne powtarzanie słowa "rutyna"
            if (preg_match_all('/rutyn/iu', $descAI_low, $m) && count($m[0]) > 2) {
                $errors[] = 'nadmierna_powtarzalnosc_rutyna';
            }
            // === /GEMINI PATCH (anti-generic and no-pipes in description) ===
            // === GEMINI PATCH (first sentence format) ===
            // Wymagaj, aby pierwszy akapit zaczynał się wzorcem „{name} – {forma + ilość} marki {manufacturer_name}.”
            // Jeśli brak producenta albo marka jest już w name, dopuszczalne jest pominięcie fragmentu „marki …”.
            $expectedName = (string)($rowData['name'] ?? '');
            $expectedBrand = trim((string)($rowData['manufacturer_name'] ?? ''));
            $firstPara = '';
            if (preg_match('/<p[^>]*>(.*?)<\\/p>/is', $desc, $mfp)) {
                $firstPara = trim(strip_tags($mfp[1]));
            }
            if ($expectedName !== '' && $firstPara !== '') {
                $ok = false;
                // Normalizuj myślnik
                $pattern = '/^\\s*' . preg_quote($expectedName, '/') . '\\s*[-–—]{1}\\s*.+/u';
                if (preg_match($pattern, $firstPara)) {
                    $ok = true;
                }
                if (!$ok) {
                    $errors[] = 'opis_pierwsze_zdanie_format';
                }
            }
            // === /GEMINI PATCH (first sentence format) ===

// === /GEMINI PATCH (usage/warnings/storage sections) ===
    
$hasChanged = false;
            foreach ($aiColumns as $aiCol) {
                if (isset($rowData[$aiCol]) && !empty(trim($rowData[$aiCol]))) {
                    $hasChanged = true;
                    break;
                }
            }
            if ($hasChanged) {
                $rowsWithChanges[] = $rowData;
            }
        }
        fclose($handle);

        if (empty($rowsWithChanges)) {
            throw new Exception('Nie znaleziono żadnych zmian w kolumnach _ai lub plik jest pusty.');
        }

        $tempPreviewPath = tempnam(sys_get_temp_dir(), 'gemini_preview_');
        if ($tempPreviewPath === false) {
            throw new Exception('Nie można utworzyć pliku tymczasowego dla podglądu.');
        }
        file_put_contents($tempPreviewPath, json_encode($rowsWithChanges));

        return $tempPreviewPath;
    }

    /**
     * KLUCZOWA ZMIANA: Metoda przyjmuje teraz tablicę ID produktów do zaimportowania.
     * @param string $importDataFilePath
     * @param array $selectedProductIds
     * @return array
     * @throws Exception
     */
    public function confirmImport(string $importDataFilePath, array $selectedProductIds = []): array
    {
        if (!file_exists($importDataFilePath)) {
            throw new Exception('Plik z danymi do importu nie istnieje.');
        }

        $importJson = file_get_contents($importDataFilePath);
        $importData = json_decode($importJson, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Błąd odczytu danych do importu z pliku tymczasowego.');
        }

        // Jeśli tablica wybranych ID jest pusta, nic nie robimy.
        if (empty($selectedProductIds)) {
            unlink($importDataFilePath);
            return ['updated' => 0, 'errors' => 0];
        }

        $updatedCount = 0;
        $errorCount = 0;
        $productActionService = new ProductActionService();
        $processed_ids = [];

        foreach ($importData as $row) {
            // === GEMINI PATCH: skip rows with validation errors from preview ===
            if (!empty($row['_errors']) && is_array($row['_errors'])) {
                \GeminiContent\Service\GeminiContentLogger::warning("confirmImport: pomijam produkt ID {$row['id_product']} z błędami: " . implode(',', $row['_errors']));
                $errorCount++;
                continue;
            }
            // sanitize _ai fields
            $aiCols = ['description_ai','meta_title_ai','meta_description_ai','link_rewrite_ai','tags_ai','description_old_ai','sklad_ai','wartosci_ai'];
            foreach ($aiCols as $c) {
                if (isset($row[$c])) {
                    $row[$c] = str_replace(';', ',', (string)$row[$c]);
                    $row[$c] = preg_replace("/\r/", "", (string)$row[$c]);
                }
            }
            // safety re-check for *_src vs *_ai
            if (!empty(trim($row['ingredients_src'] ?? '')) && (string)($row['sklad_ai'] ?? '') === '') {
                \GeminiContent\Service\GeminiContentLogger::warning("confirmImport: brak sklad_ai przy ingredients_src; ID {$row['id_product']}");
                $errorCount++;
                continue;
            }
            if (!empty(trim($row['nutrition_src'] ?? '')) && (string)($row['wartosci_ai'] ?? '') === '') {
                \GeminiContent\Service\GeminiContentLogger::warning("confirmImport: brak wartosci_ai przy nutrition_src; ID {$row['id_product']}");
                $errorCount++;
                continue;
            }
            // === /GEMINI PATCH ===

            if (!isset($row['id_product']) || !is_numeric($row['id_product']) || (int)$row['id_product'] <= 0) {
                \GeminiContent\Service\GeminiContentLogger::warning('confirmImport: Pomijam wiersz bez poprawnego id_product: '.json_encode($row));
                $errorCount++;
                continue;
            }
            $id_product = (int)$row['id_product'];

            // === NOWY WARUNEK: Sprawdzamy, czy produkt został zaznaczony ===
            if (!in_array($id_product, $selectedProductIds)) {
                continue; // Pomiń ten produkt, jeśli nie ma go na liście zaznaczonych
            }

            if (in_array($id_product, $processed_ids)) {
                \GeminiContent\Service\GeminiContentLogger::warning("confirmImport: Pomijam zduplikowany produkt ID: {$id_product} w pliku importu.");
                continue;
            }

            try {
                $productName = $row['name'] ?? '';
                $originalDescription = $row['description'] ?? '';
                $newCreativeText = $row['description_ai'] ?? null;
                $formattedOldDescription = $row['description_old_ai'] ?? null;
                $ingredientsData = $row['sklad_ai'] ?? null;
                $nutrientsData = $row['wartosci_ai'] ?? null;
                
                // Minimal sanity: description_ai is required to proceed
                if (!isset($row['description_ai']) || trim((string)$row['description_ai']) === '') {
                    \GeminiContent\Service\GeminiContentLogger::warn('Pusty description_ai – pomijam produkt ID: ' . $id_product);
                    $errorCount++;
                    continue;
                }

                $tags = null;
                if (isset($row['tags_ai']) && is_string($row['tags_ai']) && !empty(trim($row['tags_ai']))) {
                    $tags = explode(',', $row['tags_ai']);
                    $tags = array_map('trim', $tags);
                    $tags = array_filter($tags);
                }

                $seoData = [
                    'meta_title' => $row['meta_title_ai'] ?? null,
                    'meta_description' => $row['meta_description_ai'] ?? null,
                    'link_rewrite' => $row['link_rewrite_ai'] ?? null,
                    'tags' => $tags
                ];
                
                $seoData = array_filter($seoData, function($v) {
                    return $v !== null && $v !== '';
                });
                
                // POPRAWKA LOGICZNA: Zapis produktu musi być wewnątrz pętli i bloku try
                if ($productActionService->saveDescription($id_product, $productName, $originalDescription, $newCreativeText, $formattedOldDescription, $ingredientsData, $nutrientsData, $seoData)) {
                    $updatedCount++;
                } else {
                    $errorCount++;
                }
                
                $processed_ids[] = $id_product;

            } catch (Exception $e) {
                \GeminiContent\Service\GeminiContentLogger::error('Błąd (wyjątek) podczas importu dla produktu ID ' . $id_product . ': ' . $e->getMessage());
                $errorCount++;
            }
        }
        
        \GeminiContent\Service\GeminiContentLogger::info("--- Zakończono import. Zaktualizowano: {$updatedCount}, Błędy: {$errorCount} ---");
        
        unlink($importDataFilePath);

        return ['updated' => $updatedCount, 'errors' => $errorCount];
    }

    /**
     * Heurystyczne rozpoznanie typu produktu na potrzeby AI.
     * Zwraca: SPOŻYWCZY | SUPLEMENT | KOSMETYK | INNY
     */
    private function detectProductType(string $name, string $categories, string $features): string
    {
        $hay = mb_strtolower($name . ' ' . $categories . ' ' . $features, 'UTF-8');

        $hasAny = function(array $needles) use ($hay): bool {
            foreach ($needles as $n) {
                if ($n !== '' && mb_strpos($hay, mb_strtolower($n, 'UTF-8')) !== false) {
                    return true;
                }
            }
            return false;
        };

        $foodKeys = ['produkty spożywcze','miód','kasza','mąka','herbata','olej','oliwa','makaron','ryż','przypraw','bakalie','kakao','czekolad','dżem','masło orzechowe','ocet','kawa','napój','syrop','sok'];
        $suppKeys = ['suplementy diety','suplement','witamina','minerał','omega','magnez','potas','kapsuł','tablet','probiotyk','ekstrakt','mg','mcg'];
        $cosmKeys = ['kosmetyki i higiena','aromaterapia','krem','balsam','szampon','odżywka','serum','mydło','żel','tonik','maseczka','peeling','olejek','inci'];

        if ($hasAny($foodKeys))     return 'SPOŻYWCZY';
        if ($hasAny($suppKeys))     return 'SUPLEMENT';
        if ($hasAny($cosmKeys))     return 'KOSMETYK';
        return 'INNY';
    }


    /**
     * Ekstrakcja składników i wartości odżywczych.
     * 1) Najpierw z features_concat (strukturalne).
     * 2) Jeśli pusto, fallback: parsowanie description (HTML -> plaintext).
     * Zwraca: [ingredients_src (lista '|'), nutrition_src (pary 'Nazwa: wartość' '|')].
     */
        /**
         * Ekstrakcja składników i wartości odżywczych.
         * 1) Najpierw z features_concat (strukturalne).
         * 2) Jeśli pusto, fallback: parsowanie description (HTML -> plaintext).
         * Zwraca: [ingredients_src (lista '|'), nutrition_src (pary 'Nazwa: wartość' '|')].
         *
         * Zmiany v2 (minimalne względem oryginału):
         * - zawężono wykrywanie nagłówka sekcji wartości: tylko frazy zawierające 'odżyw' / 'żywieniowe' / 'nutrition'
         *   lub wskaźnik 'w 100 g/ml' / 'na 100 g/ml' — unikamy fałszywych trafień na słowo 'wartość' (np. 'wartościowe').
         * - pozostawiono pozostałą logikę bez zmian funkcjonalnych.
         */
        private function extractIngredientsAndNutrition(string $featuresConcat, string $description): array
        {
            $ingredients = [];
            $nutrition = [];
    
            // --------------------
            // 1) FEATURES (strukturalne)
            // --------------------
            $parts = array_filter(array_map('trim', explode(' | ', $featuresConcat)));
            foreach ($parts as $part) {
                $kv = explode(':', $part, 2);
                $k = isset($kv[0]) ? trim($kv[0]) : '';
                $v = isset($kv[1]) ? trim($kv[1]) : '';
                if ($k === '' && $v === '') { continue; }
    
                $kl = mb_strtolower($k, 'UTF-8');
    
                // Składniki / INCI / Skład
                if (preg_match('/^\s*(składniki|skład|inci|ingredients)\b/u', $kl)) {
                    if ($v !== '') {
                        // Rozbij: przecinki, średniki, pionowe kreski, bullet • oraz nowa linia
                        $items = preg_split('/\s*[;,|•]\s*|\R/u', $v);
                        foreach ($items as $it) {
                            $it = trim($it);
                            if ($it !== '') { $ingredients[] = $it; }
                        }
                    }
                    continue;
                }
    
                // Wartości odżywcze / aktywne (szerszy wachlarz słów-kluczy)
                $nutriKeys = [
                    'energia','wartość energetyczna','białko','tłuszcz','w tym kwasy','kwasy tłuszczowe','nasycone',
                    'węglow','cukr','sól','błonnik','sód','kcal','kj',
                    'witamina','minerał','kwas foliowy','cynk','magnez','potas','żelazo','selen','wapń','chrom','jod','koenzym','omega'
                ];
                foreach ($nutriKeys as $nk) {
                    if (mb_strpos($kl, $nk) !== false) {
                        $nutrition[] = ($v !== '') ? ($k . ': ' . $v) : $k;
                        break;
                    }
                }
            }
    
            $ingEmpty = count($ingredients) === 0;
            $nutEmpty = count($nutrition) === 0;
    
            // --------------------
            // 2) Fallback: DESCRIPTION (HTML -> plaintext + tabelki)
            // --------------------
            if ($ingEmpty || $nutEmpty) {
                $desc = $description;
    
                // Zamiana typowych separatorów tabel/list na nowe linie / pionowe kreski
                $desc = preg_replace('/<\s*tr[^>]*>/i', "\n", $desc);
                $desc = preg_replace('/<\s*\/\s*tr\s*>/i', "\n", $desc);
                $desc = preg_replace('/<\s*li[^>]*>/i', "\n• ", $desc);
                $desc = preg_replace('/<\s*\/\s*li\s*>/i', "\n", $desc);
                $desc = preg_replace('/<\s*td[^>]*>/i', ' ', $desc);
                $desc = preg_replace('/<\s*\/\s*td\s*>/i', ' ', $desc);
                $desc = preg_replace('/<\s*br\s*\/?>/i', "\n", $desc);
                $desc = preg_replace('/<\s*\/\s*p\s*>/i', "\n", $desc);
                $desc = preg_replace('/<[^>]+>/', '', $desc); // usuń resztę HTML
                $desc = str_replace(["\r\n","\r"], "\n", $desc);
    
                
            $desc = html_entity_decode($desc, ENT_QUOTES | ENT_HTML5, 'UTF-8');
// Uprość wielokrotne spacje
                $desc = preg_replace('/[ \t]{2,}/', ' ', $desc);
    
                // --- INGREDIENTS block ---
                if ($ingEmpty) {
                    // Łap po nagłówkach: Składniki / Skład / INCI / Ingredients
                    if (preg_match('/(?is)(składniki|skład|inci|ingredients)\s*[:：]?\s*(.+?)(?:\n\s*\n|(?:\n\s*(wartość\s+odż|wartości\s+odż|tabela\s+wartości|fakty\s+żywieniowe|nutrition|informacje|nazwa i adres|producent|alergen|sposób|stosowanie|przechowyw|uwagi)\b))/u', $desc, $m)) {
                        $body = trim($m[2]);
                        // rozbij po przecinkach / średnikach / bulletach / nowej linii
                        $items = preg_split('/\s*[;,|•\-]\s*|\n/u', $body);
                        foreach ($items as $it) {
                            $it = trim($it);
                            if ($it !== '') { $ingredients[] = $it; }
                        }
                    }
                }
    
                // --- NUTRITION block ---
                if ($nutEmpty) {
                    $block = '';
                    //  Zawężone wyszukiwanie sekcji żywieniowej
                    if (preg_match('/(?is)(?:'.
                                    '(?:wartość|wartości)\s+odż\w*|'.
                                    'fakty\s+żywieniowe|'.
                                    'nutrition|'.
                                    'tabela\s+wartości|'.
                                    '(?:w|na)\s*100\s*(?:g|ml)'.
                                   ')[^\n]*\n'.
                                   '(.+?)'.
                                   '(?:\n\s*\n|(?:\n\s*(składniki?|skład|inci|ingredients|sposób|stosowanie|przechowyw|ostrzeżenia|uwagi|informacje|nazwa i adres|producent|alergen)\b))'
                                   .'/', $desc, $nm)) {
                        $block = $nm[1];
                    } else {
                        // jeśli nie znaleziono sekcji, parsuj cały tekst
                        $block = $desc;
                    }
    
                    $lines = preg_split('/\R/u', (string)$block);
                    foreach ($lines as $ln) {
                        $ln = trim($ln);
                        if ($ln === '' || mb_strlen($ln, 'UTF-8') < 2) { continue; }
    
                        // 2a) "Nazwa: wartość" albo "Nazwa - wartość"
                        if (preg_match('/^([^\:–—\-]{2,}?)\s*[:–—\-]\s*(.+)$/u', $ln, $mm)) {
                            $name = trim($mm[1]);
                            $val  = trim($mm[2]);
                            $nl = mb_strtolower($name, 'UTF-8');
                            $isNut = false;
                            $nutriKeys2 = [
                                'energia','wartość energetyczna','białko','tłuszcz','w tym','nasycone','węglow','cukr','sól','błonnik','sód','kcal','kj',
                                'witamina','kwas foliowy','cynk','magnez','potas','żelazo','selen','wapń','chrom','jod','koenzym','omega','rws','nrv','iu','mg','mcg','g','%'
                            ];
                            foreach ($nutriKeys2 as $nk) {
                                if (mb_strpos($nl, $nk) !== false) { $isNut = true; break; }
                            }
                            if ($isNut) { $nutrition[] = $name . ': ' . $val; continue; }
                        }
    
                        // 2b) Linia zawierająca tylko wartość energetyczną bez nazwy (oba układy)
                        if (preg_match('/\b\d{2,4}\s*k[jJ]\s*\/\s*\d{2,4}\s*kcal\b/u', $ln) || preg_match('/\b\d{2,4}\s*kcal\s*\/\s*\d{2,4}\s*k[jJ]\b/u', $ln)) {
                            $val = preg_replace('/^\s*wartość\s*energetyczna\s*[:–—\-]?\s*/iu', '', $ln);
                            $nutrition[] = 'Wartość energetyczna: ' . trim($val);
                            continue;
                        }
    
                        // 2c) "Nazwa wartość" (bez separatora) – typowe dla <td>Label</td><td>123 g</td>
                        if (preg_match('/^([A-Za-zĄĆĘŁŃÓŚŹŻąćęłńóśźż\s\/\(\)]+?)\s+([<≤]?\s*[\d\.,]+(?:\s*(?:g|mg|µg|μg|mcg|kj|kJ|kcal|ml|l|%)\b.*))$/u', $ln, $mm2)) {
                            $name = trim($mm2[1]);
                            $val  = trim($mm2[2]);
                            $nl   = mb_strtolower($name, 'UTF-8');
                            $nutriKeys3 = [
                                'energia','wartość energetyczna','białko','tłuszcz','w tym','nasycone','węglow','cukr','sól','błonnik','sód','kcal','kj',
                                'witamina','kwas foliowy','cynk','magnez','potas','żelazo','selen','wapń','chrom','jod','koenzym','omega','rws','nrv','iu'
                            ];
                            foreach ($nutriKeys3 as $nk) {
                                if (mb_strpos($nl, $nk) !== false) {
                                    $nutrition[] = $name . ': ' . $val;
                                    break;
                                }
                            }
                        }
                    }
                }
            }
    
            // Filtracja składników: usuń oczywiste metadane
            $filteredIng = [];
            foreach ($ingredients as $ing) {
                $ingTrim = trim($ing);
                if ($ingTrim === '') { continue; }
                $ingNorm = mb_strtolower($ingTrim, 'UTF-8');
                if (strpos($ingTrim, ':') !== false) {
                    if (!(preg_match('/^(alergen|zawiera)\b/u', $ingNorm))) {
                        continue;
                    }
                }
                if (preg_match('/^(bio|bez|kraj|pochodzen|masa|pojem|certyfikat|sposób|stosowanie|przechowywanie|warto|zawarto|energi|białko|tłuszcz|węglow|cukr|sól|błonnik|sód|kwasy|omega|witamina|minerał|nrv|rws)\b/u', $ingNorm)) {
                    continue;
                }
                if (in_array($ingNorm, ['tak','nie','brak'], true)) { continue; }
                if (mb_strlen($ingNorm, 'UTF-8') < 2) { continue; }
                $filteredIng[] = $ingTrim;
            }
            $ingredients = $filteredIng;
    
            // --- DODATKOWY FALLBACK DLA MIODU (bez zmian) ---
            if (count($ingredients) === 0) {
                $descH = $description;
                $descH = preg_replace('/<\s*br\s*\/?>/i', "\n", $descH);
                $descH = preg_replace('/<\s*\/\s*p\s*>/i', "\n", $descH);
                $descH = preg_replace('/<[^>]+>/', '', $descH);
                $descH = str_replace(["\r\n","\r"], "\n", $descH);
    
                $opText = '';
                if (preg_match('/(?is)\bopis\b\s*[:：]?\s*(.+?)(?:\n\s*\n|(?:\n\s*(wartość|wartości|zawartość|skład|składniki|inci|ingredients|sposób|stosowanie|przechowywanie|ostrzeżenia|uwagi)\b))/u', $descH, $mop)) {
                    $opText = trim($mop[1]);
                } else {
                    $opText = $descH;
                }
    
                if (preg_match('/(?im)^\s*miód[^\n\.]*\.?/u', $opText, $mh)) {
                    $ing = trim($mh[0]);
                    $ing = rtrim($ing, ". ");
                    if ($ing !== '') { $ingredients[] = $ing; }
                } else {
                    if (preg_match('/(?i)\bmiód\b[^\n\.]*/u', $opText, $mh2)) {
                        $ing = trim($mh2[0]);
                        $ing = rtrim($ing, ". ");
                        if ($ing !== '') { $ingredients[] = $ing; }
                    }
                }
            }
    
            // Deduplikacja (z zachowaniem kolejności)
            $ingredients = array_values(array_unique($ingredients));
            $nutrition   = array_values(array_unique($nutrition));
    
            $ingredientsSrc = implode('|', $ingredients);
            $nutritionSrc   = implode('|', $nutrition);
    
            return [$ingredientsSrc, $nutritionSrc];
        }


    /**
     * Load a single row from the preview CSV by id_product.
     */
    public function loadPreviewRowById(string $previewFilePath, int $idProduct): ?array
    {
        if (!file_exists($previewFilePath)) {
            return null;
        }
        $handle = fopen($previewFilePath, 'r');
        if (!$handle) {
            return null;
        }
        $header = null;
        while (($data = fgetcsv($handle, 0, ';')) !== false) {
            if ($header === null) {
                $header = array_map('trim', $data);
                continue;
            }
            $row = array_combine($header, $data);
            if (!$row) { continue; }
            if (isset($row['id_product']) && (int)$row['id_product'] === (int)$idProduct) {
                fclose($handle);
                return $row;
            }
        }
        fclose($handle);
        return null;
    }

    /**
     * Build grouped preview array for template (per product).
     */
    public function buildGroupedPreview(string $previewFilePath, int $idLang, int $idShop): array
    {
        $result = [];
        if (!file_exists($previewFilePath)) { return $result; }
        $h = fopen($previewFilePath, 'r'); if (!$h) { return $result; }
        $header = null;
        while (($d = fgetcsv($h, 0, ';')) !== false) {
            if ($header === null) { $header = array_map('trim', $d); continue; }
            $row = array_combine($header, $d); if (!$row) { continue; }
            $idp = (int)($row['id_product'] ?? 0); if ($idp <= 0) { continue; }
            $product = new \Product($idp, false, $idLang, $idShop);
            $name = \Validate::isLoadedObject($product) ? $product->name : ($row['name'] ?? '');
            $reference = $row['reference'] ?? '';

            $fields = [];
            $map = [
                'description_ai' => 'Opis (HTML)',
                'meta_title_ai' => 'Meta tytuł',
                'meta_description_ai' => 'Meta opis',
                'link_rewrite_ai' => 'Przyjazny URL',
                'tags_ai' => 'Tagi',
                'sklad_ai' => 'Skład',
                'wartosci_ai' => 'Wartości',
            ];
            foreach ($map as $key => $label) {
                $new = $row[$key] ?? '';
                $cur = '';
                if (\Validate::isLoadedObject($product)) {
                    if ($key === 'description_ai') $cur = $product->description;
                    elseif ($key === 'meta_title_ai') $cur = $product->meta_title;
                    elseif ($key === 'meta_description_ai') $cur = $product->meta_description;
                    elseif ($key === 'link_rewrite_ai') $cur = $product->link_rewrite;
                }
                $fields[] = ['name'=>$key, 'label'=>$label, 'current'=>$cur, 'new'=>$new, 'suggest_apply'=> (trim($new) !== '')];
            }

            $result[] = ['id_product'=>$idp, 'name'=>$name, 'reference'=>$reference, 'fields'=>$fields];
        }
        fclose($h); return $result;
    }
}