<?php

declare(strict_types = 1);

namespace Quidam\DeadLanguagePack\got_IT;

use Quidam\Core\Locale\Locale;
use Quidam\Definitions\Extension\AddressExtensionInterface;
use Quidam\Definitions\Extension\CompanyExtensionInterface;
use Quidam\Definitions\Extension\PersonExtensionInterface;
use Quidam\Definitions\Locale\LocaleInterface;
use Quidam\ProviderPack\ProviderPackInterface;

/**
 * Gothic language pack (got_IT) - a pack for fun: the language of Wulfila's Bible (names with the father's name, the
 * Gothic alphabet and numerals, towns of the Gothic kingdoms from the Black Sea to Toledo), with the country
 * conventions of Italy, where Theoderic's Ostrogothic kingdom ruled from Ravenna:
 *
 *   $generator = Quidam::create()->withProvider(new GotItProviderPack());
 */
final readonly class GotItProviderPack implements ProviderPackInterface
{
    public function all(): array
    {
        return [
            LocaleInterface::class => self::locale(),
            PersonExtensionInterface::class => Person::class,
            AddressExtensionInterface::class => Address::class,
            CompanyExtensionInterface::class => Company::class,
        ];
    }

    /**
     * Italy: +39 without trunk prefix, EUR as "1.234,56 €", dates "12/10/2026"
     */
    public static function locale(): Locale
    {
        return new Locale(
            countryCode: 'IT',
            languageCode: 'got',
            callingCode: '39',
            trunkPrefix: '',
            currencyCode: 'EUR',
            currencySymbol: '€',
            decimalSeparator: ',',
            thousandsSeparator: '.',
            pricePattern: "{amount}\u{00A0}{symbol}",
            dateFormat: 'd/m/Y',
        );
    }
}
