<?php

namespace x13allegro\Api\Model\Order\Enum;

use x13allegro\Component\Enum;

class FulfillmentStatus extends Enum
{
    const PROCESSING = 'W realizacji';
    const SUSPENDED = 'Wstrzymane';
    const READY_FOR_SHIPMENT = 'Do wysłania';
    const READY_FOR_PICKUP = 'Do odbioru';
    const SENT = 'Wysłane';
    const PICKED_UP = 'Odebrane';
    const CANCELLED = 'Anulowane';
    const RETURNED = 'Zwrócone';

    public static function values($onlyAllowedManually = false)
    {
        $values = parent::values();

        if ($onlyAllowedManually) {
            foreach (self::getNotAllowedManually() as $status) {
                unset($values[$status]);
            }
        }

        return $values;
    }

    /**
     * @return array
     */
    public static function toChoseList($onlyAllowedManually = false)
    {
        $choseList = parent::toChoseList();

        if ($onlyAllowedManually) {
            foreach (self::getNotAllowedManually() as $status) {
                unset($choseList[$status]);
            }
        }

        return $choseList;
    }

    /**
     * @return array
     */
    public static function getNotAllowedManually()
    {
        return [
            'RETURNED'
        ];
    }

    /**
     * @return array
     */
    public static function getUnsupportedEvents()
    {
        return [
            EventType::BOUGHT,
            EventType::FILLED_IN
        ];
    }
}
