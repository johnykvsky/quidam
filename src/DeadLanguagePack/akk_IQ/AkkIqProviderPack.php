<?php

declare(strict_types = 1);

namespace Quidam\DeadLanguagePack\akk_IQ;

use Quidam\Core\Locale\Locale;
use Quidam\Definitions\Extension\AddressExtensionInterface;
use Quidam\Definitions\Extension\CompanyExtensionInterface;
use Quidam\Definitions\Extension\DateTimeExtensionInterface;
use Quidam\Definitions\Extension\PaymentExtensionInterface;
use Quidam\Definitions\Extension\PersonExtensionInterface;
use Quidam\Definitions\Locale\LocaleInterface;
use Quidam\ProviderPack\ProviderPackInterface;

/**
 * Akkadian language pack (akk_IQ) - a pack for fun: Babylon and Assyria in transcribed Akkadian (names with the
 * father's name, cuneiform, cities from Ur to Nineveh, the Babylonian lunar calendar, silver by weight), with the
 * country conventions of Iraq:
 *
 *   $generator = Quidam::create()->withProvider(new AkkIqProviderPack());
 */
final readonly class AkkIqProviderPack implements ProviderPackInterface
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
     * Iraq: +964 with trunk prefix 0, IQD (written in silver by weight - see Payment), dates "12/10/2026"; long
     * dates are Babylonian - see DateTime::longDate()
     */
    public static function locale(): Locale
    {
        return new Locale(
            countryCode: 'IQ',
            languageCode: 'akk',
            callingCode: '964',
            currencyCode: 'IQD',
            currencySymbol: 'IQD',
            dateFormat: 'd/m/Y',
        );
    }
}
