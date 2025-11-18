<?php

namespace x13allegro\Api\Model\Offers;

use JsonSerializable;
use x13allegro\Api\Model\Offers\CompatibilityList\ListItemType;

final class CompatibilityList implements JsonSerializable
{
    /** @var string */
    public $id;

    /** @var string */
    public $type;

    /** @var CompatibilityList\ListItem[] */
    public $items;

    /**
     * @return array
     */
    private function getProductBasedItems()
    {
        $items = [];
        foreach ($this->items as $item) {
            $itemObj = new \StdClass();
            $itemObj->text = $item->text;

            $items[] = $itemObj;
        }

        return $items;
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        $fields = [
            'type' => $this->type
        ];

        if ($this->type == ListItemType::PRODUCT_BASED) {
            $fields['id'] = $this->id;
            $fields['items'] = $this->getProductBasedItems();
        } else {
            $fields['items'] = $this->items;
        }

        return $fields;
    }
}
