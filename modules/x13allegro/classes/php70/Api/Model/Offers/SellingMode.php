<?php

namespace x13allegro\Api\Model\Offers;

use x13allegro\Api\Model\Offers\Enum\SellingModeType;
use JsonSerializable;

final class SellingMode implements JsonSerializable
{
    /** @var string */
    public $format;

    /** @var \x13allegro\Api\Model\Price */
    public $price;

    /** @var \x13allegro\Api\Model\Price */
    public $startingPrice;

    /** @var \x13allegro\Api\Model\Price */
    public $minimalPrice;

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        $output = [];
        $fields = [
            'format',
            'price',
            'startingPrice',
            'minimalPrice'
        ];

        foreach ($fields as $field) {
            switch ($field) {
                case 'startingPrice':
                case 'minimalPrice':
                    if (!empty($this->{$field})
                        && $this->format == SellingModeType::AUCTION
                        && $this->{$field}->amount > 0
                    ) {
                        $output[$field] = $this->{$field};
                    }
                    break;

                case 'price':
                    if (!empty($this->{$field}) && $this->{$field}->amount > 0) {
                        $output[$field] = $this->{$field};
                    }
                    break;

                default:
                    if (!empty($this->{$field})) {
                        $output[$field] = $this->{$field};
                    }
            }
        }

        return $output;
    }
}
