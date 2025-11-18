<?php

namespace x13allegro\Api\Model\Offers\ProductSet;

use JsonSerializable;

final class SafetyInformation implements JsonSerializable
{
    /** @var string */
    public $type;

    /** @var SafetyInformationAttachment[] */
    public $attachments;

    /** @var string */
    public $description;

    /**
     * @param string $attachmentId
     * @return $this
     */
    public function attachment($attachmentId)
    {
        $attachment = new SafetyInformationAttachment();
        $attachment->id = $attachmentId;

        $this->attachments[] = $attachment;

        return $this;
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        $output = [];

        if (!empty($this->type)) {
            $output['type'] = $this->type;
        }

        if ($this->type === SafetyInformationType::ATTACHMENTS) {
            $output['attachments'] = $this->attachments;
        }
        else if ($this->type === SafetyInformationType::TEXT) {
            $output['description'] = $this->description;
        }

        return $output;
    }
}
