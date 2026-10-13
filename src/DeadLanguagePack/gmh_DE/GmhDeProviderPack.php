<?php

declare(strict_types = 1);

namespace Quidam\DeadLanguagePack\gmh_DE;

use Quidam\Core\Locale\Locale;
use Quidam\Definitions\Extension\AddressExtensionInterface;
use Quidam\Definitions\Extension\CompanyExtensionInterface;
use Quidam\Definitions\Extension\DateTimeExtensionInterface;
use Quidam\Definitions\Extension\PersonExtensionInterface;
use Quidam\Definitions\Locale\LocaleInterface;
use Quidam\ProviderPack\ProviderPackInterface;

/**
 * Middle High German language pack (gmh_DE) - a pack for fun: the age of the Nibelungenlied and the Minnesang in
 * normalized Middle High German spelling (bynames, named houses, Old German months, charter dates), with the country
 * conventions of Germany:
 *
 *   $generator = Quidam::create()->withProvider(new GmhDeProviderPack());
 */
final readonly class GmhDeProviderPack implements ProviderPackInterface
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
     * Germany: +49 with trunk prefix 0, EUR as "1.234,56 €", dates "12.10.2026"; long dates are medieval - see
     * DateTime::longDate()
     */
    public static function locale(): Locale
    {
        return new Locale(
            countryCode: 'DE',
            languageCode: 'gmh',
            callingCode: '49',
            currencyCode: 'EUR',
            currencySymbol: '€',
            decimalSeparator: ',',
            thousandsSeparator: '.',
            pricePattern: "{amount}\u{00A0}{symbol}",
            dateFormat: 'd.m.Y',
        );
    }
}
