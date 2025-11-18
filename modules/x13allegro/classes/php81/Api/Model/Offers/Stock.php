<?php

namespace x13allegro\Api\Model\Offers;

use JsonSerializable;

final class Stock implements JsonSerializable
{
    /** @var int */
    public $available;

    /** @var string */
    public $unit;

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        $output = [];

        if (!empty($this->available)) {
            $output['available'] = $this->available;
        }
        if (!empty($this->unit)) {
            $output['unit'] = $this->unit;
        }

        return (!empty($output) ? $output : null);
    }
}
