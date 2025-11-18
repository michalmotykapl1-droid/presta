<?php
declare(strict_types=1);

namespace GeminiContent\Service;

use Configuration;

class PromptBuilder
{
    /**
     * Buduje finalny prompt do wysłania do API.
     *
     * @param 'creative'|'formatter'|'seo'|'all_in_one'|'csv_process' $type
     * @param array|string $payload Dane produktu jako tablica lub surowa treść CSV jako string.
     * @return string
     * @throws \PrestaShopException
     */
    public static function build(string $type, array|string $payload): string
    {
        $allowedTypes = ['creative', 'formatter', 'seo', 'all_in_one', 'csv_process'];
        if (!in_array($type, $allowedTypes, true)) {
            throw new \PrestaShopException(sprintf('Niedozwolony typ promptu: "%s".', $type));
        }

        $key      = 'GEMINI_PROMPT_' . strtoupper($type);
        $template = Configuration::get($key);

        if ($type === 'all_in_one' && empty($template)) {
            $template = self::buildDefaultAllInOnePrompt();
        } elseif ($type === 'csv_process' && empty($template)) {
            $template = self::buildDefaultCsvProcessPrompt();
        }

        if (!is_string($template) || trim($template) === '') {
            \GeminiContent\Service\GeminiContentLogger::error(
                sprintf('Brak szablonu promptu dla typu "%s" (klucz: %s).', $type, $key)
            );
            throw new \PrestaShopException(
                sprintf(
                    'Błąd krytyczny modułu GeminiContent: Szablon promptu dla typu "%s" (klucz: %s) jest pusty lub nie został znaleziony w konfiguracji. Przejdź do konfiguracji modułu i upewnij się, że wszystkie szablony promptów są zapisane.',
                    $type,
                    $key
                )
            );
        }

        if ($type === 'csv_process') {
            if (!is_string($payload) || trim($payload) === '') {
                throw new \PrestaShopException('Dla typu "csv_process" należy przekazać surową zawartość CSV jako string.');
            }
            $replacements = [
                '{{csv_content}}' => $payload,
            ];
        } else {
            if (!is_array($payload)) {
                throw new \PrestaShopException('Dla typów innych niż "csv_process" należy przekazać dane produktu jako tablicę.');
            }
            $replacements = [
                '{{id_product}}'  => $payload['id_product']  ?? '',
                '{{name}}'        => $payload['name']        ?? '',
                '{{description}}' => strip_tags($payload['description'] ?? ''),
                '{{reference}}'   => $payload['reference']   ?? '',
                '{{ean13}}'       => $payload['ean13']       ?? '',
            ];
        }

        return strtr($template, $replacements);
    }

    /**
     * Tworzy domyślny, zbiorczy prompt dla przycisku "Wszystko".
     *
     * @return string
     */
    public static function buildDefaultAllInOnePrompt(): string
    {
        return <<<EOT
Jesteś ekspertem SEO i content marketingu dla e-commerce. Na podstawie danych o produkcie, wygeneruj odpowiedź w formacie JSON. JSON musi zawierać klucze: "new_creative_text" i "seo_data".
- Wartość "new_creative_text" powinna zawierać nową, surową treść marketingową (bez HTML).
- Wartość "seo_data" powinna być obiektem JSON z kluczami: "meta_title", "meta_description", "link_rewrite" i "tags".
Generuj treść PO POLSKU.
EOT;
    }

