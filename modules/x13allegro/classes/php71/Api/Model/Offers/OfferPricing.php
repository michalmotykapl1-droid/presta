<?php

namespace x13allegro\Api\Model\Offers;

final class OfferPricing
{
    /** @var string */
    public $id;

    /** @var Category */
    public $category;

    /** @var array */
    public $parameters;

    /** @var Promotion */
    public $promotion;

    /** @var Publication */
    public $publication;

    /** @var SellingMode */
    public $sellingMode;
}
