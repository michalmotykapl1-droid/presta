<?php
// /modules/geminicontent/controllers/admin/AdminGeminiContentManagerController.php

use GeminiContent\Service\GeminiClientService;
use GeminiContent\Service\PromptBuilder;
use GeminiContent\Repository\GeminiProductRepository;
use GeminiContent\Service\ProductActionService;
use GeminiContent\Service\CsvHandlerService;
use GeminiContent\Service\CsvIngredientNutritionEnricher;

class AdminGeminiContentManagerController extends ModuleAdminController
{
    private GeminiProductRepository $productRepo;
    private ProductActionService $productActionService;
    private CsvHandlerService $csvHandlerService;

    public function __construct()
    {
        $this->bootstrap = true;
        parent::__construct();
        $this->productRepo = new GeminiProductRepository(Db::getInstance());
        $this->productActionService = new ProductActionService();
        $this->csvHandlerService = new CsvHandlerService();
    }

    public function initContent()
    {
        parent::initContent();

        // Logika dla głównego importu AI
        if (isset($this->context->cookie->gemini_preview_path) && file_exists($this->context->cookie->gemini_preview_path)) {
            $previewJson = file_get_contents($this->context->cookie->gemini_preview_path);
            $previewData = json_decode($previewJson, true);

            if (json_last_error() === JSON_ERROR_NONE && !empty($previewData)) {
                $this->context->smarty->assign([
                    'import_preview_data' => $previewData,
                    'current_controller_url' => $this->context->link->getAdminLink('AdminGeminiContentManager'),
                    'token' => $this->token,
                ]);
                $this->setTemplate('import_preview.tpl');
                return;
            }
        }
        
        // ZMIANA: Logika dla podglądu tabel Skład/Wartości na osobnej stronie
        if (isset($this->context->cookie->gc_ingr_nutr_preview_path) && file_exists($this->context->cookie->gc_ingr_nutr_preview_path)) {
            $previewJson = file_get_contents($this->context->cookie->gc_ingr_nutr_preview_path);
            $previewData = json_decode($previewJson, true);

            if (json_last_error() === JSON_ERROR_NONE && !empty($previewData)) {
                $this->context->smarty->assign([
                    'gc_ingr_nutr_preview' => $previewData,
                    'current_controller_url' => $this->context->link->getAdminLink('AdminGeminiContentManager'),
                    'token' => $this->token,
                ]);
                // Używamy nowego, dedykowanego szablonu
                $this->setTemplate('ingr_nutr_preview.tpl');
                return;
            }
        }
        
        // Jeśli żaden podgląd nie jest aktywny, wyświetl główną stronę konfiguracji
        $this->displayConfigureView();
    }

    public function postProcess()
    {
        if (Tools::isSubmit('submitGeminiSettings')) {
            $this->processSaveSettings();
        }
        if (Tools::isSubmit('submitExportCsv')) {
            $this->processExportCsv();
        }
        if (Tools::isSubmit('submitProcessCsvWithAI')) {
            $this->processFullCsvWithAI();
        }
        if (Tools::isSubmit('downloadProcessedCsv')) {
            $this->processDownloadProcessedCsv();
        }
        if (Tools::isSubmit('submitImportCsv')) {
            $this->processImportCsv();
        }
        if (Tools::isSubmit('submitConfirmImport')) {
            $this->processConfirmImport();
        }
        if (Tools::getValue('action') == 'cancelImport') {
            $this->processCancelImport();
        }
        
        // ZMIANA: Obsługa nowego, dwuetapowego procesu
        if (Tools::isSubmit('submitGcIngrNutrPreview')) {
            $this->processGcIngrNutrPreview();
        }
        if (Tools::isSubmit('submitConfirmGcIngrNutr')) {
            $this->processConfirmGcIngrNutr();
        }
        if (Tools::isSubmit('cancelGcIngrNutr')) {
            $this->processCancelGcIngrNutr();
        }


        parent::postProcess();
    }
    
    // --- Metody AJAX ---
    
    private function assertValidToken()
    {
        $token = Tools::getValue('token');
        if (!$token || $token !== Tools::getAdminTokenLite('AdminGeminiContentManager')) {
            $this->ajaxDie(json_encode(['success' => false, 'message' => $this->l('Invalid security token.')]));
        }
    }

