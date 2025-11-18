<?php

namespace x13allegro\Api\Model\Offers\TaxSettings\Enum;

use x13allegro\Component\Enum;

/**
 * @method static TaxPL TAX_23()
 * @method static TaxPL TAX_8()
 * @method static TaxPL TAX_5()
 * @method static TaxPL TAX_EXEMPT()
 */
final class TaxPL extends Enum
{
    static $backedEnum = true;

    const TAX_23 = '23.00';
    const TAX_8 = '8.00';
    const TAX_5 = '5.00';
    const TAX_EXEMPT = 'EXEMPT';

    /**
     * @return string[]
     */
    public static function translateValues()
    {
        return [
            self::TAX_23 => '23%',
            self::TAX_8 => '8%',
            self::TAX_5 => '5%',
            self::TAX_EXEMPT => 'ZW'
        ];
    }
}
