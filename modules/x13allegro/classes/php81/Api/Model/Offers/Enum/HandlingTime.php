<?php

namespace x13allegro\Api\Model\Offers\Enum;

use x13allegro\Component\Enum;

final class HandlingTime extends Enum
{
    const PT0S = 'natychmiast';
    const PT24H = '24h';
    const P2D = '2 dni';
    const P3D = '3 dni';
    const P4D = '4 dni';
    const P5D = '5 dni';
    const P7D = '7 dni';
    const P10D = '10 dni';
    const P14D = '14 dni';
    const P21D = '21 dni';
    const P30D = '30 dni';
    const P60D = '60 dni';
}
