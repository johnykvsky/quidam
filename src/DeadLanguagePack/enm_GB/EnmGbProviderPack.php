<?php

declare(strict_types = 1);

namespace Quidam\DeadLanguagePack\enm_GB;

use Quidam\Core\Locale\Locale;
use Quidam\Definitions\Extension\AddressExtensionInterface;
use Quidam\Definitions\Extension\CompanyExtensionInterface;
use Quidam\Definitions\Extension\DateTimeExtensionInterface;
use Quidam\Definitions\Extension\PaymentExtensionInterface;
use Quidam\Definitions\Extension\PersonExtensionInterface;
use Quidam\Definitions\Locale\LocaleInterface;
use Quidam\ProviderPack\ProviderPackInterface;

/**
 * Middle English language pack (enm_GB) - a pack for fun: Chaucer's England (bynames, inn signs, feast days and
 * regnal years, pounds, shillings and pence), with the country conventions of the United Kingdom:
 *
 *   $generator = Quidam::create()->withProvider(new EnmGbProviderPack());
 */
final readonly class EnmGbProviderPack implements ProviderPackInterface
{
    public function all(): array
    {
        return [
            LocaleInterface::class => self::locale(),
            PersonExtensionInterface::class => Person::class,
            AddressExtensionInterface::class => Address::class,
            DateTimeExtensionInterface::class => DateTime::class,
            CompanyExtensionInterface::class => Company::class,
            PaymentExtensionInterface::class => Payment::class,
        ];
    }

    /**
     * United Kingdom: +44 with trunk prefix 0, GBP (written in pounds, shillings and pence - see Payment), dates
     * "12/10/2026"; long dates are medieval - see DateTime::longDate()
     */
    public static function locale(): Locale
    {
        return new Locale(
            countryCode: 'GB',
            languageCode: 'enm',
            callingCode: '44',
            currencyCode: 'GBP',
            currencySymbol: '£',
            pricePattern: '{symbol}{amount}',
            dateFormat: 'd/m/Y',
        );
    }
}
