<?php

declare(strict_types = 1);

namespace Quidam\DeadLanguagePack\grc_GR;

use Quidam\Core\Locale\Locale;
use Quidam\Definitions\Extension\AddressExtensionInterface;
use Quidam\Definitions\Extension\CompanyExtensionInterface;
use Quidam\Definitions\Extension\DateTimeExtensionInterface;
use Quidam\Definitions\Extension\PersonExtensionInterface;
use Quidam\Definitions\Locale\LocaleInterface;
use Quidam\ProviderPack\ProviderPackInterface;

/**
 * Ancient Greek language pack (grc_GR) - a pack for fun: classical Athens and the Greek world (names with patronymic
 * and deme, cities from Massalia to Trapezous, the Attic calendar dated by Olympiads), with the country conventions of
 * Greece today:
 *
 *   $generator = Quidam::create()->withProvider(new GrcGrProviderPack());
 */
final readonly class GrcGrProviderPack implements ProviderPackInterface
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
     * Greece: +30 without trunk prefix, EUR as "1.234,56 €", dates "12/10/2026"; long dates are Attic - see
     * DateTime::longDate()
     */
    public static function locale(): Locale
    {
        return new Locale(
            countryCode: 'GR',
            languageCode: 'grc',
            callingCode: '30',
            trunkPrefix: '',
            currencyCode: 'EUR',
            currencySymbol: '€',
            decimalSeparator: ',',
            thousandsSeparator: '.',
            pricePattern: "{amount}\u{00A0}{symbol}",
            dateFormat: 'd/m/Y',
            timeFormat: 'H:i',
        );
    }
}
