<?php

namespace x13allegro\Api\Model\Order\Enum;

use x13allegro\Component\Enum;

final class EventType extends Enum
{
    const BOUGHT = 'BOUGHT';
    const FILLED_IN = 'FILLED_IN';
    const READY_FOR_PROCESSING = 'READY_FOR_PROCESSING';
    const FULFILLMENT_STATUS_CHANGED = 'FULFILLMENT_STATUS_CHANGED';
    const AUTO_CANCELLED = 'AUTO_CANCELLED';
    const BUYER_CANCELLED = 'BUYER_CANCELLED';
}
