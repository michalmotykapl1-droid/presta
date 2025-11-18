<?php

namespace x13allegro\Api\Model\ResponsiblePersons;

use JsonSerializable;
use stdClass;

final class Contact implements JsonSerializable
{
    /** @var string */
    public $email;

    /** @var string */
    public $phoneNumber;

    /** @var string */
    public $formUrl;

    /**
     * @return array|StdClass
     */
    public function jsonSerialize()
    {
        $output = [];

        if (!empty($this->email)) {
            $output['email'] = $this->email;
        }
        if (!empty($this->phoneNumber)) {
            $output['phoneNumber'] = $this->phoneNumber;
        }
        if (!empty($this->formUrl)) {
            $output['formUrl'] = $this->formUrl;
        }

        if (empty($output)) {
            return new StdClass();
        }

        return $output;
    }
}