    public function ajaxProcessGenerateDescription()
    {
        $this->assertValidToken();
        try {
            $idProduct = (int) Tools::getValue('id_product');
            $result = $this->productActionService->generateDescription($idProduct);
            $this->ajaxDie(json_encode(['success' => true, 'data' => $result]));
        } catch (Exception $e) {
            $this->ajaxDie(json_encode(['success' => false, 'message' => $e->getMessage()]));
        }
    }

    public function ajaxProcessGenerateSeo()
    {
        $this->assertValidToken();
        try {
            $idProduct = (int) Tools::getValue('id_product');
            $result = $this->productActionService->generateSeo($idProduct);
            $this->ajaxDie(json_encode(['success' => true, 'data' => $result]));
        } catch (Exception $e) {
            $this->ajaxDie(json_encode(['success' => false, 'message' => $e->getMessage()]));
        }
    }

    public function ajaxProcessGenerateAll()
    {
        $this->assertValidToken();
        try {
            $idProduct = (int) Tools::getValue('id_product');
            $result = $this->productActionService->generateAll($idProduct);
            $this->ajaxDie(json_encode(['success' => true, 'data' => $result]));
        } catch (Exception $e) {
            $this->ajaxDie(json_encode(['success' => false, 'message' => $e->getMessage()]));
        }
    }

    public function ajaxProcessSaveDescription()
    {
        $this->assertValidToken();
        try {
            $id = (int) Tools::getValue('id_product');
            $newRawText = Tools::getValue('description', false);
            $seoData = Tools::getValue('seo');
            
            if (!$id) {
                throw new Exception($this->l('Brak ID produktu.'));
            }
            $product = new Product($id, false, $this->context->language->id);
            if (!Validate::isLoadedObject($product)) {
                 throw new Exception($this->l('Nie można załadować produktu.'));
            }
            $product->description[$this->context->language->id] = $newRawText;
            $success = $product->update();

            if ($success) {
                $this->ajaxDie(json_encode(['success' => true, 'message' => $this->l('Zmiany zostały zapisane.')]));
            } else {
                throw new Exception($this->l('Błąd zapisu produktu.'));
            }
        } catch (Exception $e) {
            $this->ajaxDie(json_encode(['success' => false, 'message' => $e->getMessage()]));
        }
    }

    public function ajaxProcessSaveCsvPrompt()
    {
        $this->assertValidToken();
        if (!Tools::getValue('token') || Tools::getValue('token') !== Tools::getAdminTokenLite('AdminGeminiContentManager')) {
            $this->ajaxDie(json_encode(['success' => false, 'message' => $this->l('Invalid security token.')]));
        }

        try {
            $prompt = Tools::getValue('prompt');
            if ($prompt === null) {
                throw new Exception($this->l('Prompt value is missing.'));
            }
            Configuration::updateValue('GEMINI_PROMPT_CSV_PROCESS', $prompt, true); 
            $this->ajaxDie(json_encode(['success' => true, 'message' => $this->l('CSV prompt saved successfully.')]));
        } catch (Exception $e) {
            $this->ajaxDie(json_encode(['success' => false, 'message' => $this->l('Error saving CSV prompt: ') . $e->getMessage()]));
        }
    }

    public function ajaxProcessResetCsvPrompt()
    {
        $this->assertValidToken();
        try {
            if (!class_exists('GeminiContent\Service\PromptBuilder')) {
                require_once _PS_MODULE_DIR_ . 'geminicontent/src/Service/PromptBuilder.php';
            }
            $defaultPrompt = PromptBuilder::buildDefaultCsvProcessPrompt();
            Configuration::updateValue('GEMINI_PROMPT_CSV_PROCESS', $defaultPrompt, true);
            $this->ajaxDie(json_encode(['success' => true, 'message' => $this->l('CSV prompt reset to default.'), 'prompt' => $defaultPrompt]));
        } catch (Exception $e) {
            $this->ajaxDie(json_encode(['success' => false, 'message' => $e->getMessage()]));
        }
    }
    
