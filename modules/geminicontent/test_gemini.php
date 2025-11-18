<?php
// Wyłączamy buforowanie, aby natychmiast zobaczyć wynik
@ini_set('display_errors', 1);
@error_reporting(E_ALL);

echo '<pre>'; // Używamy <pre> dla czytelnego formatowania wyniku

// --- W tym miejscu wklej swój NOWY klucz API ---
$apiKey = 'AIzaSyDfoaLFv3J6hFiJdkRmc8iQxcPh_pwnV3s';
// ----------------------------------------------------

// Ten warunek MUSI zostać nietknięty. Sprawdza, czy powyższy klucz został zmieniony.
if ($apiKey === 'TWOJ_NOWY_KLUCZ_API' || empty($apiKey)) {
    die("BŁĄD: Nie wklejono klucza API do pliku testowego. Otwórz plik test_gemini.php i wklej klucz w odpowiednim miejscu.");
}

$apiUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=' . $apiKey;
$data = [
    'contents' => [['parts' => [['text' => 'Testowe zapytanie']]]],
    'generationConfig' => ['responseMimeType' => 'text/plain']
];

echo "Krok 1: Próba połączenia z adresem URL:\n" . $apiUrl . "\n\n";

$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30); // Czas oczekiwania na odpowiedź
curl_setopt($ch, CURLOPT_VERBOSE, true); // Włączamy tryb "gadatliwy" dla cURL

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);

echo "Krok 2: Analiza odpowiedzi z serwera...\n\n";

if ($curlError) {
    echo "WYSTĄPIŁ KRYTYCZNY BŁĄD cURL (problem z połączeniem na poziomie serwera):\n";
    print_r($curlError);
} else {
    echo "Połączenie cURL powiodło się. Nie ma błędów na poziomie połączenia.\n";
    echo "Otrzymany kod statusu HTTP: " . $httpCode . "\n\n";

    echo "--- Pełna surowa odpowiedź z serwera Google --- \n";
    print_r($response);
    echo "\n--- Koniec odpowiedzi ---";
}

curl_close($ch);
echo '</pre>';