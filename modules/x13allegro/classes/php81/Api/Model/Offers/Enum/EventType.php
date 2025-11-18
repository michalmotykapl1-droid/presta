<?php

namespace x13allegro\Api\Model\Offers\Enum;

use x13allegro\Component\Enum;

final class EventType extends Enum
{
    const ACTIVATED = 'OFFER_ACTIVATED';
    const CHANGED = 'OFFER_CHANGED';
    const ENDED = 'OFFER_ENDED';
    const STOCK_CHANGED = 'OFFER_STOCK_CHANGED';
    const PRICE_CHANGED = 'OFFER_PRICE_CHANGED';
    const ARCHIVED = 'OFFER_ARCHIVED';
    const BID_PLACED = 'OFFER_BID_PLACED';
    const BID_CANCELED = 'OFFER_BID_CANCELED';
    const VISIBILITY_CHANGED = 'OFFER_VISIBILITY_CHANGED';
}
