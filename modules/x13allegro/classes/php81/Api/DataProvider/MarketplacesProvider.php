<?php

namespace x13allegro\Api\DataProvider;

use Configuration;
use Context;
use Country;
use Currency;
use Language;
use XAllegroConfiguration;
use x13allegro\Api\Model\Marketplace\Enum\Marketplace;
use x13allegro\Api\Model\Marketplace\Enum\MarketplaceCountry;
use x13allegro\Api\Model\Marketplace\Enum\MarketplaceCurrency;
use x13allegro\Api\Model\Marketplace\Enum\MarketplaceCurrencyPrecision;
use x13allegro\Api\Model\Marketplace\Enum\MarketplaceLocalization;
use x13allegro\Api\Model\Marketplace\Enum\MarketplaceOfferUrl;
use x13allegro\Exception\ModuleException;

final class MarketplacesProvider
{
    /** @var string */
    private $marketplaceId;

    /** @var array */
    private static $cache = [];

    /**
     * @param string $marketplaceId
     * @throws ModuleException
     */
    public function __construct($marketplaceId)
    {
        if (!Marketplace::isValid($marketplaceId)) {
            throw new ModuleException("Invalid Marketplace $marketplaceId");
        }

        $this->marketplaceId = $marketplaceId;
    }

    /**
     * @return Marketplace
     */
    public function getMarketplace()
    {
        return Marketplace::from($this->marketplaceId);
    }

    /**
     * @return string
     */
    public function getMarketplaceName()
    {
        return $this->getMarketplace()->getValueTranslated();
    }

    /**
     * @param int $offerId
     * @param bool $isSandbox
     * @return string
     */
    public function getMarketplaceOfferUrl($offerId, $isSandbox)
    {
        $marketplaceId = Marketplace::from($this->marketplaceId)->getKey();

        return str_replace(
            [
                '{sandbox}',
                '{offerId}'
            ],
            [
                $isSandbox ? '.allegrosandbox.pl' : '',
                $offerId
            ],
            MarketplaceOfferUrl::$marketplaceId()->getValue()
        );
    }

    /**
     * @return Country
     * @throws ModuleException
     */
    public function getMarketplaceCountry()
    {
        if (isset(self::$cache[$this->marketplaceId]['country'])) {
            return self::$cache[$this->marketplaceId]['country'];
        }

        $marketplaceLocalization = self::getMarketplacesLocalization()[$this->marketplaceId];
        $countryIso = $marketplaceLocalization['countryIso'];
        $countryId = Country::getByIso($countryIso);

        if (!$countryId) {
            throw new ModuleException("Country $countryIso does not exists");
        }

        self::$cache[$this->marketplaceId]['country'] = new Country($countryId, Context::getContext()->language->id);

        return self::$cache[$this->marketplaceId]['country'];
    }

    /**
     * @return Currency
     * @throws ModuleException
     */
    public function getMarketplaceCurrency()
    {
        if (isset(self::$cache[$this->marketplaceId]['currency'])) {
            return self::$cache[$this->marketplaceId]['currency'];
        }

        $marketplaceLocalization = self::getMarketplacesLocalization()[$this->marketplaceId];
        $currencyIso = $marketplaceLocalization['currencyIso'];
        $currencyId = Currency::getIdByIsoCode($currencyIso);

        if (!$currencyId) {
            throw new ModuleException("Currency $currencyIso does not exists");
        }

        $currency = new Currency($currencyId);

        // fix for already installed Currency
        // PrestaShop may have different currencies precision than Allegro
        $currency->decimals = ($marketplaceLocalization['currencyPrecision'] > 0 ? 1 : 0);
        $currency->precision = $marketplaceLocalization['currencyPrecision'];

        if (XAllegroConfiguration::get('AUCTION_MARKETPLACE_CONVERSION_RATE') === 'VALUE') {
            $conversionRate = json_decode(XAllegroConfiguration::get('AUCTION_MARKETPLACE_CONVERSION_RATE_VALUE'), true);
            if (!empty($conversionRate[$currency->id])) {
                $currency->conversion_rate = $conversionRate[$currency->id];
            }
        }

        // fix for empty conversion_rate OR default currency
        if (empty($currency->conversion_rate) || $currency->id === (int)Configuration::get('PS_CURRENCY_DEFAULT')) {
            $currency->conversion_rate = 1;
        }

        self::$cache[$this->marketplaceId]['currency'] = $currency;

        return self::$cache[$this->marketplaceId]['currency'];
    }

    /**
     * @return Language
     */
    public function getMarketplaceLanguage()
    {
        if (isset(self::$cache[$this->marketplaceId]['language'])) {
            return self::$cache[$this->marketplaceId]['language'];
        }

        $marketplaceLocalization = self::getMarketplacesLocalization()[$this->marketplaceId];
        $languageIso = $marketplaceLocalization['localizationIso'];

        self::$cache[$this->marketplaceId]['language'] = new Language(Language::getIdByIso($languageIso));

        return self::$cache[$this->marketplaceId]['language'];
    }

    /**
     * @return array
     */
    public static function getMarketplacesLocalization()
    {
        /** @var array $marketplaces */
        static $marketplaces = null;

        if ($marketplaces !== null) {
            return $marketplaces;
        }

        foreach (Marketplace::toArray() as $key => $value) {
            $marketplaces[$value] = [
                'localizationIso' => MarketplaceLocalization::$key()->getValue(),
                'countryIso' => MarketplaceCountry::$key()->getValue(),
                'currencyIso' => MarketplaceCurrency::$key()->getValue(),
                'currencyPrecision' => MarketplaceCurrencyPrecision::$key()->getValue()
            ];
        }

        return $marketplaces;
    }
}
