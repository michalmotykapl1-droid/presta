<?php

namespace x13allegro\Api\Model\Command\QuantityChange;

use x13allegro\Api\Model\Command\CommandInterface;

final class QuantityChangeCommand implements CommandInterface
{
    /** @var Modification */
    public $modification;

    /** @var \x13allegro\Api\Model\Command\OfferCriteria[] */
    public $offerCriteria;

    /**
     * @return \x13allegro\Api\Model\Command\OfferCriteria
     */
    public function offerCriteria()
    {
        return $this->offerCriteria[0];
    }
}
