<?php

namespace x13allegro\Api\Model\Offers\TaxSettings\Enum;

use x13allegro\Component\Enum;

/**
 * @method static TaxCZ TAX_21()
 * @method static TaxCZ TAX_15()
 * @method static TaxCZ TAX_10()
 * @method static TaxCZ TAX_EXEMPT()
 */
final class TaxCZ extends Enum
{
    static $backedEnum = true;

    const TAX_21 = '21.00';
    const TAX_15 = '15.00';
    const TAX_10 = '10.00';
    const TAX_EXEMPT = 'EXEMPT';

    /**
     * @return string[]
     */
    public static function translateValues()
    {
        return [
            self::TAX_21 => '21%',
            self::TAX_15 => '15%',
            self::TAX_10 => '10%',
            self::TAX_EXEMPT => 'ZW'
        ];
    }
}
