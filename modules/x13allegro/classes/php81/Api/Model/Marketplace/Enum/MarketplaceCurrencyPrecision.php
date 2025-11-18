<?php

namespace x13allegro\Api\Model\Marketplace\Enum;

use x13allegro\Component\Enum;

/**
 * @method static MarketplaceCurrencyPrecision ALLEGRO_PL()
 * @method static MarketplaceCurrencyPrecision ALLEGRO_CZ()
 * @method static MarketplaceCurrencyPrecision ALLEGRO_SK()
 * @method static MarketplaceCurrencyPrecision ALLEGRO_HU()
 */
final class MarketplaceCurrencyPrecision extends Enum
{
    const ALLEGRO_PL = 2;
    const ALLEGRO_CZ = 0;
    const ALLEGRO_SK = 2;
    const ALLEGRO_HU = 0;
}
