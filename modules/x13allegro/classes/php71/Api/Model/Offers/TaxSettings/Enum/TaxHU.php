<?php

namespace x13allegro\Api\Model\Offers\TaxSettings\Enum;

use x13allegro\Component\Enum;

/**
 * @method static TaxHU TAX_27()
 * @method static TaxHU TAX_18()
 * @method static TaxHU TAX_5()
 * @method static TaxHU TAX_EXEMPT()
 */
final class TaxHU extends Enum
{
    static $backedEnum = true;

    const TAX_27 = '27.00';
    const TAX_18 = '18.00';
    const TAX_5 = '5.00';
    const TAX_EXEMPT = 'EXEMPT';

    /**
     * @return string[]
     */
    public static function translateValues()
    {
        return [
            self::TAX_27 => '27%',
            self::TAX_18 => '18%',
            self::TAX_5 => '5%',
            self::TAX_EXEMPT => 'ZW'
        ];
    }
}
