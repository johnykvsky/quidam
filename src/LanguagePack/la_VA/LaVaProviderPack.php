<?php

declare(strict_types = 1);

namespace Quidam\LanguagePack\la_VA;

use Quidam\Core\Locale\Locale;
use Quidam\Definitions\Extension\AddressExtensionInterface;
use Quidam\Definitions\Extension\CompanyExtensionInterface;
use Quidam\Definitions\Extension\DateTimeExtensionInterface;
use Quidam\Definitions\Extension\PersonExtensionInterface;
use Quidam\Definitions\Locale\LocaleInterface;
use Quidam\ProviderPack\ProviderPackInterface;

/**
 * Latin language pack (la_VA) - a pack for fun: classical Roman data (tria nomina, cities of the provinces, the Roman
 * calendar) with the country conventions of the Holy See, the one state where Latin is still official:
 *
 *   $generator = Quidam::create()->withProvider(new LaVaProviderPack());
 */
final readonly class LaVaProviderPack implements ProviderPackInterface
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
     * Vatican City: Italian numbering (+39, no trunk prefix to drop), EUR as "1.234,56 €", dates "09/10/2026";
     * long dates are Roman - see DateTime::longDate()
     */
    public static function locale(): Locale
    {
        return new Locale(
            countryCode: 'VA',
            languageCode: 'la',
            callingCode: '39',
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
