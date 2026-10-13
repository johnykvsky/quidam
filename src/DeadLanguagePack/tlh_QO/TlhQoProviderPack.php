<?php

declare(strict_types = 1);

namespace Quidam\DeadLanguagePack\tlh_QO;

use Quidam\Core\Locale\Locale;
use Quidam\Definitions\Extension\AddressExtensionInterface;
use Quidam\Definitions\Extension\CompanyExtensionInterface;
use Quidam\Definitions\Extension\DateTimeExtensionInterface;
use Quidam\Definitions\Extension\PaymentExtensionInterface;
use Quidam\Definitions\Extension\PersonExtensionInterface;
use Quidam\Definitions\Locale\LocaleInterface;
use Quidam\ProviderPack\ProviderPackInterface;

/**
 * Klingon language pack (tlh_QO) - an unofficial fan pack for fun: warriors named by their fathers, the worlds of
 * the Empire, Klingon numbers, darseks and stardates. QO is a user-assigned ISO code (for Qo'noS) - no real country,
 * so the locale has none:
 *
 *   $generator = Quidam::create()->withProvider(new TlhQoProviderPack());
 */
final readonly class TlhQoProviderPack implements ProviderPackInterface
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
     * No Earth country - phone numbers and IBANs of random countries; prices in darseks ("1,250 darsek", whole
     * coins, XXX - the ISO code for "no currency"); stardates as long dates - see DateTime::longDate()
     */
    public static function locale(): Locale
    {
        return new Locale(languageCode: 'tlh', currencyCode: 'XXX', currencySymbol: 'darsek', pricePattern: '{amount} {symbol}');
    }
}
