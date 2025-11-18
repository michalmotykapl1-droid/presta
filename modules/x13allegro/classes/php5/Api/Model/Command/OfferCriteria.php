<?php

namespace x13allegro\Api\Model\Command;

use JsonSerializable;

final class OfferCriteria implements JsonSerializable
{
    const TYPE_CONTAINS_OFFERS = 'CONTAINS_OFFERS';

    /** @var Offer[] */
    public $offers;

    /** @var string */
    public $type;

    /** @var array */
    private $offersCollection = array();

    /**
     * @param string|array $offers
     * @return $this
     */
    public function offers($offers)
    {
        if (!is_array($offers)) {
            $offers = array($offers);
        }

        foreach ($offers as $offer) {
            $this->offersCollection[] = new Offer($offer);
        }

        return $this;
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return array(
            'offers' => $this->offersCollection,
            'type' => $this->type
        );
    }
}

final class Offer
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
