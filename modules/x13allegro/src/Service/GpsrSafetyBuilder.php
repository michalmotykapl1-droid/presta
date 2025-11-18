<?php
namespace PrestaShop\Modules\X13Allegro\Service;

if (!defined('_PS_VERSION_')) { exit; }

use Configuration;
use Context;
use Product;

class GpsrSafetyBuilder
{
    public function __construct(private ?Context $ctx = null)
    { $this->ctx = $ctx ?: Context::getContext(); }

    public function getFallbackResponsible(): array
    {
        return [
            'name'  => (string) Configuration::get('GPSR_RESP_NAME'),
            'addr'  => (string) Configuration::get('GPSR_RESP_ADDRESS'),
            'email' => (string) Configuration::get('GPSR_RESP_EMAIL'),
            'phone' => (string) Configuration::get('GPSR_RESP_PHONE'),
        ];
    }

    public function buildText(Product $product, ?string $brand, array $responsible): string
    {
        $tpl = (string) Configuration::get('GPSR_TEMPLATE');
        if (!$tpl) {
            $tpl = "Podmiot odpowiedzialny: {RESP_NAME}\nAdres: {RESP_ADDRESS}\nKontakt: {RESP_EMAIL}, {RESP_PHONE}\nInformacje: Produkt spełnia wymagania rozporządzenia (UE) 2023/988 (GPSR).";
        }

        $ean       = (string) $product->ean13;
        $ref       = (string) $product->reference;
        $prodName  = is_array($product->name)
            ? ($product->name[$this->ctx->language->id] ?? reset($product->name))
            : (string)$product->name;
        $today     = date('Y-m-d');

        $map = [
            '{RESP_NAME}'    => (string)($responsible['name']  ?? ''),
            '{RESP_ADDRESS}' => (string)($responsible['addr']  ?? ''),
            '{RESP_EMAIL}'   => (string)($responsible['email'] ?? ''),
            '{RESP_PHONE}'   => (string)($responsible['phone'] ?? ''),
            '{PRODUCT_NAME}' => (string)$prodName,
            '{REFERENCE}'    => (string)$ref,
            '{EAN}'          => (string)$ean,
            '{BRAND}'        => (string)($brand ?? ''),
            '{DATE}'         => (string)$today,
        ];

        return strtr($tpl, $map);
    }
}
