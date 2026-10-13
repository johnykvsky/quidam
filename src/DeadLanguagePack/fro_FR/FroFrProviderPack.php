<?php

declare(strict_types = 1);

namespace Quidam\DeadLanguagePack\fro_FR;

use Quidam\Core\Locale\Locale;
use Quidam\Definitions\Extension\AddressExtensionInterface;
use Quidam\Definitions\Extension\CompanyExtensionInterface;
use Quidam\Definitions\Extension\DateTimeExtensionInterface;
use Quidam\Definitions\Extension\PaymentExtensionInterface;
use Quidam\Definitions\Extension\PersonExtensionInterface;
use Quidam\Definitions\Locale\LocaleInterface;
use Quidam\ProviderPack\ProviderPackInterface;

/**
 * Old French language pack (fro_FR) - a pack for fun: the France of the chansons de geste and Chrétien's romances
 * (bynames, house signs, feast days and years of grace, livres, sous and deniers tournois), with the country
 * conventions of France:
 *
 *   $generator = Quidam::create()->withProvider(new FroFrProviderPack());
 */
final readonly class FroFrProviderPack implements ProviderPackInterface
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
     * France: +33 with trunk prefix 0, EUR (written in livres, sous and deniers - see Payment), dates "12/10/2026";
     * long dates are medieval - see DateTime::longDate()
     */
    public static function locale(): Locale
    {
        return new Locale(
            countryCode: 'FR',
            languageCode: 'fro',
            callingCode: '33',
            currencyCode: 'EUR',
            currencySymbol: '€',
            decimalSeparator: ',',
            thousandsSeparator: "\u{202F}",
            pricePattern: "{amount}\u{00A0}{symbol}",
            dateFormat: 'd/m/Y',
        );
    }
}
