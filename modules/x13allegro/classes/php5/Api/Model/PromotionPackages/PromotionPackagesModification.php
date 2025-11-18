<?php

namespace x13allegro\Api\Model\PromotionPackages;

final class PromotionPackagesModification
{
    /** @var string */
    public $modificationType;

    /** @var string */
    public $packageType;

    /** @var string */
    public $packageId;

    /**
     * @param string $modificationType
     * @param string $packageType
     * @param string $packageId
     */
    public function __construct($modificationType, $packageType, $packageId)
    {
        $this->modificationType = $modificationType;
        $this->packageType = $packageType;
        $this->packageId = $packageId;
    }
}