    public function ajaxProcessPreviewProductChanges()
    {
        $this->assertValidToken();
        if (!Tools::getValue('token') || Tools::getValue('token') !== Tools::getAdminTokenLite('AdminGeminiContentManager')) {
            $this->ajaxDie(json_encode(['success' => false, 'message' => $this->l('Invalid security token.')]));
            $this->context->smarty->assign([
                'preview_errors' => isset($productCsvData['_errors']) ? $productCsvData['_errors'] : [],
                'preview_has_errors' => !empty($productCsvData['_errors']),
            ]);
        
        }

        try {
            $idProduct = (int) Tools::getValue('id_product');
            $previewFilePath = $this->context->cookie->gemini_preview_path ?? null;

            if (!$idProduct || empty($previewFilePath) || !file_exists($previewFilePath)) {
                throw new Exception($this->l('Brak danych do wygenerowania podglądu.'));
            }
            
            $importJson = file_get_contents($previewFilePath);
            $importData = json_decode($importJson, true);
            $productCsvData = null;
            foreach ($importData as $row) {
                if (isset($row['id_product']) && (int)$row['id_product'] === $idProduct) {
                    $productCsvData = $row;
                    break;
                }
            }

            if ($productCsvData === null) {
                throw new Exception($this->l('Nie znaleziono danych dla tego produktu w pliku importu.'));
            }

            $product = new Product($idProduct, false, $this->context->language->id, $this->context->shop->id);
            if (!Validate::isLoadedObject($product)) {
                throw new Exception($this->l('Nie można załadować produktu.'));
            }
            
            $current_product_data = [
                'name' => $product->name,
                'description' => $product->description,
                'meta_title' => $product->meta_title,
                'meta_description' => $product->meta_description,
                'link_rewrite' => $product->link_rewrite,
                'tags' => implode(', ', Tag::getProductTags($idProduct)[$this->context->language->id] ?? []),
            ];
            
            $renderer = new \GeminiContent\Service\DescriptionRendererService();
            
            $finalDescription = $renderer->render(
                $productCsvData['description_ai'] ?? null,
                $productCsvData['description_old_ai'] ?? null,
                $productCsvData['sklad_ai'] ?? null,
                $productCsvData['wartosci_ai'] ?? null,
                $product->name,
                $productCsvData['description'] ?? ''
            );

            $preview_product_data = [
                'name' => $product->name,
                'description' => $finalDescription,
                'meta_title' => !empty($productCsvData['meta_title_ai']) ? $productCsvData['meta_title_ai'] : $current_product_data['meta_title'],
                'meta_description' => !empty($productCsvData['meta_description_ai']) ? $productCsvData['meta_description_ai'] : $current_product_data['meta_description'],
                'link_rewrite' => !empty($productCsvData['link_rewrite_ai']) ? $productCsvData['link_rewrite_ai'] : $current_product_data['link_rewrite'],
                'tags' => !empty($productCsvData['tags_ai']) ? $productCsvData['tags_ai'] : $current_product_data['tags'],
            ];

            $this->context->smarty->assign([
                'current_product_data' => $current_product_data,
                'preview_product_data' => $preview_product_data,
                'preview_errors' => isset($productCsvData['_errors']) ? $productCsvData['_errors'] : [],
                'preview_has_errors' => !empty($productCsvData['_errors']),
            ]);

            $modalContent = $this->context->smarty->fetch(_PS_MODULE_DIR_ . 'geminicontent/views/templates/admin/product_preview_modal_content.tpl');

            $this->ajaxDie(json_encode(['success' => true, 'html' => $modalContent]));

        } catch (Exception $e) {
            $this->ajaxDie(json_encode(['success' => false, 'message' => $e->getMessage()]));
        }
    }
    
    // --- Metody procesów ---

    protected function processExportCsv()
    {
        try {
            $options = [
                'export_range'  => Tools::getValue('export_range'),
                'export_limit'  => Tools::getValue('export_limit'),
                'export_status' => Tools::getValue('export_status'),
                'sku_prefixes'  => Tools::getValue('sku_prefixes'),
            ];
            $this->csvHandlerService->exportProducts($options);
        } catch (Exception $e) {
            $this->errors[] = $e->getMessage();
        }
    }
    
    protected function processFullCsvWithAI()
    {
        if (!isset($_FILES['import_csv_file']) || $_FILES['import_csv_file']['error'] != UPLOAD_ERR_OK) {
            $this->errors[] = $this->l('Błąd podczas przesyłania pliku.');
            return;
        }

        $csvContent = file_get_contents($_FILES['import_csv_file']['tmp_name']);
        if (empty($csvContent)) {
            $this->errors[] = $this->l('Przesłany plik jest pusty.');
            return;
        }

        $prompt = Tools::getValue('gemini_prompt_csv_process');

        try {
            $tempFilePath = $this->csvHandlerService->processCsvWithAI($csvContent, $prompt);
            
            $this->context->cookie->gemini_processed_csv_path = $tempFilePath;
            $this->context->cookie->write();

            Tools::redirectAdmin($this->context->link->getAdminLink('AdminGeminiContentManager') . '&downloadProcessedCsv=1');
        } catch (Exception $e) {
            $this->errors[] = $this->l('Błąd podczas przetwarzania pliku przez AI: ') . $e->getMessage();
        }
    }

