<?php

namespace x13allegro\Api\Model;

final class Image
{
    /** @var string */
    public $url;

    /**
     * @param string $url
     */
    public function __construct($url = '')
    {
        $this->url = $url;
    }
}
