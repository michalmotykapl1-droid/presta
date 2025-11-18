<?php

namespace x13allegro\Api\Model\Offers;

use x13allegro\Api\Model\OfferPropertyDelete;

final class OfferUpdate extends OfferProduct
{
    /**
     * @param string $offerId
     */
    public function __construct($offerId)
    {
        $this->id = $offerId;
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        $output = [];

        $fields = [
            'additionalMarketplaces',
            'additionalServices',
            'afterSalesServices',
            'delivery',
            'description',
            'discounts',
            'external',
            'images',
            'name',
            'category',
            'parameters',
            'payments',
            'productSet',
            'publication',
            'sellingMode',
            'sizeTable',
            'stock',
            'taxSettings'
        ];

        foreach ($fields as $field) {
            switch ($field) {
                // nullable properties
                case 'additionalServices':
                case 'discounts':
                case 'external':
                case 'sizeTable':
                    if ($this->{$field} instanceof OfferPropertyDelete) {
                        $output[$field] = null;
                    }
                    else if (is_object($this->{$field})) {
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
