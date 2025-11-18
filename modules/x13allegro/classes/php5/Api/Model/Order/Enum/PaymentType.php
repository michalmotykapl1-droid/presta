<?php

namespace x13allegro\Api\Model\Order\Enum;

use x13allegro\Component\Enum;

final class PaymentType extends Enum
{
    const ONLINE = 'ONLINE';
    const EXTENDED_TERM = 'EXTENDED_TERM';
    const SPLIT_PAYMENT = 'SPLIT_PAYMENT';
    const CASH_ON_DELIVERY = 'CASH_ON_DELIVERY';
}
