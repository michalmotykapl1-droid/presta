<?php

namespace x13allegro\Api\Model;

use JsonSerializable;

final class Price implements JsonSerializable
{
    /** @var float */
    public $amount;

    /** @var string */
    public $currency;

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return array(
            'amount' => number_format((float)str_replace(',', '.', $this->amount), 2, '.', ''),
            'currency' => $this->currency
        );
    }
}
