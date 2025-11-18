<?php

namespace x13allegro\Api\Model\Offers;

use JsonSerializable;

/**
 * @todo Refactor - move outside this namespace and separate to different files
 */
final class OfferTags implements JsonSerializable
{
    /** @var Tag[] */
    public $tags;

    /** @var array */
    private $tagsCollection = array();

    /**
     * @param string $id
     * @return $this
     */
    public function tag($id)
    {
        $this->tagsCollection[] = new Tag($id);

        return $this;
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return array(
            'tags' => $this->tagsCollection
        );
    }
}

final class Tag
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
