<?php

namespace x13allegro\Api\Model\Order\Enum;

use x13allegro\Component\Enum;

final class CheckoutFormStatus extends Enum
{
    const BOUGHT = 'BOUGHT';                                // purchase without checkout form filled in
    const FILLED_IN = 'FILLED_IN';                          // checkout form filled in but payment is not completed yet so data could still change
    const READY_FOR_PROCESSING = 'READY_FOR_PROCESSING';    // payment completed, purchase is ready for processing
    const CANCELLED = 'CANCELLED';                          // purchase cancelled by buyer
}
