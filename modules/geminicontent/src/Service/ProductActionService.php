<?php
declare(strict_types=1);

namespace GeminiContent\Service;

use Product;
use Tag;
use Validate;
use Db;
use Configuration;
use GeminiContent\Service\GeminiClientService;
use GeminiContent\Service\PromptBuilder;
use Context;
use Exception;

class ProductActionService
{
    private GeminiClientService $geminiClient;
    private Context $context;

    public function __construct()
    {
        $this->geminiClient = new GeminiClientService();
        $this->context = Context::getContext();
    }
    
    public function saveDescription(int $idProduct, string $productName, string $originalDescription, ?string $newCreativeText, ?string $formattedOldDescription, ?string $ingredientsData, ?string $nutrientsData, ?array $seoData): bool
    {
        \GeminiContent\Service\GeminiContentLogger::debug('saveDescription: Rozpoczęto dla produktu ID: ' . $idProduct);

        $product = new Product($idProduct, false, $this->context->language->id);
        if (!Validate::isLoadedObject($product)) {
            \GeminiContent\Service\GeminiContentLogger::error('saveDescription: Nie można załadować produktu ID: ' . $idProduct);
            throw new Exception('Nie można załadować produktu do zapisu.');
        }

        $log_description = false;
        $log_seo = false;

        $renderer = new DescriptionRendererService();
        
        $finalDescription = $renderer->render(
            $newCreativeText,
            $formattedOldDescription,
            $ingredientsData,
            $nutrientsData,
            $productName,
            $originalDescription
        );
        
        \GeminiContent\Service\GeminiContentLogger::debug('saveDescription: Finalny opis HTML po renderowaniu: ' . substr($finalDescription, 0, 500) . '...');

        if (!is_array($product->description)) {
            $product->description = [];
        }
        $product->description[$this->context->language->id] = $finalDescription;
        $log_description = true;
        
        if (!empty($seoData) && is_array($seoData)) {
            \GeminiContent\Service\GeminiContentLogger::debug('saveDescription: Przetwarzam dane SEO: ' . json_encode($seoData));
            if (isset($seoData['meta_title'])) { if (!is_array($product->meta_title)) $product->meta_title = []; $product->meta_title[$this->context->language->id] = $seoData['meta_title']; }
            if (isset($seoData['meta_description'])) { if (!is_array($product->meta_description)) $product->meta_description = []; $product->meta_description[$this->context->language->id] = $seoData['meta_description']; }
            if (isset($seoData['link_rewrite'])) { if (!is_array($product->link_rewrite)) $product->link_rewrite = []; $product->link_rewrite[$this->context->language->id] = $seoData['link_rewrite']; }
            if (!empty($seoData['tags']) && is_array($seoData['tags'])) {
                Tag::deleteTagsForProduct($idProduct);
                Tag::addTags($this->context->language->id, $idProduct, $seoData['tags']);
                \GeminiContent\Service\GeminiContentLogger::info('saveDescription: Tagi zaktualizowane.');
            }
            $log_seo = true;
        }

        if ($product->update()) {
            $this->logChanges($idProduct, $log_description, $log_seo);
            \GeminiContent\Service\GeminiContentLogger::info('saveDescription: Produkt ID ' . $idProduct . ' zaktualizowany pomyślnie.');
            return true;
        } else {
            \GeminiContent\Service\GeminiContentLogger::error('saveDescription: Błąd aktualizacji produktu ID ' . $idProduct . ' w bazie danych.');
        }

        return false;
    }

    private function logChanges(int $idProduct, bool $log_description, bool $log_seo): void
    {
        $insert_fields = ['id_product'];
        $insert_values = [(int)$idProduct];
        $update_clauses = [];

        if ($log_description) {
            $insert_fields = array_merge($insert_fields, ['description_generated', 'description_date_add']);
            $insert_values = array_merge($insert_values, [1, 'NOW()']);
            $update_clauses = array_merge($update_clauses, ['description_generated = 1', 'description_date_add = NOW()']);
        }
        if ($log_seo) {
            $insert_fields = array_merge($insert_fields, ['seo_generated', 'seo_date_add']);
            $insert_values = array_merge($insert_values, [1, 'NOW()']);
            $update_clauses = array_merge($update_clauses, ['seo_generated = 1', 'seo_date_add = NOW()']);
        }

        if (!empty($update_clauses)) {
            $sql = 'INSERT INTO `' . _DB_PREFIX_ . 'geminicontent_log` (' . implode(', ', $insert_fields) . ')
                    VALUES (' . implode(', ', $insert_values) . ')
                    ON DUPLICATE KEY UPDATE ' . implode(', ', $update_clauses) . ', last_update = NOW()';
            Db::getInstance()->execute($sql);
            \GeminiContent\Service\GeminiContentLogger::info('logChanges: Zalogowano zmiany dla produktu ID: ' . $idProduct);
        }
    }
}