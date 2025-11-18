<?php
namespace x13allegro\Service;

use Db;

class ResponsibleProducerResolver
{
    /** cache: name_norm => uuid */
    private static array $cache = [];

    public function __construct(
        private $allegroApi,   // ten sam klient, którego X13 używa do kategorii (ma metodę request)
        private string $accessToken,
        private bool $isSandbox
    ) {}

    /** Zwraca UUID producenta w Allegro na podstawie nazwy z PS (lub null) */
    public function resolveIdByName(string $producerName): ?string
    {
        $norm = $this->norm($producerName);
        if ($norm === '') { return null; }
        if (isset(self::$cache[$norm])) { return self::$cache[$norm]; }

        // 1) pobierz listę producentów (z paginacją) i zbuduj lokalną mapę
        $offset = 0; $limit = 100;
        for ($i=0; $i<50; $i++) {
            $resp = $this->request('GET', '/sale/responsible-producers?limit='.$limit.'&offset='.$offset, null);
            if ($resp['status'] < 200 || $resp['status'] >= 300) break;
            $items = $resp['body']['responsibleProducers'] ?? [];
            foreach ($items as $it) {
                if (!empty($it['name']) && !empty($it['id'])) {
                    self::$cache[$this->norm($it['name'])] = $it['id'];
                }
            }
            if (count($items) < $limit) break;
            $offset += $limit;
        }

        return self::$cache[$norm] ?? null;
    }

    /** Podaj do payloadu Allegro: ['responsibleProducer' => ['id' => '...']] jeśli znamy UUID */
    public function enrichProductSet(array &$productSetItem, string $producerName): void
    {
        $id = $this->resolveIdByName($producerName);
        if ($id) {
            $productSetItem['responsibleProducer'] = ['id' => $id];
        }
    }

    private function request(string $method, string $path, ?array $json): array
    {
        // preferuj tego samego klienta co kategorie (X13)
        if ($this->allegroApi && method_exists($this->allegroApi, 'request')) {
            $resp = $this->allegroApi->request($method, $path, [
                'headers' => [
                    'Authorization'   => 'Bearer '.$this->accessToken,
                    'Accept'          => 'application/vnd.allegro.public.v1+json',
                    'Content-Type'    => 'application/vnd.allegro.public.v1+json',
                    'Accept-Language' => 'pl-PL',
                ],
                'json'    => $json,
            ]);
            return [
                'status' => (int)($resp['status'] ?? $resp['code'] ?? 0),
                'body'   => $resp['body'] ?? ($resp['json'] ?? null),
                'error'  => $resp['error'] ?? '',
            ];
        }

        // fallback cURL (gdyby nie było klienta)
        $base = $this->isSandbox ? 'https://api.allegro.pl.allegrosandbox.pl' : 'https://api.allegro.pl';
        $url  = rtrim($base,'/').$path;
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer '.$this->accessToken,
            'Accept: application/vnd.allegro.public.v1+json',
            'Content-Type: application/vnd.allegro.public.v1+json',
            'Accept-Language: pl-PL',
        ]);
        if ($json !== null) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($json));
        $res = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        return ['status'=>$status, 'body'=>$res ? json_decode($res,true) : null, 'error'=>$err];
    }

    private function norm(string $s): string
    {
        return strtolower(trim(preg_replace('~\s+~', ' ', $s)));
    }
}
