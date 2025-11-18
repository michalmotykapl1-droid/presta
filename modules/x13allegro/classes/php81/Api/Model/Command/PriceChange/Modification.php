<?php

namespace x13allegro\Api\Model\Command\PriceChange;

use JsonSerializable;

final class Modification implements JsonSerializable
{
    /** @var string */
    public $type;

    /** @var \x13allegro\Api\Model\Price */
    public $price;

    /** @var \x13allegro\Api\Model\Price */
    public $value;

    /** @var string */
    public $percentage;

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        $type = [
            'type' => $this->type
        ];

        switch ($this->type)
        {
            case ModificationType::INCREASE_PRICE:
            case ModificationType::DECREASE_PRICE:
                return $type + [
                    'value' => $this->value
                ];

            case ModificationType::INCREASE_PERCENTAGE:
            case ModificationType::DECREASE_PERCENTAGE:
                return $type + [
                    'percentage' => (string)$this->percentage
                ];

            default:
                return $type + [
                    'price' => $this->price
                ];
        }
    }
}
