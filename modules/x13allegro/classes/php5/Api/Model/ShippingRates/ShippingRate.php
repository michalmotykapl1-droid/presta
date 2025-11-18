<?php

namespace x13allegro\Api\Model\ShippingRates;

use DeepCopy\DeepCopy;
use JsonSerializable;

final class ShippingRate implements JsonSerializable
{
    /** @var string */
    public $id;

    /** @var string */
    public $name;

    /** @var Rate[] */
    public $rates;

    /** @var \x13allegro\Api\Model\DateTime */
    public $lastModified;

    /** @var array */
    private $ratesCollection = array();

    /**
     * @param string $deliveryMethod
     * @param $firstItemRate
     * @param $nextItemRate
     * @param int $maxQuantityPerPackage
     * @return $this
     */
    public function rate($deliveryMethod, $firstItemRate, $nextItemRate, $maxQuantityPerPackage)
    {
        $rate = (new DeepCopy(true))->copy($this->rates[0]);
        $rate->deliveryMethod($deliveryMethod);
        $rate->firstItemRate->amount = $firstItemRate;
        $rate->nextItemRate->amount = $nextItemRate;
        $rate->maxQuantityPerPackage = $maxQuantityPerPackage;

        $this->ratesCollection[] = $rate;

        return $this;
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return array(
            'id' => $this->id,
            'name' => $this->name,
            'rates' => $this->ratesCollection,
            'lastModified' => $this->lastModified
        );
    }
}
