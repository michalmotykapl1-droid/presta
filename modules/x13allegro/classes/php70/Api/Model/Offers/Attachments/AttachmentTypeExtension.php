<?php

namespace x13allegro\Api\Model\Offers\Attachments;

use x13allegro\Component\Enum;

/**
 * @method static AttachmentTypeExtension MANUAL()
 * @method static AttachmentTypeExtension SPECIAL_OFFER_RULES()
 * @method static AttachmentTypeExtension COMPETITION_RULES()
 * @method static AttachmentTypeExtension BOOK_EXCERPT()
 * @method static AttachmentTypeExtension USER_MANUAL()
 * @method static AttachmentTypeExtension INSTALLATION_INSTRUCTIONS()
 * @method static AttachmentTypeExtension GAME_INSTRUCTIONS()
 * @method static AttachmentTypeExtension ENERGY_LABEL()
 * @method static AttachmentTypeExtension PRODUCT_INFORMATION_SHEET()
 * @method static AttachmentTypeExtension TIRE_LABEL()
 * @method static AttachmentTypeExtension SAFETY_INFORMATION_MANUAL()
 */
final class AttachmentTypeExtension extends Enum
{
    static $backedEnum = true;

    const MANUAL = ['pdf'];
    const SPECIAL_OFFER_RULES = ['pdf'];
    const COMPETITION_RULES = ['pdf'];
    const BOOK_EXCERPT = ['pdf'];
    const USER_MANUAL = ['pdf'];
    const INSTALLATION_INSTRUCTIONS = ['pdf'];
    const GAME_INSTRUCTIONS = ['pdf'];
    const ENERGY_LABEL = ['jpg', 'jpeg', 'png'];
    const PRODUCT_INFORMATION_SHEET = ['pdf'];
    const TIRE_LABEL = ['jpg', 'jpeg', 'png'];
    const SAFETY_INFORMATION_MANUAL = ['pdf', 'jpg', 'jpeg', 'png'];
}
