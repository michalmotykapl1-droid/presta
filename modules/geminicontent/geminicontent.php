<?php
    // Prosty PSR-4 autoloader dla przestrzeni nazw GeminiContent\
    spl_autoload_register(function ($class) {
        $prefix  = 'GeminiContent\\';
        $baseDir = __DIR__ . '/src/';
        if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
            return;
        }
        $relativeClass = substr($class, strlen($prefix));
        $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
        if (file_exists($file)) {
            require $file;
        }
    });

    if (!defined('_PS_VERSION_')) {
        exit;
    }

    class GeminiContent extends Module
    {
        public function __construct()
        {
            $this->name = 'geminicontent';
            $this->tab = 'content_management';
            $this->version = '1.1.0'; // ZMIANA 1: Podniesiona wersja modułu
            $this->author = 'BIGBIO';
            $this->need_instance = 0;
            $this->ps_versions_compliancy = [
                'min' => '8.1.0',
                'max' => _PS_VERSION_
            ];
            $this->bootstrap = true;

            parent::__construct();

            $this->displayName = $this->l('Gemini AI Content');
            $this->description = $this->l('Moduł do generowania i optymalizacji opisów produktów za pomocą Gemini API.');
            $this->confirmUninstall = $this->l('Czy na pewno chcesz odinstalować ten moduł?');
            $this->logo = $this->_path.'logo.png';
            
            \GeminiContent\Service\GeminiContentLogger::debug('Moduł GeminiContent został załadowany.');
        }

        public function install()
        {
            \GeminiContent\Service\GeminiContentLogger::info('Rozpoczęto instalację modułu GeminiContent.');
            
            // ZMIANA: Usunięto rejestrację hooka displayAdminAfterHeader, ponieważ panel zostanie przeniesiony do szablonu
            if (!parent::install()
                || !$this->registerHook('actionAdminControllerSetMedia')
                || !$this->installDb()
            ) {
                \GeminiContent\Service\GeminiContentLogger::error('Instalacja nie powiodła się na wczesnym etapie (parent, hook, db).');
                return false;
            }

            // --- NOWE, ULEPSZONE PROMPTY ---
            Configuration::updateValue('GEMINI_PROMPT_CREATIVE', <<<EOT
Jesteś ekspertem ds. content marketingu. Na podstawie nazwy i istniejącego opisu produktu, napisz 2-3 akapity nowej, angażującej treści marketingowej. Skup się na korzyściach dla klienta. Zwróć czysty tekst, bez formatowania HTML i bez Markdown.
EOT
            , true);

            Configuration::updateValue('GEMINI_PROMPT_FORMATTER', <<<EOT
Jesteś ekspertem ds. formatowania webowego. Sformatuj poniższy tekst do czystego HTML.
- Nagłówki oznaczone jako "## " zmień na <h2>.
- Linie zaczynające się od myślnika "-" zmień na elementy listy <li> wewnątrz tagu <ul>.
- Tekst otoczony **gwiazdkami** zmień na <strong>.
Nie zmieniaj treści, tylko format.
EOT
            , true);

            Configuration::updateValue('GEMINI_PROMPT_SEO', <<<EOT
Jesteś ekspertem SEO dla e-commerce. Na podstawie danych o produkcie wygeneruj odpowiedź w formacie JSON z kluczami: "meta_title" (max 60 znaków), "meta_description" (100-160 znaków), "link_rewrite" i "tags" (tablica stringów).
EOT
            , true);
            
            Configuration::updateValue('GEMINI_PROMPT_ALL_IN_ONE', <<<EOT
Jesteś ekspertem SEO i content marketingu dla e-commerce. Na podstawie danych o produkcie, wygeneruj odpowiedź w formacie JSON. JSON musi zawierać klucze: "new_creative_text" i "seo_data".
- Wartość "new_creative_text" powinna zawierać nową, surową treść marketingową (bez HTML).
- Wartość "seo_data" powinna być obiektem JSON z kluczami: "meta_title", "meta_description", "link_rewrite" i "tags".
Generuj treść PO POLSKU.
EOT
            , true);

            // Zaktualizowany prompt dla GEMINI_PROMPT_CSV_PROCESS
            Configuration::updateValue('GEMINI_PROMPT_CSV_PROCESS', <<<EOT
# Twoja Rola: Skrypt Przetwarzający Dane CSV z Optymalizacją SEO i Zgodnością SANEPID

Jesteś zaawansowanym skryptem do przetwarzania danych w formacie CSV. Twoim zadaniem jest odczytanie zawartości pliku CSV, przetworzenie każdej linii z uwzględnieniem wytycznych SEO i wymogów SANEPID, a następnie zwrócenie pełnego, zaktualizowanego pliku CSV.

# Zasady Działania
1.  **Dane Wejściowe:** Otrzymasz ode mnie blok tekstu, który jest zawartością pliku CSV. Separatorem kolumn jest średnik `;`. Nagłówek jest zawsze pierwszą linią.
2.  **Twoje Zadanie:** Przeczytaj każdą linię danych (pomiń nagłówek). Dla każdej linii, używając danych z kolumn `name`, `description`, `reference`, `ean13` itd. jako kontekstu, wygeneruj nowe treści dla kolumn `description_ai`, `meta_title_ai`, `meta_description_ai`, `link_rewrite_ai`, `tags_ai` oraz **nowej kolumny `description_old_ai`**.
3.  **Dane Wyjściowe (Krytycznie Ważne):** Twoja odpowiedź musi być **wyłącznie surowym tekstem**, reprezentującym kompletną, nową zawartość pliku CSV. Zwróć cały plik: nagłówek, oryginalne dane i nowo wygenerowane treści. **Nie dodawaj żadnych komentarzy ani bloków kodu ```. NIE Zmieniaj oryginalnych kolumn (przed `_ai`), tylko je powtarzaj.**

---
## **Wymagania dla generowanych treści (dla kolumn z końcówką _ai)**

Dla każdej linii CSV, na podstawie danych produktu, wygeneruj treści dla poniższych kolumn. Odpowiedzi muszą być w języku polskim. **Pamiętaj o wytycznych SANEPID (brak twierdzeń leczniczych i medycznych) oraz optymalizacji SEO.**

### Kolumna `description_ai` (Nowy, Angażujący Opis Produktu)
-   **Cel:** Na podstawie nazwy produktu i jego podstawowych cech (z kolumn `name`, `reference`, `ean13`), napisz 2-3 akapity zupełnie nowej, angażującej treści marketingowej. **Ignoruj treść z kolumny `description` przy tworzeniu tego opisu.** Skup się wyłącznie na korzyściach dla klienta, walorach smakowych, kulinarnych i zastosowaniu.
-   **Wymogi SEO:** Wpleć naturalnie frazy kluczowe (np. z `name` lub sugestie dotyczące kategorii produktu), utrzymując zagęszczenie na poziomie 2-3%.
-   **Wymogi SANEPID:** Całkowicie unikaj stwierdzeń o właściwościach leczniczych, medycznych, prozdrowotnych lub jakichkolwiek sugestii zdrowotnych. Tekst musi być zgodny z prawem żywnościowym.
-   **Ton:** Przyjazny, marketingowy, ale w pełni zgodny z prawem.
-   **Format:** **Wygeneruj treść w formacie HTML.** Używaj tagów `<h2>` dla nagłówków sekcji (np. "Odkryj smak...", "Idealny do..."), `<p>` dla akapitów, `<ul>` i `<li>` dla list (jeśli zasadne). Tekst powinien być czytelny i dobrze sformatowany.

### Kolumna `description_old_ai` (Oryginalny Opis Produktu - sformatowany i ustrukturyzowany przez AI)
-   **Cel:** Na podstawie oryginalnego opisu produktu (z kolumny `description`), przeanalizuj go i sformatuj do postaci czytelnego HTML. **Wyodrębnij i klarownie zaprezentuj kluczowe informacje, takie jak skład, wartości odżywcze, sposób użycia, informacje alergiczne, kraj pochodzenia, producent itp.** Nie dodawaj nowej treści marketingowej.
-   **Wymogi SANEPID:** Upewnij się, że formatowanie nie wprowadza nowych, niezgodnych z przepisami twierdzeń. Jedynie strukturyzuj istniejące dane.
-   **Format:** **Pełny kod HTML.** Używaj tagów `<h2>` dla głównych sekcji (np. "Składniki", "Wartości odżywcze", "Sposób przygotowania", "Informacje dla alergików", "Kraj pochodzenia"), `<p>` dla akapitów, `<ul>` i `<li>` dla list. Każda sekcja powinna być jasno oddzielona.

### Kolumny SEO: `meta_title_ai`, `meta_description_ai`, `link_rewrite_ai`, `tags_ai`
-   **Cel:** Na podstawie danych o produkcie wygeneruj: "meta_title" (max 60 znaków), "meta_description" (100-160 znaków), "link_rewrite" i "tags" (lista tagów oddzielonych przecinkiem).
-   **Wymogi SEO:**
    * `meta_title_ai`: Chwytliwy tytuł meta, maksymalnie 60 znaków, zawierający główne słowa kluczowe.
    * `meta_description_ai`: Zachęcający opis meta, 100-160 znaków, skłaniający do kliknięcia, zawierający naturalnie wplecione słowa kluczowe i korzyści.
    * `link_rewrite_ai`: Małe litery, bez polskich znaków, słowa oddzielone myślnikiem. Powinien być czytelny i zawierać główne słowo kluczowe.
    * `tags_ai`: Lista istotnych i trafnych tagów oddzielonych przecinkiem, reprezentujących produkt i jego cechy.
-   **Wymogi SANEPID:** Upewnij się, że żadne elementy SEO (tytuły, opisy, tagi) nie zawierają twierdzeń leczniczych ani medycznych.

---
**Poniżej znajduje się zawartość pliku CSV do przetworzenia. Zawsze zwracaj cały CSV, włącznie z nagłówkiem i wszystkimi kolumnami.**
{{description}}
EOT
            , true);
            // DODANIE DOMYŚLNEGO SZABLONU SCALANIA PRZY INSTALACJI
            Configuration::updateValue('GEMINI_MERGE_TEMPLATE', $this->getInitialMergeTemplateDefault(), true);
            // -------------------------------------------------------

            // Dodanie zakładek w BO
            $parentTab = new Tab();
            $parentTab->active     = 1;
            $parentTab->class_name = 'AdminGeminiContentParent';
            $parentTab->name       = [];
            foreach (Language::getLanguages(true) as $lang) {
                $parentTab->name[$lang['id_lang']] = 'Gemini AI Content';
            }
            $parentTab->id_parent = (int) Tab::getIdFromClassName('IMPROVE');
            $parentTab->module    = $this->name;
            if (!$parentTab->add()) {
                \GeminiContent\Service\GeminiContentLogger::error('Instalacja: Nie udało się dodać zakładki nadrzędnej.');
                return false;
            }

            $tab1 = new Tab();
            $tab1->active     = 1;
            $tab1->class_name = 'AdminGeminiContentManager';
            $tab1->name       = [];
            foreach (Language::getLanguages(true) as $lang) {
                $tab1->name[$lang['id_lang']] = 'Do zrobienia';
            }
            $tab1->id_parent = (int) $parentTab->id;
            $tab1->module    = $this->name;
            if (!$tab1->add()) {
                \GeminiContent\Service\GeminiContentLogger::error('Instalacja: Nie udało się dodać zakładki "Do zrobienia".');
                return false;
            }

            $tab2 = new Tab();
            $tab2->active     = 1;
            $tab2->class_name = 'AdminGeminiContentLog';
            $tab2->name       = [];
            foreach (Language::getLanguages(true) as $lang) {
                $tab2->name[$lang['id_lang']] = 'Zmienione produkty';
            }
            $tab2->id_parent = (int) $parentTab->id;
            $tab2->module    = $this->name;
            if (!$tab2->add()) {
                \GeminiContent\Service\GeminiContentLogger::error('Instalacja: Nie udało się dodać zakładki "Zmienione produkty".');
                return false;
            }

            // NOWA ZAKŁADKA: Szablony Wyświetlania
            $tab3 = new Tab();
            $tab3->active     = 1;
            $tab3->class_name = 'AdminGeminiContentDisplayTemplates'; // Unikalna klasa
            $tab3->name       = [];
            foreach (Language::getLanguages(true) as $lang) {
                $tab3->name[$lang['id_lang']] = 'Szablony Wyświetlania'; // Nazwa widoczna w menu
            }
            $tab3->id_parent = (int) $parentTab->id; // Podpięcie pod ten sam rodzic
            $tab3->module    = $this->name;
            if (!$tab3->add()) {
                \GeminiContent\Service\GeminiContentLogger::error('Instalacja: Nie udało się dodać zakładki "Szablony Wyświetlania".');
                return false;
            }


            \GeminiContent\Service\GeminiContentLogger::info('Instalacja modułu GeminiContent zakończona sukcesem.');
            return true;
        }

        public function installDb()
        {
            $sql = "
                CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX_ . "geminicontent_log` (
                    `id_product` INT(11) UNSIGNED NOT NULL,
                    `description_generated` TINYINT(1) NOT NULL DEFAULT 0,
                    `description_date_add` DATETIME NULL DEFAULT NULL,
                    `seo_generated` TINYINT(1) NOT NULL DEFAULT 0,
                    `seo_date_add` DATETIME NULL DEFAULT NULL,
                    `last_update` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id_product`)
                ) ENGINE=" . _MYSQL_ENGINE_ . " DEFAULT CHARSET=utf8;
            ";
            return Db::getInstance()->execute($sql);
        }

        public function uninstall()
        {
            \GeminiContent\Service\GeminiContentLogger::info('Rozpoczęto deinstalację modułu GeminiContent.');
            
            Db::getInstance()->execute('DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'geminicontent_log`');

            $tabClasses = [
                'AdminGeminiContentManager',
                'AdminGeminiContentLog',
                'AdminGeminiContentParent',
                'AdminGeminiContentDisplayTemplates', // DODANO NOWĄ KLASĘ ZAKŁADKI
            ];
            foreach ($tabClasses as $class_name) {
                $tabId = (int) Tab::getIdFromClassName($class_name);
                if ($tabId) {
                    $tab = new Tab($tabId);
                    $tab->delete();
                }
            }

            Configuration::deleteByName('GEMINI_API_KEY');
            Configuration::deleteByName('GEMINI_MOCK_MODE');
            Configuration::deleteByName('GEMINI_PROMPT_CREATIVE');
            Configuration::deleteByName('GEMINI_PROMPT_FORMATTER');
            Configuration::deleteByName('GEMINI_PROMPT_SEO');
            Configuration::deleteByName('GEMINI_PROMPT_ALL_IN_ONE');
            Configuration::deleteByName('GEMINI_PROMPT_CSV_PROCESS');
            // DODANO KLUCZ DLA GŁÓWNEGO SZABLONU SCALANIA
            Configuration::deleteByName('GEMINI_MERGE_TEMPLATE'); 

            return parent::uninstall();
        }

        /**
         * Zwraca domyślny szablon scalania, jeśli nie ma go w konfiguracji.
         * Dodano tutaj, aby móc go użyć przy instalacji oraz w DescriptionRendererService.
         */
        protected function getInitialMergeTemplateDefault(): string
        {
            // ZMIANA 2: Nowy, trzyczęściowy szablon domyślny
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

        public function getContent()
        {
            // Przekierowanie do nowej zakładki domyślnej, np. do zarządzania szablonami
            Tools::redirectAdmin(
                $this->context->link->getAdminLink('AdminGeminiContentDisplayTemplates') // Zmieniono na nową zakładkę
            );
        }

        public function hookActionAdminControllerSetMedia()
        {
            $currentController = Tools::getValue('controller');
            $allowedControllers = [
                'AdminGeminiContentManager',
                'AdminGeminiContentLog',
                'AdminGeminiContentDisplayTemplates', // DODANO NOWY KONTROLER
            ];

            if (in_array($currentController, $allowedControllers)) {
                \GeminiContent\Service\GeminiContentLogger::debug("Dodawanie JS/CSS dla kontrolera: {$currentController}.");
                
                $this->context->controller->addJs($this->_path . 'views/js/admin_manager.js');
                $this->context->controller->addCss($this->_path . 'views/css/geminicontent.css');
                
                $js_variables = [
                    'gemini_ajax_url' => $this->context->link->getAdminLink('AdminGeminiContentManager'),
                    // <<< KLUCZOWA POPRAWKA TUTAJ: Generujemy token dla właściwego kontrolera >>>
                    'gemini_token' => Tools::getAdminTokenLite('AdminGeminiContentManager')
                ];

                if ($currentController === 'AdminGeminiContentManager') {
                    if (!class_exists('GeminiContent\Service\PromptBuilder')) {
                        require_once __DIR__ . '/src/Service/PromptBuilder.php';
                    }
                    $js_variables['default_all_in_one_prompt'] = \GeminiContent\Service\PromptBuilder::buildDefaultAllInOnePrompt();
                }
                
                Media::addJsDef($js_variables);
            }
        }
    }

