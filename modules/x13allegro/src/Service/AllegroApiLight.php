<?php
namespace PrestaShop\Modules\X13Allegro\Service;

if (!defined('_PS_VERSION_')) { exit; }

class AllegroApiLight
{
    public function __construct(private string $accessToken, private bool $isSandbox=false) {}

    protected function base(): string
    { return $this->isSandbox ? 'https://api.allegro.pl.allegrosandbox.pl' : 'https://api.allegro.pl'; }

    protected function headers(): array
    {
        return [
            'Authorization: Bearer '.$this->accessToken,
            'Accept: application/vnd.allegro.public.v1+json',
            'Content-Type: application/json',
        ];
    }

    protected function request(string $method, string $path, ?array $payload=null): array
    {
        $url = rtrim($this->base(),'/').$path;
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers());
        if ($payload !== null) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        $res = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        return ['status'=>$status, 'body'=>$res ? json_decode($res, true) : null, 'error'=>$err];
    }

    public function listResponsibleProducers(int $limit=100, int $offset=0): array
    { return $this->request('GET', '/sale/responsible-producers?limit='.$limit.'&offset='.$offset); }

    public function createResponsibleProducer(array $payload): array
    { return $this->request('POST', '/sale/responsible-producers', $payload); 
    public function listResponsiblePersons(int $limit=100, int $offset=0): array
    { return $this->request('GET', '/sale/responsible-persons?limit='.$limit.'&offset='.$offset); }
}
