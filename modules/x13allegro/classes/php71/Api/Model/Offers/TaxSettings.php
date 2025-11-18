<?php

namespace x13allegro\Api\Model\Offers;

use JsonSerializable;

final class TaxSettings implements JsonSerializable
{
    /** @var TaxSettings\Rate[] */
    public $rates;

    /** @var string */
    public $subject;

    /** @var string */
    public $exemption;

    /**
     * @param string $rate
     * @param string $countryCode
     * @return $this
     */
    public function addTaxRate($rate, $countryCode)
    {
        $taxRate = new TaxSettings\Rate();
        $taxRate->rate = $rate;
        $taxRate->countryCode = $countryCode;

        $this->rates[$countryCode] = $taxRate;

        return $this;
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        $output = [
            'rates' => array_values($this->rates)
        ];

        if (!empty($this->subject)) {
            $output['subject'] = $this->subject;
        }
        if (!empty($this->exemption)) {
            $output['exemption'] = $this->exemption;
        }

        return $output;
    }
}
