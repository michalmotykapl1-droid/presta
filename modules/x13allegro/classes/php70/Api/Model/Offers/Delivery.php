<?php

namespace x13allegro\Api\Model\Offers;

use JsonSerializable;
use x13allegro\Api\Model\OfferPropertyDelete;

final class Delivery implements JsonSerializable
{
    /** @var string */
    public $additionalInfo;

    /** @var string */
    public $handlingTime;

    /** @var ShippingRate */
    public $shippingRates;

    /** @var \x13allegro\Api\Model\DateTime */
    public $shipmentDate;

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        $output = [];

        if (!empty($this->additionalInfo)) {
            $output['additionalInfo'] = $this->additionalInfo;
        }
        if (!empty($this->handlingTime)) {
            $output['handlingTime'] = $this->handlingTime;
        }
        if (!empty($this->shippingRates)) {
            $output['shippingRates'] = $this->shippingRates;
        }
        if (!empty($this->shipmentDate)) {
            $output['shipmentDate'] = ($this->shipmentDate instanceof OfferPropertyDelete ? null : $this->shipmentDate);
        }

        return (!empty($output) ? $output : null);
    }
}
