<?php

namespace x13allegro\Api\Model\Marketplace\Enum;

use x13allegro\Component\Enum;

/**
 * @method static MarketplaceOfferUrl ALLEGRO_PL()
 * @method static MarketplaceOfferUrl ALLEGRO_CZ()
 * @method static MarketplaceOfferUrl ALLEGRO_SK()
 * @method static MarketplaceOfferUrl ALLEGRO_HU()
 */
final class MarketplaceOfferUrl extends Enum
{
    const ALLEGRO_PL = 'https://allegro.pl{sandbox}/oferta/{offerId}';
    const ALLEGRO_CZ = 'https://allegro.cz{sandbox}/nabidka/{offerId}';
    const ALLEGRO_SK = 'https://allegro.sk{sandbox}/ponuka/{offerId}';
    const ALLEGRO_HU = 'https://allegro.hu{sandbox}/ajanlat/{offerId}';
}