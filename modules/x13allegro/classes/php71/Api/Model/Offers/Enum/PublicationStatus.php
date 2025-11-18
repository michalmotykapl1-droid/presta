<?php

namespace x13allegro\Api\Model\Offers\Enum;

use x13allegro\Component\Enum;

final class PublicationStatus extends Enum
{
    const INACTIVE = 'INACTIVE';
    const ACTIVATING = 'ACTIVATING';
    const ACTIVE = 'ACTIVE';
    const ENDED = 'ENDED';
}
