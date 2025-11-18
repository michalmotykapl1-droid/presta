<?php

namespace x13allegro\Api\Model\Offers;

use JsonSerializable;
use x13allegro\Api\Model\OfferPropertyDelete;

final class ProductSet implements JsonSerializable
{
    /** @var ProductSet\Product */
    public $product;

    /** @var ProductSet\ResponsiblePerson */
    public $responsiblePerson;

    /** @var ProductSet\ResponsibleProducer */
    public $responsibleProducer;

    /** @var ProductSet\SafetyInformation */
    public $safetyInformation;

    /** @var bool */
    public $marketedBeforeGPSRObligation;

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        $output = [];

        if ($this->product) {
            $output['product'] = $this->product;
        }

        if ($this->responsiblePerson instanceof OfferPropertyDelete) {
            $output['responsiblePerson'] = null;
        }
        else if (is_object($this->responsiblePerson) && !empty($this->responsiblePerson->id)) {
            $output['responsiblePerson'] = $this->responsiblePerson;
        }

        if (is_object($this->responsibleProducer) && !empty($this->responsibleProducer->id)) {
            $output['responsibleProducer'] = $this->responsibleProducer;
        }

        if (is_object($this->safetyInformation) && !empty($this->safetyInformation->type)) {
            $output['safetyInformation'] = $this->safetyInformation;
        }

        if (isset($this->marketedBeforeGPSRObligation)) {
            $output['marketedBeforeGPSRObligation'] = $this->marketedBeforeGPSRObligation;
        }

        return $output;
    }
}
