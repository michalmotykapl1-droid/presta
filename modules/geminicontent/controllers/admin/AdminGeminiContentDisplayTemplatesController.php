<?php
declare(strict_types=1);

class AdminGeminiContentDisplayTemplatesController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        $this->context = Context::getContext();
        $this->className = 'Configuration'; // Używamy 'Configuration' jako className, bo zarządzamy konf. modułu
        $this->table = 'configuration'; // Tabela w bazie danych
        $this->identifier = 'id_configuration'; // Klucz główny dla tej tabeli
        $this->name = 'AdminGeminiContentDisplayTemplates'; // Nazwa kontrolera

        parent::__construct();

        $this->fields_options = [
            'general_settings' => [
                'title' => $this->l('Główne Ustawienia Szablonów Wyświetlania'),
                'fields' => [
                    'GEMINI_MERGE_TEMPLATE' => [
                        'title' => $this->l('Szablon scalania opisów'),
                        'type' => 'textarea',
                        'rows' => 15,
                        'cols' => 90,
                        'desc' => $this->l('Definiuje szablon HTML używany do połączenia wszystkich części opisu. Użyj placeholderów: {NEW_CONTENT_PLACEHOLDER} (nowy opis AI), {EXTRACTED_TABLES_PLACEHOLDER} (tabele ze składnikami i wartościami odżywczymi) oraz {ORIGINAL_OLD_CONTENT_PLACEHOLDER} (oryginalny, nietknięty stary opis).'),
                        'validation' => 'isCleanHtml', // Ważne dla bezpieczeństwa!
                        'cast' => 'strval', // Upewnij się, że jest to string
                        'default' => $this->getInitialMergeTemplate(), // Ustawienie domyślne
                    ],
                ],
                'submit' => ['title' => $this->l('Zapisz zmiany')],
            ],
        ];
    }

    /**
     * Zwraca nowy, trzyczęściowy szablon scalania.
     */
    protected function getInitialMergeTemplate(): string
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
        <h3>Oryginalny opis produktu:</h3>
        <div class="original-description-text">
            {ORIGINAL_OLD_CONTENT_PLACEHOLDER}
        </div>
    </div>
</div>';
    }

    /**
     * Metoda postProcess jest wywoływana po przesłaniu formularza.
     * Dodatkowe logowanie i obsługa sukcesu/błędu.
     */
    public function postProcess()
    {
        if (Tools::isSubmit('submitOptionsgeneral_settings')) {
            // Logowanie przed wywołaniem parent::postProcess()
            \GeminiContent\Service\GeminiContentLogger::debug('AdminGeminiContentDisplayTemplatesController: Formularz wysłany, przetwarzam dane.');
            parent::postProcess();
            // Po udanym zapisie (lub błędzie) parent::postProcess przekierowuje lub wyświetla błąd
            // Tutaj możesz dodać dodatkowe logowanie sukcesu
            if (empty($this->errors)) {
                \GeminiContent\Service\GeminiContentLogger::info('AdminGeminiContentDisplayTemplatesController: Ustawienia szablonów zapisane pomyślnie.');
            } else {
                \GeminiContent\Service\GeminiContentLogger::error('AdminGeminiContentDisplayTemplatesController: Błąd podczas zapisywania ustawień szablonów: ' . implode(', ', $this->errors));
            }
        }
    }

    /**
     * Overrides the display method to show the options form.
     */
    public function renderOptions()
    {
        // Sprawdzenie, czy klasa GeminiContent\Service\GeminiContentLogger istnieje
        if (!class_exists('GeminiContent\Service\GeminiContentLogger')) {
            require_once _PS_MODULE_DIR_ . 'geminicontent/src/Service/GeminiContentLogger.php';
        }
        \GeminiContent\Service\GeminiContentLogger::debug('AdminGeminiContentDisplayTemplatesController: Renderuję formularz opcji.');

        return parent::renderOptions();
    }
}