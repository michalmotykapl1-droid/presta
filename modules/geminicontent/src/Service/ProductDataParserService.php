<?php
// /modules/geminicontent/src/Service/ProductDataParserService.php

declare(strict_types=1);

namespace GeminiContent\Service;

use Tools;
use DOMDocument;
use DOMXPath;

class ProductDataParserService
{
    private const SECTION_MAP = [
        'opis' => ['/<h2[^>]*>Opis<\/h2>/iu', '/<h2[^>]*>Opis Produktu<\/h2>/iu'],
        'skladniki' => ['/<h2[^>]*>Składniki<\/h2>/iu', '/<h2[^>]*>Skład<\/h2>/iu'],
        'wartosci_odzywcze' => [
            '/<h2[^>]*>Wartość odżywcza.*?<\/h2>/iu',
            '/<h2[^>]*>Wartości odżywcze.*?<\/h2>/iu',
            '/<h2[^>]*>Informacja o wartości odżywczej.*?<\/h2>/iu',
            '/<h2[^>]*>Tabela wartości odżywczych.*?<\/h2>/iu',
            '/<h2[^>]*>Fakty żywieniowe.*?<\/h2>/iu',
            '/<h2[^>]*>Wartość odżywcza w 100g.*?<\/h2>/iu', 
            '/<h2[^>]*>Wartość odżywcza w 100 g.*?<\/h2>/iu',
        ],
        'sposob_przygotowania' => ['/<h2[^>]*>Sposób przygotowania.*?<\/h2>/iu'],
        'sposob_uzycia' => ['/<h2[^>]*>Sposób użycia.*?<\/h2>/iu'],
        'informacje_alergenne' => [
            '/<h2[^>]*>Informacje dla alergik.*?<\/h2>/iu',
            '/<h2[^>]*>Informacja o alergenach<\/h2>/iu',
            '/<h2[^>]*>Informacje alergiczne<\/h2>/iu',
            '/<h2[^>]*>Alergeny<\/h2>/iu',
        ],
        'przechowywanie' => ['/<h2[^>]*>Przechowywanie<\/h2>/iu', '/<h2[^>]*>Zalecane warunki przechowywania<\/h2>/iu'],
        'kraj_pochodzenia' => ['/<h2[^>]*>Kraj pochodzenia<\/h2>/iu'],
        'producent' => [
            '/<h2[^>]*>Producent\s*\/\s*Podmiot odpowiedzialny<\/h2>/iu',
            '/<h2[^>]*>Producent<\/h2>/iu',
            '/<h2[^>]*>Producent\s*i\s*adres<\/h2>/iu',
            '/<h2[^>]*>Producent\s*[–-]\s*Nazwa i adres<\/h2>/iu',
        ],
        'dodatkowe_informacje' => ['/<h2[^>]*>Dodatkowe informacje<\/h2>/iu'],
    ];

    private array $ingredientsBlacklist = []; 
    private array $ingredientsBlacklistLower = []; 

    public function __construct()
    {
        $this->ingredientsBlacklist = [
            'brak szczegółowych informacji o składnikach',
            'brak składników',
            'nie dotyczy',
            'nie podano',
            'sprawdź opakowanie',
            'zawartość kakao: minimum',
            'zawiera tylko naturalnie występujące cukry',
            'wyłącznie ze względu na naturalne występowanie sodu',
            'nadmierne spożycie może mieć efekt przeczyszczający',
            'certyfikowany składnik ekologiczny',
            'certyfikowany składnik bio',
            'ilość w 100g',
            'w 100g',
            'na 100g',
            'ilość',
            'minimalnie',
            'maksymalnie',
            'wartość energetyczna',
            'tłuszcz', 'węglowodany', 'białko', 'sól', 'błonnik', 'cukry',
        ];

        foreach ($this->ingredientsBlacklist as $item) {
            $this->ingredientsBlacklistLower[] = Tools::strtolower($item);
        }
    }

