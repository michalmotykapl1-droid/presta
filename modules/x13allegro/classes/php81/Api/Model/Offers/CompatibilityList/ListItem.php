<?php

namespace x13allegro\Api\Model\Offers\CompatibilityList;

use JsonSerializable;

final class ListItem implements JsonSerializable
{
    /** @var string */
    public $id;

    /** @var string */
    public $type;

    /** @var string */
    public $text;

    /** @var ListItemAdditionalInfo[] */
    public $additionalInfo;

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        $fields = [
            'type' => $this->type,
            'text' => $this->text
        ];

        if ($this->type == ItemType::ID) {
            $fields['id'] = $this->id;
            $fields['additionalInfo'] = $this->additionalInfo;
        }

        return $fields;
    }
}