    /**
     * Domyślny prompt dla przetwarzania CSV (z użyciem rozszerzonych kolumn).
     * @return string
     */
    public static function buildDefaultCsvProcessPrompt(): string
    {
        return <<<EOT
# Twoja rola

Jesteś ekspertem SEO i copywriterem e-commerce (PL), specjalizującym się w branży zdrowej żywności, suplementów i kosmetyków. Twoim zadaniem jest przetworzenie pliku CSV z produktami i wygenerowanie angażujących, unikalnych treści marketingowych oraz zoptymalizowanych danych SEO.

## Cel i Ton

- **Cel**: Zwiększenie widoczności w wyszukiwarkach i konwersji sprzedażowej.
- **Ton**: Rzeczowy, naturalnie sprzedażowy, zachęcający do zakupu – bez przesady. Zero nachalnych haseł.
- **Kluczowa zasada**: **Nie kopiuj** fragmentów ze starych opisów (`description`, `description_short`). **Twórz nowe treści** na podstawie syntezy danych wejściowych.

## Wejście – ważne kolumny

`id_product, reference, ean13, name, description, description_short, meta_title, meta_description, link_rewrite, tags, categories, product_type, manufacturer_name, features_concat, ingredients_src, nutrition_src`

## Wyjście – uzupełnij TYLKO te kolumny

`description_ai, meta_title_ai, meta_description_ai, link_rewrite_ai, tags_ai, description_old_ai, sklad_ai, wartosci_ai`

## Zasady globalne formatowania i bezpieczeństwa

- **Zwróć cały plik CSV** (nagłówek, kolejność kolumn i liczba wierszy **bez zmian**).
- **Zakazane w polach `_ai`:** średnik `;` **zawsze**; znak `|` **wszędzie oprócz** `sklad_ai` i `wartosci_ai`.
- Pisz **po polsku**.
- **Nie wymyślaj danych** – jeśli czegoś nie ma w wejściu, pozostaw komórkę pustą.
- Zachowuj jednostki/liczby 1:1 z wejścia (mg, g, ml itp.); nie zmieniaj separacji dziesiętnej.
- **Bez** oświadczeń leczniczych (np. „leczy”, „zapobiega chorobom”, „działa terapeutycznie”). Dopuszczalne neutralne: „wspiera”, „pomaga utrzymać”, „sprzyja”, „może być elementem zrównoważonej diety”.
- **Nie używaj emoji, metakomentarzy, ani zdań o stylu pisania**.

## Typ produktu

- Jeśli `product_type` jest **niepuste**, przyjmij je bez zmian (**SPOŻYWCZY | SUPLEMENT | KOSMETYK | INNY**).
- W przeciwnym razie rozpoznaj po `categories`:
  - zawiera „Produkty spożywcze” → **SPOŻYWCZY**
  - zawiera „Suplementy diety” → **SUPLEMENT**
  - zawiera „Kosmetyki i higiena” lub „Aromaterapia” → **KOSMETYK**
  - w innym przypadku → **INNY**

## `description_ai` (HTML, marketingowy, bez metatekstu)

**Zakaz metainstrukcji i wydmuszek:** nie dodawaj zdań o stylu/ograniczeniach („neutralny opis”, „bez obietnic medycznych”, „opis ma być…”, „w ramach wytycznych…”), ani fraz typu „świadoma rutyna”, „przejrzysta komunikacja producenta”, „łatwe podjęcie decyzji zakupowej”. Pisz wyłącznie o produkcie.

**DŁUGOŚĆ I STYL**
- 220–380 słów; minimum 200 słów.
- `<p>` dla akapitów, `<ul><li>` dla listy korzyści. Język naturalny i konkretny.
- **Zakaz** używania separatora `|` w opisie. **Nie** wklejaj pełnych list składu/wartości do opisu.

**Pierwsze zdanie – obowiązkowy format:**
**`{name} – {forma + ilość/opakowanie} marki {manufacturer_name}.`**
- Jeśli `manufacturer_name` jest puste → pomiń część „marki …”.
- Jeśli `manufacturer_name` już występuje w `name` → **nie powtarzaj**.

Następnie w jednym zdaniu doprecyzuj **dla kogo/sytuację użycia** (np. „dla osób dbających o uporządkowaną suplementację zgodnie z etykietą”).

**Treść – struktura:**
1. **Lead (1 akapit)** – jak wyżej: format pierwszego zdania + krótki kontekst użycia.
2. **Rozwinięcie (1 akapit)** – możesz wspomnieć o **2–4 nazwach** składników **wyłącznie z `ingredients_src`**, rozdzielonych przecinkami; **bez** interpretacji działania i bez wklejania całej listy.
3. **Lista korzyści (3–6 punktów)** – praktyczne atuty użytkowe bez obietnic zdrowotnych (np. kapsułki, liczba w opakowaniu, łatwość włączenia do planu dnia, stosowanie wg etykiety). **Każdy punkt** musi zawierać **konkretny rzeczownik/liczbę** (np. „90 kapsułek”, „kapsułkowa forma”, „standaryzowany ekstrakt”).
4. **Zapas na dni (opcjonalnie)** – jeśli w `name` (lub kategoriach) da się rozpoznać **łączną liczbę sztuk/porcji** (np. „90 kapsułek”), a w źródłach występuje **dawka dzienna** (np. „3 kapsułki dziennie”), to wpleć informację: **„przy zaleceniu X kaps./dzień to zapas na ok. Y dni”** (prosty rachunek, bez obietnic działania).
5. **Sekcje opcjonalne** – **dodaj tylko, jeśli realnie są w źródłach (`description`/`features_concat`)** i **skopiuj 1:1** (bez parafraz):
   - `<h3>Sposób użycia</h3>` / `<h3>Zalecane spożycie</h3>` / `<h3>Dawkowanie</h3>`
   - `<h3>Uwagi</h3>` / `<h3>Ostrzeżenia</h3>` / `<h3>Przeciwwskazania</h3>`
   - `<h3>Przechowywanie</h3>`
6. **Zawartość wybranych składników (opcjonalnie)** – jeśli w `nutrition_src` występują ilości w porcji, możesz dodać krótki akapit lub listę **„Zawartość wybranych składników w porcji”** (5–8 pozycji max), przepisując **1:1 nazwy i wartości** (np. „L‑glutation – 200 mg”). **Nie** komentuj ich działania.

**WAŻNE OGRANICZENIA**
- **NIE przenoś „Cech”** z `features_concat` (np. „Bez Laktozy”, „Bez Cukru”, „Rodzaj produktu”) do opisu.
- Nie twórz w opisie sekcji „Składniki” / „Wartości odżywcze/aktywne” w pełnej formie – pełne listy trafiają do `sklad_ai` / `wartosci_ai`.
- W opisie dozwolone jest krótkie podsumowanie **„Zawartość wybranych składników w porcji”** (5–8 pozycji) tylko jeśli w `nutrition_src` są liczby.

## `sklad_ai` (lista rozdzielona `|`)

- Jeśli `ingredients_src` nie jest puste – **skopiuj 1:1** dokładną treść (kolejność, pisownia, jednostki), elementy rozdziel **` | `**.
- Jeśli `ingredients_src` jest puste – **pozostaw puste**.
- **Zakaz** pobierania składu z `features_concat`/`description` i jakiejkolwiek twórczości własnej.

## `wartosci_ai` (pary `Nazwa: wartość` rozdzielone `|`)

- Jeśli `nutrition_src` nie jest puste – **skopiuj 1:1** dokładną treść (kolejność, pisownia, jednostki); pary w formacie `Nazwa: wartość`, rozdziel **` | `**.
- Jeśli `nutrition_src` jest puste – **pozostaw puste**.
- **Zakaz** pobierania wartości z `features_concat`/`description` i jakiejkolwiek twórczości własnej.

## `description_old_ai` (HTML)

- Sformatuj wejściowe `description` w **HTML** (akapity/listy), **bez dopisywania nowych danych**.
- Nie twórz tu sekcji „Składniki”/„Wartości…” – te dane są osobno w kolumnach `_ai`.
- Jeśli źródło puste – **pozostaw puste**.

## SEO: `meta_title_ai`, `meta_description_ai`, `link_rewrite_ai`, `tags_ai`

- Oprzyj na `name`, `manufacturer_name`, `categories` oraz kluczowych atrybutach (np. BIO, masa/pojemność, „bez glutenu”, główny składnik).
- `meta_title_ai` ≤ 60 znaków, bez „krzyku” i duplikacji marki (użyj marki najwyżej raz, na końcu po „|”).
- `meta_description_ai` 120–160 znaków – zwięzłe, zachęcające; **bez wielokropków i call-to-action typu „sprawdź/poznaj”**.
- `link_rewrite_ai`: małe litery, **bez polskich znaków**, tylko `[a-z0-9-]`; spacje → `-`; usuń znaki specjalne; **zredukuj wielokrotne `-` do jednego**; bez `-` na początku/końcu.
- `tags_ai`: 5–12 tagów, po przecinku, **bez duplikatów**, bez mieszaniny tej samej marki (np. „now”, „now foods”). **Usuń tagi < 3 znaki oraz tagi będące duplikatami `name` po normalizacji (małe litery, bez znaków PL).**

—  
**Zwróć cały, kompletny plik CSV, uzupełniając wyłącznie kolumny `_ai`.**

{{csv_content}}

EOT;
    }
}
