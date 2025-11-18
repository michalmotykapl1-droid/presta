<?php

namespace x13allegro\Api\Model\Offers\Enum;

use x13allegro\Component\Enum;

/**
 * @method static SellingModeType BUY_NOW()
 * @method static SellingModeType AUCTION()
 */
final class SellingModeType extends Enum
{
    const BUY_NOW = 'BUY_NOW';
    const AUCTION = 'AUCTION';
}
