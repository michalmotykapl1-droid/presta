<?php
// /modules/geminicontent/src/Service/CsvIngredientNutritionEnricher.php

/**
 * GeminiContent - CsvIngredientNutritionEnricher
 * PrestaShop 8.2.x compatible
 */
namespace GeminiContent\Service;

if (!defined('_PS_VERSION_')) {
    exit;
}

class CsvIngredientNutritionEnricher
{
    /**
     * Główna metoda wzbogacająca opis.
     * @param string $description Oryginalny, stary opis produktu.
     * @param string|null $ingredientsSrc Surowy tekst składników.
     * @param string|null $nutritionSrc Surowy tekst wartości odżywczych.
     * @param string $productName Nazwa produktu (do logiki BIO).
     * @return string
     */
    public function enrichDescriptionWithTables(string $description, ?string $ingredientsSrc, ?string $nutritionSrc, string $productName): string
    {
        $prefix = '';
        $ingredientsSrc = trim((string) $ingredientsSrc);
        $nutritionSrc = trim((string) $nutritionSrc);

        if ($ingredientsSrc !== '') {
            // Przekazujemy oryginalny opis i nazwę produktu do metody renderującej składniki
            $prefix .= $this->renderIngredientsTable($ingredientsSrc, $description, $productName);
        }
        if ($nutritionSrc !== '') {
            $prefix .= $this->renderNutritionTable($nutritionSrc);
        }
        
        return $prefix !== '' ? $prefix . "<hr>" . $description : $description;
    }

    /**
     * Renderuje tabelę składników z logiką BIO.
     * @param string $text Surowy tekst składników (z `ingredients_src`).
     * @param string $originalDescription Oryginalny opis produktu do analizy pod kątem legendy BIO.
     * @param string $productName Nazwa produktu do analizy.
     * @return string
     */
    protected function renderIngredientsTable(string $text, string $originalDescription, string $productName): string
    {
        $cleanIngredientsList = $this->smartSplit($text);
        
        // --- NOWA LOGIKA WYKRYWANIA BIO ---
        $plainOriginal = preg_replace('/\s+/', ' ', strip_tags((string)$originalDescription));
        $lowerOriginal = mb_strtolower($plainOriginal, 'UTF-8');

        $legendPatterns = [
            '/\*\s*(certyfikowan\w*\s+)?(składnik\w*|surowce)\s+(bio|ekologiczn\w+)/iu',
            '/\*\s*.*(z\s*(rolnictwa|upraw)\s*ekologiczn\w+)/iu',
            '/gwiazd\w+.*oznacz\w+.*(bio|ekologiczn\w+)/iu',
            '/oznaczon\w*\s*\*.*(bio|ekologiczn\w+)/iu',
        ];
        $hasGlobalLegend = false;
        foreach ($legendPatterns as $p) {
            if (preg_match($p, $lowerOriginal)) {
                $hasGlobalLegend = true;
                break;
            }
        }

        $rows = [];
        foreach ($cleanIngredientsList as $ingredientName) {
            $ingredientName = trim($ingredientName);
            if ($ingredientName === '') continue;

            $isBio = false;

            if ($hasGlobalLegend) {
                $ingredientPattern = '/\b' . preg_quote(mb_strtolower(trim($ingredientName), 'UTF-8'), '/') . '\b\s*\*/iu';
                if (preg_match($ingredientPattern, $lowerOriginal)) {
                    $isBio = true;
                }
            }
            if (!$isBio && count($cleanIngredientsList) === 1 && stripos($productName, 'BIO') !== false) {
                $isBio = true;
            }
            if (!$isBio && stripos($ingredientName, 'ekologiczny') !== false) {
                 $isBio = true;
            }

            // Budowanie wiersza tabeli
            $bioCellContent = '';
            if ($isBio) {
                $bioCellContent = '<i class="icon-check" style="color: green;"></i> <strong>BIO</strong>';
            }
            $rows[] = '<tr><td>' . $this->e($ingredientName) . '</td><td style="text-align:center;">' . $bioCellContent . '</td></tr>';
        }
        
        if (empty($rows)) {
            return '';
        }

        $body = implode("\n", $rows);
        
        // Zwraca pełną tabelę HTML, na wzór szablonu IngredientsList.tpl
        return <<<HTML
<section class="gc-nutri-block gc-ingredients">
  <h3>Skład</h3>
  <div class="table-responsive">
    <table class="table table-bordered table-striped">
      <thead>
        <tr>
          <th>Składnik</th>
          <th style="width: 120px; text-align: center;">Certyfikat BIO</th>
        </tr>
      </thead>
      <tbody>{$body}</tbody>
    </table>
  </div>
</section>
HTML;
    }

    protected function renderNutritionTable(string $text): string
    {
        $pairs = $this->smartPairs($text);
        $rows = [];
        if (empty($pairs)) {
            $rows[] = '<tr><td colspan="2">' . $this->e($text) . '</td></tr>';
        } else {
            foreach ($pairs as $k => $v) {
                $rows[] = '<tr><th style="width:55%">' . $this->e($k) . '</th><td>' . $this->e($v) . '</td></tr>';
            }
        }
        $body = implode("\n", $rows);
        return <<<HTML
<section class="gc-nutri-block gc-nutrition">
  <h3>Wartości odżywcze</h3>
  <div class="table-responsive">
    <table class="table table-bordered table-striped">
      <tbody>{$body}</tbody>
    </table>
  </div>
</section>
HTML;
    }

    protected function smartSplit(string $text): array
    {
        $text = trim($text);
        if ($text === '') return [];
        $text = preg_replace('/\s{2,}/', ' ', $text);
        $parts = preg_split('/\s*\|\s*|\r\n|\r|\n|;|•|\x{2022}/u', $text);
        $parts = array_map('trim', $parts);
        $parts = array_filter($parts, static function ($v) { return $v !== ''; });
        if (count($parts) >= 8) return [ $text ];
        return array_values($parts);
    }

    protected function smartPairs(string $text): array
    {
        $text = trim($text);
        if ($text === '') return [];
        $chunks = preg_split('/\s*\|\s*|\r\n|\r|\n|;|•|\x{2022}/u', $text);
        $pairs = [];
        foreach ($chunks as $chunk) {
            $chunk = trim($chunk);
            if ($chunk === '') continue;
            if (strpos($chunk, ':') !== false) {
                [$k, $v] = array_map('trim', explode(':', $chunk, 2));
                if ($k !== '' && $v !== '') { $pairs[$k] = $v; continue; }
            }
            if (preg_match('/^(.+?)\s+([0-9]+(?:[\,\.][0-9]+)?\s*(?:kcal|kJ|g|mg|µg|mcg|%|(?:g\/100g)|(?:mg\/100g)|(?:kcal\/100g)|[a-zA-Z\/%]+))$/u', $chunk, $m)) {
                $pairs[trim($m[1])] = trim($m[2]); continue;
            }
            $pairs[$chunk] = '';
        }
        return $pairs;
    }
    protected function e(string $html): string
    {
        return htmlspecialchars($html, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}