<?php

namespace x13allegro\Api\Model\Offers\TaxSettings;

use JsonSerializable;

final class Rate implements JsonSerializable
{
    /** @var string */
    public $rate;

    /** @var string */
    public $countryCode;

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return [
            'rate' => (is_numeric($this->rate) ? number_format((float)str_replace(',', '.', $this->rate), 2, '.', '') : $this->rate),
            'countryCode' => $this->countryCode
        ];
    }
}
