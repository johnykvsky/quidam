<?php

declare(strict_types = 1);

namespace Quidam\Test\Fixtures;

use Quidam\Core\Locale\Locale;
use Quidam\Definitions\Extension\BarcodeExtensionInterface;
use Quidam\Definitions\Extension\DateTimeExtensionInterface;
use Quidam\Definitions\Extension\PersonExtensionInterface;
use Quidam\Definitions\Extension\PhoneNumberExtensionInterface;
use Quidam\Definitions\Locale\LocaleInterface;
use Quidam\ProviderPack\ProviderPackInterface;

/**
 * Example language pack - Polish locale (conventions) and data of a few extensions (words)
 */
final readonly class PlProviderPack implements ProviderPackInterface
{
    public function all(): array
    {
        return [
            LocaleInterface::class => new Locale(
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
                longDateFormat: '{day} {month} {year}',
            ),
            PersonExtensionInterface::class => PlPerson::class,
            BarcodeExtensionInterface::class => PolishBarcode::class,
            DateTimeExtensionInterface::class => PlDateTime::class,
            PhoneNumberExtensionInterface::class => PlPhoneNumber::class,
        ];
    }
}
