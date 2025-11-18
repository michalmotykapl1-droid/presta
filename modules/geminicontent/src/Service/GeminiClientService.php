<?php
declare(strict_types=1);

namespace GeminiContent\Service;

use Configuration;
use PrestaShopException;
use Tools; // Dodano, bo używamy Tools::link_rewrite w mocku

class GeminiClientService
{
    private string $apiKey;
    private bool $isMockMode;
    private const API_BASE_URL = 'https://generativelanguage.googleapis.com/v1beta/models/';

    public function __construct()
    {
        $this->apiKey = (string) Configuration::get('GEMINI_API_KEY');
        $this->isMockMode = (bool) Configuration::get('GEMINI_MOCK_MODE');
        \GeminiContent\Service\GeminiContentLogger::debug('GeminiClientService: API Key loaded: ' . (empty($this->apiKey) ? 'EMPTY' : '***') . ', Mock Mode: ' . ($this->isMockMode ? 'TRUE' : 'FALSE'));
    }

    /**
     * Wysyła prompt do API Gemini.
     *
     * @param string $prompt
     * @param bool   $useProModel
     * @return array{success: bool, message?: string, data?: array|string}
     * @throws PrestaShopException
     */
    public function sendPrompt(string $prompt, bool $useProModel = false): array
    {
        \GeminiContent\Service\GeminiContentLogger::debug('sendPrompt: Wysyłam prompt. Model: ' . ($useProModel ? 'pro' : 'flash') . ', Tryb mock: ' . ($this->isMockMode ? 'TAK' : 'NIE') . ', Prompt (początek): ' . substr($prompt, 0, 200) . '...');

        if ($this->isMockMode) {
            $mockResponse = $this->getMockResponse($prompt);
            \GeminiContent\Service\GeminiContentLogger::debug('sendPrompt: Odpowiedź z mocka: ' . json_encode($mockResponse));
            return $mockResponse;
        }

        if (empty($this->apiKey)) {
            \GeminiContent\Service\GeminiContentLogger::error('sendPrompt: Klucz API jest pusty.');
            return ['success' => false, 'message' => 'Brak klucza API Gemini. Uzupełnij go w ustawieniach modułu.'];
        }

        $model = $useProModel ? 'gemini-1.0-pro-latest' : 'gemini-1.5-flash-latest';
        $apiUrl = self::API_BASE_URL . $model . ':generateContent?key=' . $this->apiKey;

        $payload = json_encode([
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ]
        ]);

        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 45);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            \GeminiContent\Service\GeminiContentLogger::error('sendPrompt: Błąd cURL: ' . $error);
            return ['success' => false, 'message' => 'Błąd cURL: ' . $error];
        }

        $responseData = json_decode($response, true);
        \GeminiContent\Service\GeminiContentLogger::debug('sendPrompt: Surowa odpowiedź API: ' . $response);

        if ($httpCode !== 200 || isset($responseData['error'])) {
            $errorMessage = $responseData['error']['message'] ?? 'Nieznany błąd API.';
            \GeminiContent\Service\GeminiContentLogger::error("sendPrompt: Błąd API (HTTP {$httpCode}): " . $errorMessage);
            return ['success' => false, 'message' => "Błąd API (HTTP {$httpCode}): " . $errorMessage];
        }
        
        $text = $responseData['candidates'][0]['content']['parts'][0]['text'] ?? '';

        if (empty($text)) {
            \GeminiContent\Service\GeminiContentLogger::warning('sendPrompt: API zwróciło pustą odpowiedź tekstową.');
            return ['success' => false, 'message' => 'API zwróciło pustą odpowiedź.'];
        }

        // Usuń bloki kodu markdown, jeśli AI je dodało
        $text = trim(str_replace(['```json', '```'], '', $text));
        \GeminiContent\Service\GeminiContentLogger::debug('sendPrompt: Przetworzona odpowiedź tekstowa: ' . substr($text, 0, 500) . '...');

        return ['success' => true, 'message' => $text];
    }

    /**
     * Zwraca fałszywą odpowiedź na potrzeby testów w trybie deweloperskim
     */
    private function getMockResponse(string $prompt): array
    {
        // Mock dla creative prompt (zwraca czysty tekst)
        if (str_contains($prompt, 'Jesteś ekspertem ds. content marketingu.')) {
            return [
                'success' => true,
                'message' => "To jest przykładowy nowy opis marketingowy wygenerowany w trybie mock. Jest świeży i angażujący, podkreśla kluczowe korzyści dla klienta.\n\nZapraszamy do zapoznania się z naszą ofertą!."
            ];
        }

        // Mock dla SEO prompt (zwraca JSON)
        if (str_contains($prompt, 'Jesteś ekspertem SEO dla e-commerce.')) {
             $data = [
                 "meta_title" => "Testowy Tytuł SEO - Produkt Mock",
                 "meta_description" => "To jest testowy opis meta generowany w trybie deweloperskim. Ma od 100 do 160 znaków.",
                 "link_rewrite" => "testowy-produkt-mock",
                 "tags" => ["test", "mock", "produkt"]
             ];
             return ['success' => true, 'message' => json_encode($data)];
         }

        // Mock dla formatter prompt (zwraca HTML)
        if (str_contains($prompt, 'Sformatuj poniższy tekst do czystego HTML.')) {
            return ['success' => true, 'message' => '<h2>Sformatowany Nagłówek</h2><p><strong>Pogrubiony</strong> tekst z trybu deweloperskiego.</p><ul><li>Punkt 1</li><li>Punkt 2</li></ul>'];
        }

        // Mock dla old_description_parser (zwraca JSON ustrukturyzowanych danych)
        if (str_contains($prompt, 'Jesteś zaawansowanym skryptem do parsowania i strukturyzowania danych.')) {
            return ['success' => true, 'message' => json_encode([
                "skladniki" => "mąka pszenna, woda, sól (mock_data)",
                "wartosc_odzywcza" => "Wartość energetyczna: 1500 kJ/358 kcal, Tłuszcz: 1.5g (mock_data)",
                "sposob_uzycia" => "Gotować 8-10 minut (mock_data).",
                "informacje_alergenne" => "Może zawierać śladowe ilości orzechów (mock_data).",
                "opis_ogolny" => "To jest ogólny tekst ze starego opisu, który nie pasował do żadnej sekcji (mock_data)."
            ])];
        }

        // Mock dla ALL_IN_ONE prompt (zwraca JSON z creative i seo)
        if (str_contains($prompt, 'Jesteś ekspertem SEO i content marketingu dla e-commerce. Na podstawie danych o produkcie, wygeneruj odpowiedź w formacie JSON. JSON musi zawierać klucze: "new_creative_text" i "seo_data".')) {
            $mockCreative = "To jest nowy opis z trybu ALL_IN_ONE, generowany przez mocka. Jest to bardzo atrakcyjny tekst marketingowy, pełen korzyści dla klienta.";
            $mockSeo = [
                "meta_title" => "Super Produkt ALL-IN-ONE (Mock)",
                "meta_description" => "Oto wszechstronny opis meta z trybu ALL-IN-ONE. Idealny dla SEO i konwersji.",
                "link_rewrite" => "super-produkt-all-in-one",
                "tags" => ["all-in-one", "mock", "seo-opis"]
            ];
            return ['success' => true, 'message' => json_encode([
                "new_creative_text" => $mockCreative,
                "seo_data" => $mockSeo
            ])];
        }

        // ZMIANA TUTAJ: Mock dla CSV_PROCESS - zwraca przykład przetworzonego wiersza CSV z HTML w description_ai i description_old_ai
        if (str_contains($prompt, '# Twoja Rola: Skrypt Przetwarzający Dane CSV')) {
            // Spróbujmy sparsować ID produktu z promptu, jeśli jest dostępne w linii CSV
            $idProduct = 'unknown';
            if (preg_match('/id_product[^;]*;\s*(\d+)/', $prompt, $matches)) {
                $idProduct = $matches[1];
            } else if (preg_match('/id_product":"(\d+)"/', $prompt, $matches)) {
                 $idProduct = $matches[1];
            }

            // Nowy, angażujący opis marketingowy jako HTML
            $mockDescription = "<h2>Przykładowy opis produktu ID {$idProduct}</h2><p>To jest <strong>nowy, angażujący</strong> opis marketingowy, wygenerowany w trybie mock CSV. Podkreśla kluczowe cechy i korzyści:</p><ul><li>Łatwy w użyciu</li><li>Wysoka jakość</li><li>Dostępna cena</li></ul><p>Zapraszamy do zakupu!</p>";
            
            // Sformatowany oryginalny opis jako HTML
            $mockOldDescription = "<h2>Oryginalne informacje o produkcie ID {$idProduct}</h2><p><strong>Skład:</strong> mąka pszenna, woda, sól.</p><p><strong>Wartość odżywcza (na 100g):</strong></p><ul><li>Wartość energetyczna: 1500 kJ/358 kcal</li><li>Tłuszcz: 1.5g</li></ul><p><strong>Sposób użycia:</strong> Gotować 8-10 minut.</p><p><strong>Informacje alergenne:</strong> Może zawierać śladowe ilości orzechów.</p>";

            $mockMetaTitle = "Tytuł SEO dla ID {$idProduct} (CSV Mock)";
            $mockMetaDescription = "Zoptymalizowany opis meta dla produktu ID {$idProduct}. Zachęcający do kliknięcia w wynikach wyszukiwania.";
            $mockLinkRewrite = Tools::link_rewrite("produkt-{$idProduct}-csv-mock");
            $mockTags = "produkt,csv,mock,tag{$idProduct}";

            // Zwracamy przykładową linię CSV z uzupełnionymi kolumnami _ai.
            // Ważne: Musimy zwrócić CAŁY CSV, włącznie z nagłówkiem i wszystkimi kolumnami,
            // dlatego najlepiej wziąć oryginalną linię CSV z promptu i dopisać do niej _ai.
            // Ta logika jest uproszczona i zakłada, że prompt zawiera pojedynczy wiersz CSV do przetworzenia.
            $csvLines = explode(PHP_EOL, trim($prompt));
            $headerLine = '';
            $dataLine = '';

            // Przeszukaj linie w poszukiwaniu nagłówka i linii danych
            foreach ($csvLines as $line) {
                if (str_contains($line, 'id_product') && str_contains($line, ';')) {
                    if (str_contains($line, '_ai')) { // To nagłówek
                        $headerLine = $line;
                    } else { // To linia danych
                        $dataLine = $line;
                    }
                }
            }

            // Domyślny nagłówek, jeśli nie znaleziono w prompcie (teraz z description_old_ai)
            if (empty($headerLine)) { 
                $headerLine = 'id_product;reference;ean13;name;description;meta_title;meta_description;link_rewrite;tags;description_ai;meta_title_ai;meta_description_ai;link_rewrite_ai;tags_ai;description_old_ai';
            }

            $outputCsv = $headerLine . PHP_EOL;

            if (!empty($dataLine)) {
                 $cols = explode(';', $dataLine);
                 // Upewnij się, że masz wystarczającą liczbę kolumn dla oryginalnych danych
                 while (count($cols) < 9) { // 9 kolumn to id_product do tags
                     $cols[] = '';
                 }

                 $processedCols = array_slice($cols, 0, 9); // Bierzemy oryginalne kolumny
                 $processedCols[] = $mockDescription;
                 $processedCols[] = $mockMetaTitle;
                 $processedCols[] = $mockMetaDescription;
                 $processedCols[] = $mockLinkRewrite;
                 $processedCols[] = $mockTags;
                 $processedCols[] = $mockOldDescription; // DODANO: sformatowany stary opis

                 $outputCsv .= implode(';', $processedCols);
            } else {
                 // Domyślna linia danych, jeśli nie znaleziono w prompcie
                 $outputCsv .= "794;TESTREF;TESTEAN;Testowy Produkt CSV;Orginalny opis.;Orginalny tytul;Orginalny meta opis;orginalny-link;orginalne-tagi;{$mockDescription};{$mockMetaTitle};{$mockMetaDescription};{$mockLinkRewrite};{$mockTags};{$mockOldDescription}";
            }

            return ['success' => true, 'message' => $outputCsv];
        }


        // Domyślna odpowiedź dla kreatywnego opisu
        return [
            'success' => true,
            'message' => "To jest domyślny, ogólny tekst generowany w trybie deweloperskim. Symuluje odpowiedź AI dla promptów, które nie mają specyficznego mocka.\n\nNowy akapit z dodatkowymi informacjami."
        ];
    }
}
