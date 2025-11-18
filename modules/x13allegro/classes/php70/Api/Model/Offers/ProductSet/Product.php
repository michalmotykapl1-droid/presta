<?php

namespace x13allegro\Api\Model\Offers\ProductSet;

use JsonSerializable;

final class Product implements JsonSerializable
{
    /** @var string */
    public $id;

    /** @var string */
    public $idType;

    /** @var string */
    public $name;

    /** @var \x13allegro\Api\Model\Offers\Category */
    public $category;

    /** @var array */
    public $parameters;

    /** @var array */
    public $images;

    /**
     * @param string $url
     * @return $this
     */
    public function image($url)
    {
        $this->images[] = $url;

        return $this;
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return array(
            'id' => $this->id,
            'idType' => $this->idType,
            'name' => $this->name,
            'category' => $this->category,
            'parameters' => $this->parameters,
            'images' => $this->images
        );
    }
}
