<?php

namespace x13allegro\Api\Model\Command\PriceChange;

final class ModificationType
{
    const FIXED_PRICE = 'FIXED_PRICE';
    const INCREASE_PRICE = 'INCREASE_PRICE';
    const DECREASE_PRICE = 'DECREASE_PRICE';
    const INCREASE_PERCENTAGE = 'INCREASE_PERCENTAGE';
    const DECREASE_PERCENTAGE = 'DECREASE_PERCENTAGE';
}
