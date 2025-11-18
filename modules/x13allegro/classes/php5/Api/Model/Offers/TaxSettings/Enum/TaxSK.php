<?php

namespace x13allegro\Api\Model\Offers\TaxSettings\Enum;

use x13allegro\Component\Enum;

/**
 * @method static TaxSK TAX_23()
 * @method static TaxSK TAX_19()
 * @method static TaxSK TAX_5()
 * @method static TaxSK TAX_EXEMPT()
 */
final class TaxSK extends Enum
{
    static $backedEnum = true;

    const TAX_23 = '23.00';
    const TAX_19 = '19.00';
    const TAX_5 = '5.00';
    const TAX_EXEMPT = 'EXEMPT';

    /**
     * @return string[]
     */
    public static function translateValues()
    {
        return [
            self::TAX_23 => '23%',
            self::TAX_19 => '19%',
            self::TAX_5 => '5%',
            self::TAX_EXEMPT => 'ZW'
        ];
    }
}
