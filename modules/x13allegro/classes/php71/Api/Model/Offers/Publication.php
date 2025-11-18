<?php

namespace x13allegro\Api\Model\Offers;

use JsonSerializable;

final class Publication implements JsonSerializable
{
    /** @var string */
    public $duration;

    /** @var Marketplaces readonly property */
    public $marketplaces;

    /** @var string */
    public $status;

    /** @var string */
    public $startingAt;

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        $output = [];

        if (!empty($this->duration)) {
            $output['duration'] = $this->duration;
        }
        if (!empty($this->status)) {
            $output['status'] = $this->status;
        }
        if (!empty($this->startingAt)) {
            $output['startingAt'] = $this->startingAt;
        }

        return (!empty($output) ? $output : null);
    }
}
