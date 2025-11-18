<?php

namespace x13allegro\Api\Model\Order;

final class LineItem
{
    /** @var string */
    public $id;

    /**
     * @param string $id
     */
    public function __construct($id = null)
    {
        $this->id = $id;
    }
}