    protected function processDownloadProcessedCsv()
    {
        $tempFilePath = $this->context->cookie->gemini_processed_csv_path ?? null;

        if (empty($tempFilePath) || !file_exists($tempFilePath) || strpos($tempFilePath, sys_get_temp_dir()) !== 0) {
            $this->errors[] = $this->l('Plik tymczasowy nie został znaleziony lub dostęp został odrzucony.');
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminGeminiContentManager'));
            return;
        }

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="gemini-export-processed-' . date('Y-m-d') . '.csv"');
        header('Content-Length: ' . filesize($tempFilePath));
        header('Pragma: no-cache');
        header('Expires: 0');

        ob_end_clean();
        readfile($tempFilePath);

        unlink($tempFilePath);
        unset($this->context->cookie->gemini_processed_csv_path);

        exit();
    }
    
    protected function processImportCsv()
    {
        if (!isset($_FILES['import_csv_file']) || $_FILES['import_csv_file']['error'] != UPLOAD_ERR_OK) {
            $this->errors[] = $this->l('Błąd podczas przesyłania pliku.');
            return;
        }
        
        try {
            $tempPreviewPath = $this->csvHandlerService->prepareImportPreview($_FILES['import_csv_file']['tmp_name']);
            
            if (isset($this->context->cookie->gemini_preview_path)) {
                unset($this->context->cookie->gemini_preview_path);
            }
            
            $this->context->cookie->gemini_preview_path = $tempPreviewPath;
            $this->context->cookie->write();
            
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminGeminiContentManager'));
        } catch (Exception $e) {
            $this->errors[] = $e->getMessage();
        }
    }
    
    protected function processConfirmImport()
    {
        $previewFilePath = $this->context->cookie->gemini_preview_path ?? null;
        $selectedProductIds = Tools::getValue('products_to_import', []);

        if (empty($previewFilePath)) {
            $this->errors[] = $this->l('Brak danych do importu. Sesja mogła wygasnąć.');
            return;
        }
        
        if (empty($selectedProductIds)) {
            $this->warnings[] = $this->l('Nie zaznaczono żadnych produktów do importu.');
            unset($this->context->cookie->gemini_preview_path);
            if (file_exists($previewFilePath)) {
                unlink($previewFilePath);
            }
            return;
        }
        
        try {
            $result = $this->csvHandlerService->confirmImport($previewFilePath, $selectedProductIds);
            
            unset($this->context->cookie->gemini_preview_path);

            if ($result['updated'] > 0) {
                $this->confirmations[] = sprintf($this->l('Pomyślnie zaktualizowano %d produktów.'), $result['updated']);
            } else {
                if ($result['errors'] == 0) {
                    $this->warnings[] = $this->l('Żaden produkt nie został zaktualizowany.');
                }
            }

            if ($result['errors'] > 0) {
                $this->errors[] = sprintf($this->l('Napotkano %d błędów podczas importu.'), $result['errors']);
            }

        } catch (Exception $e) {
            $this->errors[] = $e->getMessage();
        }
    }

    protected function processCancelImport()
    {
        $previewFilePath = $this->context->cookie->gemini_preview_path ?? null;
        if ($previewFilePath && file_exists($previewFilePath)) {
            unlink($previewFilePath);
        }
        unset($this->context->cookie->gemini_preview_path);
        
        Tools::redirectAdmin($this->context->link->getAdminLink('AdminGeminiContentManager'));
    }

    // =========================================================================
    // NOWA LOGIKA: Funkcje do obsługi "Dodaj tabelę..." z przekierowaniem
    // =========================================================================

