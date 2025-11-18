<?php

namespace x13allegro\Api\Model\Order;

final class Fulfillment
{
    /** @var string */
    public $status;

    /** @var ShipmentSummary */
    public $shipmentSummary;
}
