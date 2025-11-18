<?php

namespace x13allegro\Api\Model\Offers\Attachments;

use x13allegro\Component\Enum;

/**
 * @method static AttachmentType MANUAL()
 * @method static AttachmentType SPECIAL_OFFER_RULES()
 * @method static AttachmentType COMPETITION_RULES()
 * @method static AttachmentType BOOK_EXCERPT()
 * @method static AttachmentType USER_MANUAL()
 * @method static AttachmentType INSTALLATION_INSTRUCTIONS()
 * @method static AttachmentType GAME_INSTRUCTIONS()
 * @method static AttachmentType ENERGY_LABEL()
 * @method static AttachmentType PRODUCT_INFORMATION_SHEET()
 * @method static AttachmentType TIRE_LABEL()
 * @method static AttachmentType SAFETY_INFORMATION_MANUAL()
 */
final class AttachmentType extends Enum
{
    const MANUAL = 'MANUAL';
    const SPECIAL_OFFER_RULES = 'SPECIAL_OFFER_RULES';
    const COMPETITION_RULES = 'COMPETITION_RULES';
    const BOOK_EXCERPT = 'BOOK_EXCERPT';
    const USER_MANUAL = 'USER_MANUAL';
    const INSTALLATION_INSTRUCTIONS = 'INSTALLATION_INSTRUCTIONS';
    const GAME_INSTRUCTIONS = 'GAME_INSTRUCTIONS';
    const ENERGY_LABEL = 'ENERGY_LABEL';
    const PRODUCT_INFORMATION_SHEET = 'PRODUCT_INFORMATION_SHEET';
    const TIRE_LABEL = 'TIRE_LABEL';
    const SAFETY_INFORMATION_MANUAL = 'SAFETY_INFORMATION_MANUAL';

    /**
     * @return string[]
     */
    public static function translateValues()
    {
        return [
            self::MANUAL => 'Poradnik',
            self::SPECIAL_OFFER_RULES => 'Regulamin promocji',
            self::COMPETITION_RULES => 'Regulamin konkursu',
            self::BOOK_EXCERPT => 'Fragment książki',
            self::USER_MANUAL => 'Instrukcja obsługi',
            self::INSTALLATION_INSTRUCTIONS => 'Instrukcja montażu',
            self::GAME_INSTRUCTIONS => 'Instrukcja gry',
            self::ENERGY_LABEL => 'Etykieta energetyczna',
            self::PRODUCT_INFORMATION_SHEET => 'Karta produktu',
            self::TIRE_LABEL => 'Etykieta opony',
            self::SAFETY_INFORMATION_MANUAL => 'Instrukcja dotycząca bezpieczeństwa'
        ];
    }
}
