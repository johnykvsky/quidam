<?php

declare(strict_types = 1);

namespace Quidam\DeadLanguagePack\non_IS;

use Quidam\Core\Locale\Locale;
use Quidam\Definitions\Extension\AddressExtensionInterface;
use Quidam\Definitions\Extension\CompanyExtensionInterface;
use Quidam\Definitions\Extension\DateTimeExtensionInterface;
use Quidam\Definitions\Extension\PersonExtensionInterface;
use Quidam\Definitions\Locale\LocaleInterface;
use Quidam\ProviderPack\ProviderPackInterface;

/**
 * Old Norse language pack (non_IS) - a pack for fun: the Viking age in normalized Old Norse spelling (patronymics and
 * bynames, runes, places from Vínland to Miklagarðr, the Old Icelandic calendar), with the country conventions of
 * Iceland, whose language is the closest to Old Norse and which still names people by their fathers:
 *
 *   $generator = Quidam::create()->withProvider(new NonIsProviderPack());
 */
final readonly class NonIsProviderPack implements ProviderPackInterface
{
    public function all(): array
    {
        return [
            LocaleInterface::class => self::locale(),
            PersonExtensionInterface::class => Person::class,
            AddressExtensionInterface::class => Address::class,
            DateTimeExtensionInterface::class => DateTime::class,
            CompanyExtensionInterface::class => Company::class,
        ];
    }

    /**
     * Iceland: +354 without trunk prefix, ISK as "1.234 kr." (no decimals), dates "12.10.2026"; long dates are
     * Old Icelandic - see DateTime::longDate()
     */
    public static function locale(): Locale
    {
        return new Locale(
            countryCode: 'IS',
            languageCode: 'non',
            callingCode: '354',
            trunkPrefix: '',
            currencyCode: 'ISK',
            currencySymbol: 'kr.',
            decimalSeparator: ',',
            thousandsSeparator: '.',
            pricePattern: "{amount}\u{00A0}{symbol}",
            dateFormat: 'd.m.Y',
            timeFormat: 'H:i',
        );
    }
}
