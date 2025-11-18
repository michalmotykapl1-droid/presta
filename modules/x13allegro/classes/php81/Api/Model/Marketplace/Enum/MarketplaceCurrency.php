<?php

namespace x13allegro\Api\Model\Marketplace\Enum;

use x13allegro\Component\Enum;

/**
 * @method static MarketplaceCurrency ALLEGRO_PL()
 * @method static MarketplaceCurrency ALLEGRO_CZ()
 * @method static MarketplaceCurrency ALLEGRO_SK()
 * @method static MarketplaceCurrency ALLEGRO_HU()
 */
final class MarketplaceCurrency extends Enum
{
    const ALLEGRO_PL = 'PLN';
    const ALLEGRO_CZ = 'CZK';
    const ALLEGRO_SK = 'EUR';
    const ALLEGRO_HU = 'HUF';
}