    protected function processGcIngrNutrPreview()
    {
        if (!isset($_FILES['gc_ingr_nutr_csv']) || (int)$_FILES['gc_ingr_nutr_csv']['error'] !== UPLOAD_ERR_OK) {
            $this->errors[] = $this->l('Nie wybrano pliku CSV lub wystąpił błąd podczas przesyłania.');
            return;
        }

        $tmpPath = $_FILES['gc_ingr_nutr_csv']['tmp_name'];
        $previewData = [];
        $header = [];
        $rows = [];

        if (($handle = @fopen($tmpPath, 'r')) !== false) {
            $rawHeader = fgets($handle);
            $rawHeader = preg_replace('/^\x{EF}\x{BB}\x{BF}/', '', $rawHeader);
            $header = array_map('trim', str_getcsv($rawHeader, ';'));

            while (($row = fgetcsv($handle, 0, ';')) !== false) {
                if (count($row) === 1 && $row[0] === null) continue;
                if (count($header) == count($row)) {
                    $rows[] = array_combine($header, $row);
                }
            }
            fclose($handle);
        } else {
            $this->errors[] = $this->l('Nie można otworzyć pliku CSV.');
        }

        if (!empty($this->errors)) return;
        
        foreach (['id_product', 'name', 'description', 'ingredients_src', 'nutrition_src'] as $col) {
            if (!in_array($col, $header)) {
                $this->errors[] = sprintf($this->l('Brak wymaganej kolumny w CSV: %s'), $col);
            }
        }
        if (!empty($this->errors)) return;

        $enricher = new CsvIngredientNutritionEnricher();
        foreach ($rows as $row) {
            $oldDesc = (string)($row['description'] ?? '');
            $ing = (string)($row['ingredients_src'] ?? '');
            $nut = (string)($row['nutrition_src'] ?? '');
            $productName = (string)($row['name'] ?? ''); // Pobranie nazwy produktu
            
            // KLUCZOWA ZMIANA: Przekazanie nazwy produktu do serwisu
            $newDesc = $enricher->enrichDescriptionWithTables($oldDesc, $ing, $nut, $productName);
            
            $id_product = (int)($row['id_product'] ?? 0);
            $edit_link = '';
            if ($id_product > 0) {
                $edit_link = $this->context->link->getAdminLink('AdminProducts', true, ['id_product' => $id_product, 'updateproduct' => '1']);
            }

            $previewData[] = [
                'id_product' => $id_product,
                'reference'  => $row['reference'] ?? '',
                'name'       => $row['name'] ?? 'Brak nazwy',
                'old_desc'   => $oldDesc,
                'new_desc'   => $newDesc,
                'edit_link'  => $edit_link,
            ];
        }

        if (empty($previewData)) {
            $this->warnings[] = $this->l('Nie znaleziono danych do przetworzenia w pliku CSV.');
            return;
        }

        $tempPreviewPath = tempnam(sys_get_temp_dir(), 'gemini_ingr_nutr_');
        file_put_contents($tempPreviewPath, json_encode($previewData));
        $this->context->cookie->gc_ingr_nutr_preview_path = $tempPreviewPath;

        // Przekierowanie na tę samą stronę, aby initContent() mogło zadziałać
        Tools::redirectAdmin($this->context->link->getAdminLink('AdminGeminiContentManager'));
    }

    protected function processConfirmGcIngrNutr()
    {
        $previewPath = $this->context->cookie->gc_ingr_nutr_preview_path ?? null;
        $selectedIds = Tools::getValue('products_to_update', []);

        if (!$previewPath || !file_exists($previewPath)) {
            $this->errors[] = $this->l('Sesja wygasła lub nie znaleziono danych do zapisu. Proszę wgrać plik ponownie.');
            return;
        }
        
        if (empty($selectedIds)) {
            $this->warnings[] = $this->l('Nie zaznaczono żadnych produktów do aktualizacji.');
            unlink($previewPath);
            unset($this->context->cookie->gc_ingr_nutr_preview_path);
            return;
        }
        
        $previewData = json_decode(file_get_contents($previewPath), true);
        $updatedCount = 0;
        $errorCount = 0;

        foreach ($previewData as $item) {
            $id_product = (int)$item['id_product'];
            if (in_array($id_product, $selectedIds)) {
                try {
                    $product = new Product($id_product, false, $this->context->language->id);
                    if (Validate::isLoadedObject($product)) {
                        $product->description[$this->context->language->id] = $item['new_desc'];
                        if ($product->update()) {
                            $updatedCount++;
                        } else {
                            $errorCount++;
                        }
                    } else {
                        $errorCount++;
                    }
                } catch (Exception $e) {
                    $errorCount++;
                }
            }
        }

        if ($updatedCount > 0) {
            $this->confirmations[] = sprintf($this->l('Pomyślnie zaktualizowano opisy dla %d produktów.'), $updatedCount);
        }
        if ($errorCount > 0) {
            $this->errors[] = sprintf($this->l('Nie udało się zaktualizować %d produktów.'), $errorCount);
        }
        if ($updatedCount == 0 && $errorCount == 0) {
            $this->warnings[] = $this->l('Żaden produkt nie został zaktualizowany.');
        }

        unlink($previewPath);
        unset($this->context->cookie->gc_ingr_nutr_preview_path);
    }
    
