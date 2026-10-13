<?php

declare(strict_types = 1);

namespace Quidam\DeadLanguagePack\ang_GB;

use Quidam\Core\Locale\Locale;
use Quidam\Definitions\Extension\AddressExtensionInterface;
use Quidam\Definitions\Extension\CompanyExtensionInterface;
use Quidam\Definitions\Extension\DateTimeExtensionInterface;
use Quidam\Definitions\Extension\PersonExtensionInterface;
use Quidam\Definitions\Locale\LocaleInterface;
use Quidam\ProviderPack\ProviderPackInterface;

/**
 * Old English language pack (ang_GB) - a pack for fun: Anglo-Saxon England (compound names, the seven kingdoms and
 * their shires, Bede's month names, Chronicle years), with the country conventions of the United Kingdom:
 *
 *   $generator = Quidam::create()->withProvider(new AngGbProviderPack());
 */
final readonly class AngGbProviderPack implements ProviderPackInterface
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
     * United Kingdom: +44 with trunk prefix 0, GBP as "£1,234.56", dates "12/10/2026"; long dates are Old English -
     * see DateTime::longDate()
     */
    public static function locale(): Locale
    {
        return new Locale(
            countryCode: 'GB',
            languageCode: 'ang',
            callingCode: '44',
            currencyCode: 'GBP',
            currencySymbol: '£',
            pricePattern: '{symbol}{amount}',
            dateFormat: 'd/m/Y',
        );
    }
}
