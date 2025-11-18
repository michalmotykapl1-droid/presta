<?php

namespace x13allegro\Api\Model\Offers\ProductSet;

use x13allegro\Component\Enum;

final class SafetyInformationType extends Enum
{
    const NO_SAFETY_INFORMATION = 'NO_SAFETY_INFORMATION';
    const ATTACHMENTS = 'ATTACHMENTS';
    const TEXT = 'TEXT';

    /**
     * @return string[]
     */
    public static function translateValues()
    {
        return [
            self::NO_SAFETY_INFORMATION => 'Produkt nie posiada informacji o bezpieczeństwie',
            self::ATTACHMENTS => 'Dodaj informacje o bezpieczeństwie produktu w postaci załączników',
            self::TEXT => 'Dodaj informacje o bezpieczeństwie produktu w postaci opisu tekstowego'
        ];
    }
}
