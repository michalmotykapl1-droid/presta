<?php

namespace x13allegro\Api\Model\Marketplace\Enum;

use x13allegro\Component\Enum;

/**
 * @method static MarketplacePublicationStatus APPROVED()
 * @method static MarketplacePublicationStatus REFUSED()
 * @method static MarketplacePublicationStatus IN_PROGRESS()
 * @method static MarketplacePublicationStatus NOT_REQUESTED()
 * @method static MarketplacePublicationStatus PENDING()
 */
final class MarketplacePublicationStatus extends Enum
{
    const APPROVED = 'APPROVED';
    const REFUSED = 'REFUSED';
    const IN_PROGRESS = 'IN_PROGRESS';
    const NOT_REQUESTED = 'NOT_REQUESTED';
    const PENDING = 'PENDING';

    /**
     * @return array
     */
    public static function translateValues()
    {
        return [
            self::APPROVED => 'zatwierdzona',
            self::REFUSED => 'odrzucona',
            self::IN_PROGRESS => 'przetwarzana',
            self::NOT_REQUESTED => 'nie zgłoszona',
            self::PENDING => 'w oczekiwaniu'
        ];
    }
}
