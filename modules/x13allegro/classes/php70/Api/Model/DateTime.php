<?php

namespace x13allegro\Api\Model;

use DateTime as PhpDateTime;
use JsonSerializable;

final class DateTime extends PhpDateTime implements JsonSerializable
{
    /**
     * @return string
     */
    public function jsonSerialize()
    {
        return $this->format(self::ATOM);
    }
}
