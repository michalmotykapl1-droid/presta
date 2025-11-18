<?php

namespace x13allegro\Api\Model\Marketplace\Enum;

use x13allegro\Api\XAllegroApi;
use x13allegro\Component\Enum;

/**
 * @method static Marketplace ALLEGRO_PL()
 * @method static Marketplace ALLEGRO_CZ()
 * @method static Marketplace ALLEGRO_SK()
 * @method static Marketplace ALLEGRO_HU()
 */
final class Marketplace extends Enum
{
    static $backedEnum = true;

    const ALLEGRO_PL = XAllegroApi::MARKETPLACE_PL;
    const ALLEGRO_CZ = XAllegroApi::MARKETPLACE_CZ;
    const ALLEGRO_SK = XAllegroApi::MARKETPLACE_SK;
    const ALLEGRO_HU = XAllegroApi::MARKETPLACE_HU;

    /**
     * @return string[]
     */
    public static function translateValues()
    {
        return [
            self::ALLEGRO_PL => 'Polska - allegro.pl',
            self::ALLEGRO_CZ => 'Czechy - allegro.cz',
            self::ALLEGRO_SK => 'Słowacja - allegro.sk',
            self::ALLEGRO_HU => 'Węgry - allegro.hu'
        ];
    }
}
