<?php

declare(strict_types = 1);

namespace Quidam\DeadLanguagePack\en_XS;

use Quidam\Core\Locale\Locale;
use Quidam\Definitions\Extension\AddressExtensionInterface;
use Quidam\Definitions\Extension\CompanyExtensionInterface;
use Quidam\Definitions\Extension\DateTimeExtensionInterface;
use Quidam\Definitions\Extension\PaymentExtensionInterface;
use Quidam\Definitions\Extension\PersonExtensionInterface;
use Quidam\Definitions\Locale\LocaleInterface;
use Quidam\ProviderPack\ProviderPackInterface;

/**
 * Shakespearean English language pack (en_XS) - a pack for fun: Shakespeare's England and the worlds of his plays
 * (players' names, signs, Old Style dates, pounds, shillings and pence, insults). Early Modern English has no ISO code
 * of its own - XS is a user-assigned country code, the locale is the United Kingdom's:
 *
 *   $generator = Quidam::create()->withProvider(new EnXsProviderPack());
 */
final readonly class EnXsProviderPack implements ProviderPackInterface
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
     * "12/10/2026"; long dates are Old Style - see DateTime::longDate()
     */
    public static function locale(): Locale
    {
        return new Locale(
            countryCode: 'GB',
            languageCode: 'en',
            callingCode: '44',
            currencyCode: 'GBP',
            currencySymbol: '£',
            pricePattern: '{symbol}{amount}',
            dateFormat: 'd/m/Y',
        );
    }
}
