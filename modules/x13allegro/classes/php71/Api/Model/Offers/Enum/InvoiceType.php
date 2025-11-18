<?php

namespace x13allegro\Api\Model\Offers\Enum;

use x13allegro\Component\Enum;

final class InvoiceType extends Enum
{
    const VAT = 'faktura VAT';
    const VAT_MARGIN = 'faktura VAT marża';
    const WITHOUT_VAT = 'faktura bez VAT';
    const NO_INVOICE = 'nie wystawiam faktury';
}
