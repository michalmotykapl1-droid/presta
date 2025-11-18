<?php

namespace x13allegro\Api\Model\PromotionPackages\Enum;

use x13allegro\Component\Enum;

final class PackageModificationType extends Enum
{
    const CHANGE = 'CHANGE';
    const REMOVE_WITH_END_OF_CYCLE = 'REMOVE_WITH_END_OF_CYCLE';
    const REMOVE_NOW = 'REMOVE_NOW';
}
