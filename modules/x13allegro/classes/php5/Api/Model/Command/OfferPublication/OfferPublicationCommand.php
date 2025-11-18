<?php

namespace x13allegro\Api\Model\Command\OfferPublication;

use x13allegro\Api\Model\Command\CommandInterface;

final class OfferPublicationCommand implements CommandInterface
{
    /** @var Publication */
    public $publication;

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
