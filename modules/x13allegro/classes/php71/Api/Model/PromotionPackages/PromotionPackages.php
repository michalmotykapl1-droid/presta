<?php

namespace x13allegro\Api\Model\PromotionPackages;

use JsonSerializable;

final class PromotionPackages implements JsonSerializable
{
    /** @var PromotionPackagesModification[] */
    public $modifications = [];

    /**
     * @param string $modificationType
     * @param string $packageType
     * @param string $packageId
     * @return $this
     */
    public function addModification($modificationType, $packageType, $packageId)
    {
        $this->modifications[] = new PromotionPackagesModification($modificationType, $packageType, $packageId);

        return $this;
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return [
            'modifications' => $this->modifications
        ];
    }
}
