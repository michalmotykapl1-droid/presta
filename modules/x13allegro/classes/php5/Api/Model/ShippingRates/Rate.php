<?php

namespace x13allegro\Api\Model\ShippingRates;

final class Rate
{
    /** @var DeliveryMethod */
    public $deliveryMethod;

    /** @var int */
    public $maxQuantityPerPackage;

    /** @var \x13allegro\Api\Model\Price */
    public $firstItemRate;

    /** @var \x13allegro\Api\Model\Price */
    public $nextItemRate;

    /**
     * @param string $id
     * @return $this
     */
    public function deliveryMethod($id)
    {
        $this->deliveryMethod = new DeliveryMethod($id);

        return $this;
    }
}

final class DeliveryMethod
{
    /** @var string */
    public $id;

    /**
     * @param string $id
     */
    public function __construct($id)
    {
        $this->id = $id;
    }
}
