<?php

declare(strict_types = 1);

namespace Quidam\DefinitionPack;

use Psr\Clock\ClockInterface;
use Quidam\Addon\EdgeCases;
use Quidam\Addon\Filler;
use Quidam\Addon\RichText;
use Quidam\Addon\Template;
use Quidam\Addon\Text;
use Quidam\Clock\SystemClock;
use Quidam\Core\Address;
use Quidam\Core\Barcode;
use Quidam\Core\Blood;
use Quidam\Core\Calculator\EanCalculator;
use Quidam\Core\Calculator\IbanCalculator;
use Quidam\Core\Calculator\IsbnCalculator;
use Quidam\Core\Calculator\LuhnCalculator;
use Quidam\Core\Color;
use Quidam\Core\Commerce;
use Quidam\Core\Company;
use Quidam\Core\DateTime;
use Quidam\Core\Enum;
use Quidam\Core\File;
use Quidam\Core\Hash;
use Quidam\Core\Internet;
use Quidam\Core\Language;
use Quidam\Core\Locale\Locale;
use Quidam\Core\Lorem;
use Quidam\Core\Number;
use Quidam\Core\Payment;
use Quidam\Core\Person;
use Quidam\Core\PhoneNumber;
use Quidam\Core\Profile;
use Quidam\Core\Randomizer\SecureRandomizer;
use Quidam\Core\Randomizer\Seed;
use Quidam\Core\Randomizer\XoshiroRandomizer;
use Quidam\Core\Strings;
use Quidam\Core\System;
use Quidam\Core\Transliterator\Transliterator;
use Quidam\Core\UserAgent;
use Quidam\Core\Uuid;
use Quidam\Core\Vehicle;
use Quidam\Core\Version;
use Quidam\Definitions\Addon\EdgeCasesInterface;
use Quidam\Definitions\Addon\FillerInterface;
use Quidam\Definitions\Addon\RichTextInterface;
use Quidam\Definitions\Addon\TemplateInterface;
use Quidam\Definitions\Addon\TextInterface;
use Quidam\Definitions\Calculator\EanCalculatorInterface;
use Quidam\Definitions\Calculator\IbanCalculatorInterface;
use Quidam\Definitions\Calculator\IsbnCalculatorInterface;
use Quidam\Definitions\Calculator\LuhnCalculatorInterface;
use Quidam\Definitions\Extension\AddressExtensionInterface;
use Quidam\Definitions\Extension\BarcodeExtensionInterface;
use Quidam\Definitions\Extension\BloodExtensionInterface;
use Quidam\Definitions\Extension\ColorExtensionInterface;
use Quidam\Definitions\Extension\CommerceExtensionInterface;
use Quidam\Definitions\Extension\CompanyExtensionInterface;
use Quidam\Definitions\Extension\DateTimeExtensionInterface;
use Quidam\Definitions\Extension\EnumExtensionInterface;
use Quidam\Definitions\Extension\FileExtensionInterface;
use Quidam\Definitions\Extension\HashExtensionInterface;
use Quidam\Definitions\Extension\InternetExtensionInterface;
use Quidam\Definitions\Extension\LanguageExtensionInterface;
use Quidam\Definitions\Extension\LoremExtensionInterface;
use Quidam\Definitions\Extension\NumberExtensionInterface;
use Quidam\Definitions\Extension\PaymentExtensionInterface;
use Quidam\Definitions\Extension\PersonExtensionInterface;
use Quidam\Definitions\Extension\PhoneNumberExtensionInterface;
use Quidam\Definitions\Extension\ProfileExtensionInterface;
use Quidam\Definitions\Extension\StringsExtensionInterface;
use Quidam\Definitions\Extension\SystemExtensionInterface;
use Quidam\Definitions\Extension\UserAgentExtensionInterface;
use Quidam\Definitions\Extension\UuidExtensionInterface;
use Quidam\Definitions\Extension\VehicleExtensionInterface;
use Quidam\Definitions\Extension\VersionExtensionInterface;
use Quidam\Definitions\Locale\LocaleInterface;
use Quidam\Definitions\Randomizer\RandomizerInterface;
use Quidam\Definitions\Randomizer\SecureRandomizerInterface;
use Quidam\Definitions\Transliterator\TransliteratorInterface;
use Quidam\Strategy\SimpleStrategy;
use Quidam\Strategy\StrategyInterface;

/**
 * Default definitions. Ids are interfaces - extensions depend on each other through them, so replacing a definition
 * (withDefinition(), language provider) changes it for every extension using it.
 */
class DefinitionPack implements DefinitionPackInterface
{
    public function coreDefinitions(): array
    {
        return [
            Seed::class => new Seed(),
            RandomizerInterface::class => static fn (Seed $seed): RandomizerInterface => new XoshiroRandomizer($seed->value),
            SecureRandomizerInterface::class => SecureRandomizer::class,
            StrategyInterface::class => SimpleStrategy::class,
            ClockInterface::class => SystemClock::class,
            LocaleInterface::class => Locale::class,
            TransliteratorInterface::class => Transliterator::class,
        ];
    }

    public function calculators(): array
    {
        return [
            EanCalculatorInterface::class => EanCalculator::class,
            IbanCalculatorInterface::class => IbanCalculator::class,
            IsbnCalculatorInterface::class => IsbnCalculator::class,
            LuhnCalculatorInterface::class => LuhnCalculator::class,
        ];
    }

    public function extensions(): array
    {
        return [
            AddressExtensionInterface::class => Address::class,
            BarcodeExtensionInterface::class => Barcode::class,
            BloodExtensionInterface::class => Blood::class,
            ColorExtensionInterface::class => Color::class,
            CommerceExtensionInterface::class => Commerce::class,
            CompanyExtensionInterface::class => Company::class,
            DateTimeExtensionInterface::class => DateTime::class,
            EnumExtensionInterface::class => Enum::class,
            FileExtensionInterface::class => File::class,
            HashExtensionInterface::class => Hash::class,
            InternetExtensionInterface::class => Internet::class,
            LanguageExtensionInterface::class => Language::class,
            LoremExtensionInterface::class => Lorem::class,
            NumberExtensionInterface::class => Number::class,
            PaymentExtensionInterface::class => Payment::class,
            PersonExtensionInterface::class => Person::class,
            PhoneNumberExtensionInterface::class => PhoneNumber::class,
            ProfileExtensionInterface::class => Profile::class,
            StringsExtensionInterface::class => Strings::class,
            SystemExtensionInterface::class => System::class,
            UserAgentExtensionInterface::class => UserAgent::class,
            UuidExtensionInterface::class => Uuid::class,
            VehicleExtensionInterface::class => Vehicle::class,
            VersionExtensionInterface::class => Version::class,
        ];
    }

    /**
     * Opt-in add-ons - class names only, nothing is loaded until ext() asks for one
     */
    public function addons(): array
    {
        return [
            EdgeCasesInterface::class => EdgeCases::class,
            FillerInterface::class => Filler::class,
            RichTextInterface::class => RichText::class,
            TemplateInterface::class => Template::class,
            TextInterface::class => Text::class,
        ];
    }

    public function all(): array
    {
        return [...$this->coreDefinitions(), ...$this->calculators(), ...$this->extensions(), ...$this->addons()];
    }
}
