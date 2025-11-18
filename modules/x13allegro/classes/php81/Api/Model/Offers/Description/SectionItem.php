<?php

namespace x13allegro\Api\Model\Offers\Description;

use JsonSerializable;

final class SectionItem implements JsonSerializable
{
    /** @var string */
    public $type;

    /** @var string */
    public $content;

    /** @var string */
    public $url;

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        $fields = [
            'type' => $this->type
        ];

        if ($this->type == SectionItemType::TEXT) {
            $fields['content'] = $this->content;
        } else {
            $fields['url'] = $this->url;
        }

        return $fields;
    }
}
