<?php

namespace x13allegro\Api\Model\Order;

use JsonSerializable;

final class ParcelTrackingNumber implements JsonSerializable
{
    /** @var string */
    public $carrierId;

    /** @var string */
    public $waybill;

    /** @var string */
    public $carrierName;

    /** @var LineItem[] */
    public $lineItems;

    /** @var array */
    private $lineItemsCollection = [];

    /**
     * @param string $id
     * @return $this
     */
    public function lineItem($id)
    {
        if (!array_key_exists($id, $this->lineItemsCollection)) {
            $this->lineItemsCollection[$id] = new LineItem($id);
        }

        return $this;
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return array(
            'carrierId' => $this->carrierId,
            'waybill' => $this->waybill,
            'carrierName' => $this->carrierName,
            'lineItems' => array_values($this->lineItemsCollection)
        );
    }
}