    protected function processCancelGcIngrNutr()
    {
        $previewPath = $this->context->cookie->gc_ingr_nutr_preview_path ?? null;
        if ($previewPath && file_exists($previewPath)) {
            unlink($previewPath);
        }
        unset($this->context->cookie->gc_ingr_nutr_preview_path);
        
        Tools::redirectAdmin($this->context->link->getAdminLink('AdminGeminiContentManager'));
    }

    // =========================================================================
    // Koniec nowej logiki
    // =========================================================================
    
    private function displayConfigureView(): void
    {
        if (!class_exists('GeminiContent\Service\PromptBuilder')) {
            require_once _PS_MODULE_DIR_ . 'geminicontent/src/Service/PromptBuilder.php';
        }
        $csvProcessPrompt = Configuration::get('GEMINI_PROMPT_CSV_PROCESS');
        if (empty($csvProcessPrompt)) {
            $csvProcessPrompt = PromptBuilder::buildDefaultCsvProcessPrompt();
        }

        $stats = [
            'missing_both' => $this->productRepo->countToProcess(['status' => 'missing_both']),
            'missing_desc' => $this->productRepo->countToProcess(['status' => 'missing_desc']),
            'missing_seo' => $this->productRepo->countToProcess(['status' => 'missing_seo']),
        ];

        $this->context->smarty->assign([
            'gemini_api_key'        => Configuration::get('GEMINI_API_KEY'),
            'gemini_mock_mode'      => Configuration::get('GEMINI_MOCK_MODE'),
            'products_to_process'   => $this->productRepo->fetchToProcess([], 50, 0),
            'current_controller_url' => $this->context->link->getAdminLink('AdminGeminiContentManager'),
            'gemini_prompt_creative'  => Configuration::get('GEMINI_PROMPT_CREATIVE'),
            'gemini_prompt_formatter' => Configuration::get('GEMINI_PROMPT_FORMATTER'),
            'gemini_prompt_seo'       => Configuration::get('GEMINI_PROMPT_SEO'),
            'gemini_prompt_all_in_one' => Configuration::get('GEMINI_PROMPT_ALL_IN_ONE'),
            'gemini_prompt_csv_process' => $csvProcessPrompt,
            'gemini_stats'          => $stats,
            'gemini_merge_template'  => Configuration::get('GEMINI_MERGE_TEMPLATE'),
        ]);
        $this->setTemplate('configure.tpl');
    }

    private function processSaveSettings(): void
    {
        Configuration::updateValue('GEMINI_API_KEY', Tools::getValue('GEMINI_API_KEY'));
        Configuration::updateValue('GEMINI_MOCK_MODE', (int) Tools::getValue('GEMINI_MOCK_MODE'));
        Configuration::updateValue('GEMINI_PROMPT_CREATIVE',  Tools::getValue('GEMINI_PROMPT_CREATIVE'), true);
        Configuration::updateValue('GEMINI_PROMPT_FORMATTER', Tools::getValue('GEMINI_PROMPT_FORMATTER'), true);
        Configuration::updateValue('GEMINI_PROMPT_SEO',       Tools::getValue('GEMINI_PROMPT_SEO'), true);
        Configuration::updateValue('GEMINI_PROMPT_ALL_IN_ONE', Tools::getValue('GEMINI_PROMPT_ALL_IN_ONE'), true);
        Configuration::updateValue('GEMINI_PROMPT_CSV_PROCESS', Tools::getValue('GEMINI_PROMPT_CSV_PROCESS'), true);
        Configuration::updateValue('GEMINI_MERGE_TEMPLATE', Tools::getValue('GEMINI_MERGE_TEMPLATE'), true);
        $this->confirmations[] = $this->l('Ustawienia zostały zapisane.');
    }
}