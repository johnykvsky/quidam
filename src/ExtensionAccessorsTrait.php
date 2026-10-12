<?php

declare(strict_types = 1);

namespace Quidam;

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

/**
 * Typed shortcuts to default extensions - $generator->person()->name() is $generator->ext(PersonExtensionInterface::class)->name().
 * They resolve interfaces, so provider packs and withDefinition() replacements apply. Own extensions and add-ons
 * stay on ext() (or own accessors in a subclass).
 */
trait ExtensionAccessorsTrait
{
    /**
     * @param class-string<T> $id
     * @return T
     *
     * @template T of object
     */
    abstract public function ext(string $id): object;

    public function address(): AddressExtensionInterface
    {
        return $this->ext(AddressExtensionInterface::class);
    }

    public function barcode(): BarcodeExtensionInterface
    {
        return $this->ext(BarcodeExtensionInterface::class);
    }

    public function blood(): BloodExtensionInterface
    {
        return $this->ext(BloodExtensionInterface::class);
    }

    public function color(): ColorExtensionInterface
    {
        return $this->ext(ColorExtensionInterface::class);
    }

    public function commerce(): CommerceExtensionInterface
    {
        return $this->ext(CommerceExtensionInterface::class);
    }

    public function company(): CompanyExtensionInterface
    {
        return $this->ext(CompanyExtensionInterface::class);
    }

    public function dateTime(): DateTimeExtensionInterface
    {
        return $this->ext(DateTimeExtensionInterface::class);
    }

    public function enum(): EnumExtensionInterface
    {
        return $this->ext(EnumExtensionInterface::class);
    }

    public function file(): FileExtensionInterface
    {
        return $this->ext(FileExtensionInterface::class);
    }

    public function hash(): HashExtensionInterface
    {
        return $this->ext(HashExtensionInterface::class);
    }

    public function internet(): InternetExtensionInterface
    {
        return $this->ext(InternetExtensionInterface::class);
    }

    public function language(): LanguageExtensionInterface
    {
        return $this->ext(LanguageExtensionInterface::class);
    }

    public function lorem(): LoremExtensionInterface
    {
        return $this->ext(LoremExtensionInterface::class);
    }

    public function number(): NumberExtensionInterface
    {
        return $this->ext(NumberExtensionInterface::class);
    }

    public function payment(): PaymentExtensionInterface
    {
        return $this->ext(PaymentExtensionInterface::class);
    }

    public function person(): PersonExtensionInterface
    {
        return $this->ext(PersonExtensionInterface::class);
    }

    public function phoneNumber(): PhoneNumberExtensionInterface
    {
        return $this->ext(PhoneNumberExtensionInterface::class);
    }

    public function profile(): ProfileExtensionInterface
    {
        return $this->ext(ProfileExtensionInterface::class);
    }

    public function strings(): StringsExtensionInterface
    {
        return $this->ext(StringsExtensionInterface::class);
    }

    public function system(): SystemExtensionInterface
    {
        return $this->ext(SystemExtensionInterface::class);
    }

    public function userAgent(): UserAgentExtensionInterface
    {
        return $this->ext(UserAgentExtensionInterface::class);
    }

    public function uuid(): UuidExtensionInterface
    {
        return $this->ext(UuidExtensionInterface::class);
    }

    public function vehicle(): VehicleExtensionInterface
    {
        return $this->ext(VehicleExtensionInterface::class);
    }

    public function version(): VersionExtensionInterface
    {
        return $this->ext(VersionExtensionInterface::class);
    }
}
