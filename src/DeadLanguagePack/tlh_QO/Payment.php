<?php

declare(strict_types = 1);

namespace Quidam\DeadLanguagePack\tlh_QO;

use Quidam\Core\Payment as CorePayment;

/**
 * Darsek - the currency of the Empire, under XXX (ISO 4217 "no currency"), in whole coins
 */
class Payment extends CorePayment
{
    /** @var array<string, string> */
    protected const array CURRENCIES = [...parent::CURRENCIES, 'XXX' => 'Darsek'];

    /** @var array<string, int> */
    protected const array CURRENCY_DECIMALS = [...parent::CURRENCY_DECIMALS, 'XXX' => 0];
}
