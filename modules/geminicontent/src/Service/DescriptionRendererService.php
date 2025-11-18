<?php
declare(strict_types=1);

namespace GeminiContent\Service;

use Configuration;
use Smarty;
use Context;
use Tools;

class DescriptionRendererService
{
    private Smarty $smarty;

    public function __construct()
    {
        $this->smarty = Context::getContext()->smarty;
        $this->smarty->addTemplateDir(_PS_MODULE_DIR_ . 'geminicontent/src/Template/');
    }

    public function render(?string $newCreativeContent, ?string $formattedOldContent, ?string $ingredientsData, ?string $nutrientsData, string $productName, string $originalDescription): string
    {
        \GeminiContent\Service\GeminiContentLogger::debug('DescriptionRendererService: Rozpoczynam renderowanie.');

        $newContentHtml = (string)$newCreativeContent;
        $formattedOldContentHtml = (string)$formattedOldContent;
        
        $extractedTablesHtml = $this->renderExtractedTables($ingredientsData, $nutrientsData, $productName, $originalDescription);

        $mergeTemplate = Configuration::get('GEMINI_MERGE_TEMPLATE');
        if (empty($mergeTemplate)) {
            $mergeTemplate = $this->getDefaultMergeTemplate();
        }

        $replacements = [
            '{NEW_CONTENT_PLACEHOLDER}' => $newContentHtml,
            '{ORIGINAL_OLD_CONTENT_PLACEHOLDER}' => $formattedOldContentHtml,
            '{EXTRACTED_TABLES_PLACEHOLDER}' => $extractedTablesHtml,
        ];

        $finalHtml = strtr($mergeTemplate, $replacements);

        \GeminiContent\Service\GeminiContentLogger::debug('DescriptionRendererService: Zakończono renderowanie.');
        return $finalHtml;
    }

    private function renderExtractedTables(?string $ingredientsData, ?string $nutrientsData, string $productName, string $originalDescription): string
    {
        $outputHtml = '';

        // Przetwarzanie składników z logiką BIO
        if (!empty(trim((string)$ingredientsData))) {
            $cleanIngredientsList = array_filter(array_map('trim', explode('|', (string)$ingredientsData)));
            $ingredientsListFormatted = [];
            
            // Oczyszczamy oryginalny opis z HTML i ujednolicamy spacje oraz wielkość liter
            $plainOriginal = preg_replace('/\s+/', ' ', strip_tags((string)$originalDescription));
            $lowerOriginal = mb_strtolower($plainOriginal, 'UTF-8');

            // 1) Wykrywanie legendy BIO w różnych wariantach za pomocą wyrażeń regularnych
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
            
            foreach ($cleanIngredientsList as $cleanIngredientName) {
                $isBio = false;

                // 2) Dopasowanie składnika z gwiazdką – bez HTML, bez rozróżniania wielkości liter
                if ($hasGlobalLegend) {
                    $ingredientPattern = '/\b' . preg_quote(mb_strtolower(trim($cleanIngredientName), 'UTF-8'), '/') . '\b\s*\*/iu';
                    if (preg_match($ingredientPattern, $lowerOriginal)) {
                        $isBio = true;
                    }
                }

                // 3) Reguła produktu jednoskładnikowego: BIO w nazwie
                if (!$isBio && count($cleanIngredientsList) === 1 && stripos($productName, 'BIO') !== false) {
                    $isBio = true;
                }

                // Reguła dodatkowa: słowo "ekologiczny" w nazwie składnika
                if (!$isBio && stripos($cleanIngredientName, 'ekologiczny') !== false) {
                     $isBio = true;
                }

                $ingredientsListFormatted[] = [
                    'name' => $cleanIngredientName,
                    'is_bio' => $isBio,
                ];
            }

            if (!empty($ingredientsListFormatted)) {
                $this->smarty->assign('ingredients', $ingredientsListFormatted);
                $outputHtml .= $this->smarty->fetch('IngredientsList.tpl');
            }
        }

        // Przetwarzanie wartości odżywczych (bez zmian)
        if (!empty(trim((string)$nutrientsData))) {
            $nutrientsListRaw = array_filter(array_map('trim', explode('|', (string)$nutrientsData)));
            $nutrientsListFormatted = [];
            foreach ($nutrientsListRaw as $item) {
                $parts = explode(':', $item, 2);
                if (count($parts) === 2) {
                    $nutrientsListFormatted[] = ['display_name' => trim($parts[0]), 'value' => trim($parts[1])];
                }
            }
            if (!empty($nutrientsListFormatted)) {
                $this->smarty->assign('nutritional_facts', $nutrientsListFormatted);
                $outputHtml .= $this->smarty->fetch('NutritionalFactsTable.tpl');
            }
        }

        return $outputHtml;
    }

    private function getDefaultMergeTemplate(): string
    {
        return '
<div class="gemini-description-wrapper">
    <div class="gemini-new-description">
        {NEW_CONTENT_PLACEHOLDER}
    </div>
    
    <div class="gemini-extracted-tables">
        {EXTRACTED_TABLES_PLACEHOLDER}
    </div>

    <hr class="gemini-separator">

    <div class="gemini-product-old-details">
        <h3>Oryginalne informacje o produkcie:</h3>
        <div class="original-description-text">
            {ORIGINAL_OLD_CONTENT_PLACEHOLDER}
        </div>
    </div>
</div>';
    }
}