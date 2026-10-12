<?php

declare(strict_types = 1);

namespace Quidam\LanguagePack\pl_PL;

use Quidam\Addon\Template;
use Quidam\Core\Locale\Locale;
use Quidam\Definitions\Addon\TextInterface;
use Quidam\Definitions\Extension\AddressExtensionInterface;
use Quidam\Definitions\Extension\CommerceExtensionInterface;
use Quidam\Definitions\Extension\CompanyExtensionInterface;
use Quidam\Definitions\Extension\DateTimeExtensionInterface;
use Quidam\Definitions\Extension\InternetExtensionInterface;
use Quidam\Definitions\Extension\PaymentExtensionInterface;
use Quidam\Definitions\Extension\PersonExtensionInterface;
use Quidam\Definitions\Extension\PhoneNumberExtensionInterface;
use Quidam\Definitions\Extension\ProfileExtensionInterface;
use Quidam\Definitions\Extension\VehicleExtensionInterface;
use Quidam\Definitions\Locale\LocaleInterface;
use Quidam\ProviderPack\ProviderPackInterface;

/**
 * Polish language pack (pl_PL) - country conventions in one locale, Polish data in extensions:
 *
 *   $generator = Quidam::create()->withProvider(new PlPlProviderPack());
 *
 * or PlPlGenerator::create(), which adds typed accessors of the pack's own methods.
 */
final readonly class PlPlProviderPack implements ProviderPackInterface
{
    public function all(): array
    {
        return [
            LocaleInterface::class => self::locale(),
            PersonExtensionInterface::class => Person::class,
            AddressExtensionInterface::class => Address::class,
            PhoneNumberExtensionInterface::class => PhoneNumber::class,
            DateTimeExtensionInterface::class => DateTime::class,
            InternetExtensionInterface::class => Internet::class,
            CompanyExtensionInterface::class => Company::class,
            CommerceExtensionInterface::class => Commerce::class,
            PaymentExtensionInterface::class => Payment::class,
            VehicleExtensionInterface::class => Vehicle::class,
            ProfileExtensionInterface::class => Profile::class,
            NationalIdInterface::class => NationalId::class,
            TextInterface::class => Text::class,
            // templates and the CLI: {{ nationalId.pesel }}, quidam -l pl_PL nationalId.pesel
            Template::alias('nationalId') => NationalIdInterface::class,
        ];
    }

    /**
     * Poland: +48 without trunk prefix, PLN as "1 234,56 zł", dates "09.10.2026" and "9 października 2026"
     */
    public static function locale(): Locale
    {
        return new Locale(
            countryCode: 'PL',
            languageCode: 'pl',
            callingCode: '48',
            trunkPrefix: '',
            currencyCode: 'PLN',
            currencySymbol: 'zł',
            decimalSeparator: ',',
            thousandsSeparator: "\u{00A0}",
            pricePattern: "{amount}\u{00A0}{symbol}",
            dateFormat: 'd.m.Y',
            timeFormat: 'H:i',
            longDateFormat: '{day} {month} {year}',
        );
    }
}
