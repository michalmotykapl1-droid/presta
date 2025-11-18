<?php

namespace x13allegro\Api\Model\Offers;

use JsonSerializable;

final class AfterSalesServices implements JsonSerializable
{
    /** @var AfterSalesServices\ImpliedWarranty */
    public $impliedWarranty;

    /** @var AfterSalesServices\ReturnPolicy */
    public $returnPolicy;

    /** @var AfterSalesServices\Warranty */
    public $warranty;

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return [
            'impliedWarranty' => (is_object($this->impliedWarranty) && $this->impliedWarranty->id ? $this->impliedWarranty : null),
            'returnPolicy' => (is_object($this->returnPolicy) && $this->returnPolicy->id ? $this->returnPolicy : null),
            'warranty' => (is_object($this->warranty) && $this->warranty->id ? $this->warranty : null)
        ];
    }
}