    public function parseHtml(string $htmlContent): array
    {
        \GeminiContent\Service\GeminiContentLogger::debug('ProductDataParserService: Rozpoczynam parsowanie HTML starego opisu. Treść (początek): ' . substr($htmlContent, 0, 500) . '...');
        $parsedData = [];
        $rawSections = []; 
        $fullOldDescription = $htmlContent; 

        $cleanedHtml = preg_replace('/\s+/', ' ', $htmlContent);
        $cleanedHtml = str_replace(['<p> </p>', '<ul> </ul>', '<p></p>', '<br>', '<br/>', '<br />'], ' ', $cleanedHtml);
        $cleanedHtml = trim($cleanedHtml);

        $segments = preg_split('#(<h2[^>]*>.*?<\/h2>)#iu', $cleanedHtml, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

        $currentSectionKey = 'ogolny_tekst_przed_sekcjami';
        $currentSectionContentBuffer = '';

        foreach ($segments as $segment) {
            $segment = trim($segment);
            if (empty($segment)) {
                continue;
            }

            if (preg_match('#<h2[^>]*>(.*?)<\/h2>#iu', $segment, $matches)) {
                if (!empty($currentSectionContentBuffer)) {
                    $rawSections[$currentSectionKey] = trim($currentSectionContentBuffer);
                }
                $currentSectionContentBuffer = '';

                $headerText = $matches[0];
                $headerTitle = $matches[1];

                $matchedKey = null;
                foreach (self::SECTION_MAP as $key => $regexes) {
                    foreach ($regexes as $regex) {
                        if (preg_match($regex, $headerText)) {
                            $matchedKey = $key;
                            break 2;
                        }
                    }
                }
                $currentSectionKey = $matchedKey ?: Tools::link_rewrite($headerTitle);
            } else {
                $currentSectionContentBuffer .= ' ' . $segment;
            }
        }
        if (!empty($currentSectionContentBuffer)) {
            $rawSections[$currentSectionKey] = trim($currentSectionContentBuffer);
        }

        foreach ($rawSections as $key => $content) {
            if (empty($content) || (is_string($content) && trim($content) === '')) {
                continue;
            }

            if ($key === 'sposob_przygotowania') {
                $prep = strip_tags($content);
                $prep = preg_replace('/\b(?:WARTOŚĆ\s+ODŻYWCZA|Fakty\s+żywieniowe|Wartość\s+energetyczna|Wartość\s+odżywcza\s+w\s+100g)\b.*$/ius', '', $prep);
                $parsedData['sposob_przygotowania'] = trim($prep);
            } elseif ($key === 'skladniki') {
                $parsedData[$key] = $this->parseIngredients($content);
            } elseif ($key === 'producent') { 
                $text = trim(preg_replace('/\s+/', ' ', strip_tags($content)));
                $parsedData[$key] = $text;
            } elseif ($key === 'sposob_uzycia') { 
                $parsedData[$key] = $this->processSectionContentOnly($key, $content);
            }
            else {
                if ($key !== 'ogolny_tekst_przed_sekcjami' && $key !== 'opis') {
                    if (isset($parsedData['opis']) && is_string($parsedData['opis'])) {
                        $parsedData['opis'] = [$parsedData['opis']];
                    }
                    if (isset($parsedData['opis']) && is_array($parsedData['opis'])) {
                        $tempContent = $this->processSectionContentOnly($key, $content);
                        if (is_array($tempContent)) {
                            $parsedData['opis'] = array_merge($parsedData['opis'], $tempContent);
                        } elseif (!empty($tempContent)) {
                             $parsedData['opis'][] = $tempContent;
                        }
                    } else { 
                        $parsedData['opis'] = $this->processSectionContentOnly($key, $content);
                    }
                    \GeminiContent\Service\GeminiContentLogger::debug("ProductDataParserService: Scalono niemapowaną sekcję '{$key}' do 'opis'.");
                } else {
                    $parsedData[$key] = $this->processSectionContentOnly($key, $content);
                }
            }
        }

        $nfSource = $rawSections['wartosci_odzywcze'] ?? $fullOldDescription; 
        $allFacts = $this->parseNutritionalFacts($nfSource); 
        if (!empty($allFacts)) {
            $parsedData['wartosci_odzywcze'] = $allFacts;
        }


        if (isset($parsedData['ogolny_tekst_przed_sekcjami']) && empty(trim((string)$parsedData['ogolny_tekst_przed_sekcjami']))) {
            unset($parsedData['ogolny_tekst_przed_sekcjami']);
        }

        \GeminiContent\Service\GeminiContentLogger::debug('ProductDataParserService: Zakończono parsowanie. Wynik: ' . json_encode($parsedData, JSON_UNESCAPED_UNICODE));
        return $parsedData;
    }

    private function processSectionContentOnly(string $sectionKey, string $content)
    {
        $content = preg_replace('/^<p>(.*)<\/p>$/isU', '$1', $content);
        $content = trim($content);

        $paragraphs = preg_split('/(?:<br\s*\/?>|<\/p><p>|\n|\.(?=\s*\p{Lu})|!\s*(?=\s*\p{Lu})|\?(?=\s*\p{Lu})|\.(?=\s*$)|\n)/iu', $content, -1, PREG_SPLIT_NO_EMPTY);
        $paragraphs = array_values(array_filter(array_map('trim', array_map('strip_tags', $paragraphs)))); 
        
        return count($paragraphs) > 1 ? $paragraphs : (empty($paragraphs) ? '' : $paragraphs[0]);
    }

    private function parseIngredients(string $htmlContent): array
    {
        $ingredients = [];
        $cleanedText = trim(strip_tags($htmlContent));

        $blacklistLower = $this->ingredientsBlacklistLower; 

        $doc = new DOMDocument();
        libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="utf-8">' . $htmlContent);
        libxml_clear_errors();
        $xpath = new DOMXPath($doc);

        $nodes = $xpath->query('//li | //p');

        if ($nodes->length > 0) {
            foreach ($nodes as $node) {
                $item = trim(strip_tags($node->nodeValue));
                $parts = preg_split('/\s*(?:,|;| i | oraz |:)\s*/u', $item, -1, PREG_SPLIT_NO_EMPTY);
                
                foreach ($parts as $part) {
                    $part = trim($part);
                    if ($part === '') continue;

                    $part = preg_replace('/^(składniki?|sklad|zawiera|z czego|lista składników|produkty)\s*:\s*/iu', '', $part);
                    $part = trim($part);
                    if (empty($part)) continue;

                    if (preg_match('/\d(?:[.,]\d+)?\s*(?:kJ|kcal|g|mg|%)\b/iu', $part)) { 
                        \GeminiContent\Service\GeminiContentLogger::debug('parseIngredients (DOM List/Paragraph, split): Pomijam potencjalny NF w składnikach: ' . $part);
                        continue;
                    }

                    $partLower = Tools::strtolower($part);
                    $isBlacklisted = false;
                    foreach ($blacklistLower as $blacklistItemLower) {
                        if (strpos($partLower, $blacklistItemLower) !== false) {
                            \GeminiContent\Service\GeminiContentLogger::debug('parseIngredients (DOM List/Paragraph, split): Pomijam z czarnej listy: ' . $part);
                            $isBlacklisted = true;
                            break;
                        }
                    }
                    if (!$isBlacklisted) {
                        $normalizedPartKey = Tools::link_rewrite($partLower); 
                        if (!isset($ingredients[$normalizedPartKey])) { 
                            $ingredients[$normalizedPartKey] = $part; 
                            \GeminiContent\Service\GeminiContentLogger::debug('parseIngredients (DOM List/Paragraph, split): Dodano składnik: ' . $part);
                        }
                    }
                }
            }
        } else { // Fallback na parsowanie czystego tekstu, jeśli nie ma HTML list/paragrafów
            $textToSplit = preg_replace('/^(składniki|sklad|zawiera|z czego|lista składników|produkty):?\s*/iu', '', $cleanedText);
            $textToSplit = trim($textToSplit);

            if (empty($textToSplit)) {
                return [];
            }

            $splitRegex = '/(?:,\s*|;\s*|\.\s*(?!\d)|(?<!\d\.)\.\s*|\b(?:oraz|i)\b)/iu'; 
            $potentialIngredients = preg_split($splitRegex, $textToSplit, -1, PREG_SPLIT_NO_EMPTY);
            
            foreach ($potentialIngredients as $item) {
                $item = trim($item);
                $itemLower = Tools::strtolower($item);

                if (preg_match('/\d(?:[.,]\d+)?\s*(?:kJ|kcal|g|mg|%)\b/iu', $item)) { 
                    \GeminiContent\Service\GeminiContentLogger::debug('parseIngredients (Text Split): Pomijam potencjalny NF w składnikach: ' . $item);
                    continue;
                }

                $isBlacklisted = false;
                foreach ($blacklistLower as $blacklistItemLower) {
                    if (strpos($itemLower, $blacklistItemLower) !== false) {
                        $isBlacklisted = true;
                        \GeminiContent\Service\GeminiContentLogger::debug('parseIngredients (Text Split): Pomijam z czarnej listy: ' . $item);
                        break;
                    }
                }
                if (!$isBlacklisted && !empty($item)) {
                    $normalizedItemKey = Tools::link_rewrite($itemLower);
                    if (!isset($ingredients[$normalizedItemKey])) {
                        $ingredients[$normalizedItemKey] = $item;
                        \GeminiContent\Service\GeminiContentLogger::debug('parseIngredients (Text Split): Dodano składnik: ' . $item);
                    }
                }
            }
        }

        return array_values($ingredients); 
    }

    private function parseHtmlList(string $htmlList): array
    {
        preg_match_all('/<li>(.*?)<\/li>/isU', $htmlList, $matches);
        $listItems = array_map('strip_tags', $matches[1]);
        $listItems = array_map('trim', $listItems);
        
        $filteredItems = [];
        $blacklistLower = $this->ingredientsBlacklistLower; 
        foreach ($listItems as $item) {
            $itemLower = Tools::strtolower($item);
            $isBlacklisted = false;
            foreach ($blacklistLower as $blacklistItemLower) {
                if (strpos($itemLower, $blacklistItemLower) !== false) {
                    $isBlacklisted = true;
                    \GeminiContent\Service\GeminiContentLogger::debug('parseIngredients (HTML List): Pomijam z czarnej listy: ' . $item);
                    break;
                }
            }
            if (preg_match('/\d(?:[.,]\d+)?\s*(?:kJ|kcal|g|mg|%)\b/iu', $item)) { 
                \GeminiContent\Service\GeminiContentLogger::debug('parseIngredients (HTML List): Pomijam potencjalny NF w składnikach: ' . $item);
                continue;
            }

            if (!$isBlacklisted && !empty($item)) {
                $filteredItems[] = $item;
            }
        }
        return array_filter($filteredItems);
    }

    private function ensureUnit(string $val, string $default=' g'): string {
        return preg_match('/\b(?:kJ|kcal|g|mg|µg|mcg|%)\b/iu', $val) ? $val : ($val.$default);
    }

    private function parseNutritionalFacts(string $html): array
    {
        $hasList = (stripos($html, '<li') !== false);
        $plain    = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $norm     = preg_replace('/[\x{00A0}\x{202F}\s]+/u', ' ', trim($plain));
        $norm     = str_replace(['—','–','•','·'], '-', $norm);
        $lower    = mb_strtolower($norm, 'UTF-8');

        $hasTrigger =
            (preg_match('/warto(?:ść|sci|ści)\s*od(żywcze|żywcza)/iu', $lower) === 1) ||
            (preg_match('/\b(?:w|na|per)\s*100\s*(?:g|ml)\b/iu', $lower) === 1) || 
            (preg_match('/\b(kJ|kcal|t[łl]uszcz|we?glowod|bia[łl]ko|s[óo]l|b[łl]onnik|poliole)\b/iu', $lower) === 1);

        if (!$hasTrigger) {
            \GeminiContent\Service\GeminiContentLogger::debug('parseNutritionalFacts: brak triggerów – zwracam pustą tablicę.');
            return [];
        }

        $lines = [];

        if (stripos($html, '<table') !== false) {
            $doc = new DOMDocument();
            libxml_use_internal_errors(true);
            $doc->loadHTML('<?xml encoding="utf-8">' . $html);
            libxml_clear_errors();
            $xp = new DOMXPath($doc);

            foreach ($xp->query('//table//tr') as $tr) {
                $cells = [];
                foreach ($xp->query('.//th|.//td', $tr) as $td) {
                    $txt = html_entity_decode($td->textContent ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    $txt = preg_replace('/[\x{00A0}\x{202F}\s]+/u', ' ', trim($txt));
                    $txt = str_replace(['—','–','•','·'], '-', $txt);
                    if ($txt !== '') { $cells[] = $txt; }
                }

                if (count($cells) >= 2) {
                    $label = $cells[0];
                    $value = $cells[count($cells) - 1]; 
                    if (preg_match('/\d/', $value)) { 
                        $lines[] = $label . ' ' . $value; 
                    }
                }
            }
        }

        if (!$lines) {
            if ($hasList) { 
                if (preg_match_all('/<li[^>]*>(.*?)<\/li>/isu', $html, $m)) {
                    $lines = array_map(static function ($x) {
                        $t = html_entity_decode(strip_tags($x), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                        $t = preg_replace('/[\x{00A0}\x{202F}\s]+/u', ' ', trim($t));
                        return str_replace(['—','–','•','·'], '-', $t);
                    }, $m[1]);
                }
            }
            if (!$lines) {
                $tmpHtml = preg_replace('/<\/t[rd]>/i', "\n", $html);
                $tmpHtml = preg_replace('/<br\s*\/?>/i', "\n", $tmpHtml);
                $plain_content_for_split = html_entity_decode(strip_tags($tmpHtml), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $lines = preg_split('/\R/u', $plain_content_for_split);

                $filteredLines = array_values(array_filter(array_map('trim', $lines)));
                if (count($filteredLines) < 2 && !empty($norm)) {
                    \GeminiContent\Service\GeminiContentLogger::debug('parseNutritionalFacts: Wykryto mało linii. Uruchamiam inteligentny podział dla sklejonego tekstu.');

                    // --- NOWA LOGIKA: Izolacja bloku danych odżywczych ---
                    $dataBlock = $norm; // Domyślnie cały tekst
                    $headerPattern = '/(?:WARTOŚĆ|WARTOŚCI)\s+ODŻYWCZ(?:A|E)\s+W\s+100\s*(?:g|ml)\s*[:–—]?\s*/iu';
                    
                    $parts = preg_split($headerPattern, $norm);
                    if (count($parts) > 1) {
                        $dataBlock = $parts[1]; // Bierzemy tekst po nagłówku
                        \GeminiContent\Service\GeminiContentLogger::debug('parseNutritionalFacts: Znaleziono nagłówek i wyizolowano blok danych.');
                    } else {
                         \GeminiContent\Service\GeminiContentLogger::debug('parseNutritionalFacts: Nie znaleziono standardowego nagłówka, parsowanie całego tekstu.');
                    }
                    // --- KONIEC NOWEJ LOGIKI ---

                    $splitPattern = '/(?<!^)\s*[:-]?\s*(?=\b(Wartość\s+energetyczna|T[łl]uszcz|w\s+tym\s+kwasy|w\s+tym\s+cukry|poliole|b[łl]onnik|Bia[łl]ko|S[óo]l|W[ęe]glowodany)\b)/iu';
                    
                    // ZMIANA: Użyj wyizolowanego $dataBlock zamiast $norm
                    $potentialLines = preg_split($splitPattern, $dataBlock, -1, PREG_SPLIT_NO_EMPTY);

                    if (count($potentialLines) > 1) {
                        \GeminiContent\Service\GeminiContentLogger::debug('parseNutritionalFacts: Inteligentny podział znalazł ' . count($potentialLines) . ' linii.');
                        $lines = $potentialLines;
                    } else {
                        \GeminiContent\Service\GeminiContentLogger::debug('parseNutritionalFacts: Inteligentny podział nie powiódł się, fallback na podział po kropce/średniku.');
                        $lines = preg_split('/[\.;]\s+/', $norm, -1, PREG_SPLIT_NO_EMPTY);
                    }
                }
                $lines = array_values(array_filter(array_map(static function ($x) {
                    $t = html_entity_decode(strip_tags((string)$x), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    $t = preg_replace('/[\x{00A0}\x{202F}\s]+/u', ' ', trim($t));
                    return str_replace(['—','–','•','·'], '-', $t);
                }, $lines)));
            }
        }

        $rxNumG = '([\d]+(?:[.,]\d+)?)(?:\s*g)?'; 
        $found = [
            'energy_kj' => null, 'energy_kcal' => null,
            'fat' => null, 'sat' => null, 'carb' => null, 'sugars' => null, 'polyols' => null,
            'fiber' => null, 'protein' => null, 'salt' => null, 'sodium_mg' => null,
        ];

        foreach ($lines as $rawLine) {
            $line = mb_strtolower($rawLine, 'UTF-8');
            $sep = '\s*[:\-–—]?\s*';
            if (!$found['energy_kj'] || !$found['energy_kcal']) {
                if (preg_match('/(?:(?P<kj>[\d]+(?:[.,]\d+)?)\s*k[jJ][^0-9]*?(?P<kcal>[\d]+(?:[.,]\d+)?)\s*kcal|(?P<kcal2>[\d]+(?:[.,]\d+)?)\s*kcal[^0-9]*?(?P<kj2>[\d]+(?:[.,]\d+)?)\s*k[jJ])/iu', $line, $m)) {
                    $found['energy_kj']    = $found['energy_kj']    ?: ($m['kj']    ?? $m['kj2']    ?? null);
                    $found['energy_kcal'] = $found['energy_kcal'] ?: ($m['kcal'] ?? $m['kcal2'] ?? null);
                } elseif (preg_match('/([\d]+(?:[.,]\d+)?)\s*k[jJ]\b/u', $line, $m)) {
                    $found['energy_kj'] = $found['energy_kj'] ?: $m[1];
                } elseif (preg_match('/([\d]+(?:[.,]\d+)?)\s*kcal\b/u', $line, $m)) {
                    $found['energy_kcal'] = $found['energy_kcal'] ?: $m[1];
                }
            }
            if (!$found['fat']       && preg_match('/\bt[łl]uszcz\b'.$sep.$rxNumG.'/u', $line, $m))          $found['fat']      = $m[1];
            if (!$found['sat']       && preg_match('/\b(?:kwasy\s*t[łl]uszczowe\s*nasycone|kwasy\s*nasycone)\b'.$sep.$rxNumG.'/u', $line, $m)) $found['sat']      = $m[1];
            if (!$found['carb']      && preg_match('/\bw[ęe]glowodany\b'.$sep.$rxNumG.'/u', $line, $m))       $found['carb']     = $m[1];
            if (!$found['sugars']    && preg_match('/\bw\s+tym\s+cukry\b'.$sep.$rxNumG.'/u', $line, $m))            $found['sugars']   = $m[1];
            if (!$found['polyols']   && preg_match('/\bpoliole\b'.$sep.$rxNumG.'/u', $line, $m))          $found['polyols']  = $m[1];
            if (!$found['fiber']     && preg_match('/\bb[łl]onnik\b'.$sep.$rxNumG.'/u', $line, $m))          $found['fiber']    = $m[1];
            if (!$found['protein']   && preg_match('/\bbia[łl]ko\b'.$sep.$rxNumG.'/u', $line, $m))           $found['protein']  = $m[1];
            if (!$found['salt']      && preg_match('/\bs[óo]l\b'.$sep.$rxNumG.'/u', $line, $m))           $found['salt']     = $m[1];
            if (!$found['sodium_mg'] && preg_match('/\bs[óo]d\b'.$sep.'([\d]+(?:[.,]\d+)?)\s*mg/u', $line, $m)) $found['sodium_mg']= $m[1];
        }

        if (!$found['salt'] && $found['sodium_mg']) {
            $saltG = (float)str_replace(',', '.', $found['sodium_mg']) * 2.5 / 1000;
            $found['salt'] = rtrim(rtrim(number_format($saltG, 2, ',', ''), '0'), ',') . ' g'; 
        }

        $out = [];
        if ($found['energy_kj'] || $found['energy_kcal']) {
            $ener = trim(
                ($found['energy_kj']    ? str_replace('.', ',', $found['energy_kj']).' kJ'    : '') .
                (($found['energy_kj'] && $found['energy_kcal']) ? ' / ' : '') .
                ($found['energy_kcal'] ? str_replace('.', ',', $found['energy_kcal']).' kcal' : '')
            );
            $out[] = ['display_name' => 'Wartość energetyczna', 'value' => $ener];
        }
        $map = [
            'Tłuszcz' => 'fat',
            'w tym kwasy tłuszczowe nasycone' => 'sat',
            'Węglowodany' => 'carb',
            'w tym cukry' => 'sugars',
            'w tym poliole' => 'polyols',
            'Błonnik' => 'fiber',
            'Białko' => 'protein',
            'Sól' => 'salt',
        ];
        foreach ($map as $label => $key) {
            if ($found[$key] !== null) {
                $val = str_replace('.', ',', (string)$found[$key]);
                $val = $this->ensureUnit($val, ' g');
                $out[] = ['display_name' => $label, 'value' => trim($val)];
            }
        }

        if (empty($out)) {
            \GeminiContent\Service\GeminiContentLogger::debug('parseNutritionalFacts: brak dopasowań po regexach – prawdopodobnie nietypowy zapis jednostek.');
        } else {
            \GeminiContent\Service\GeminiContentLogger::debug('parseNutritionalFacts: sparsowane rekordy: ' . json_encode($out, JSON_UNESCAPED_UNICODE));
        }
        return $out;
    }
}