<?php

declare(strict_types = 1);

namespace Quidam\LanguagePack\pl_PL;

use Psr\Clock\ClockInterface;
use Quidam\Container\ContainerFactoryInterface;
use Quidam\DefinitionPack\DefinitionPackInterface;
use Quidam\Definitions\Addon\TextInterface;
use Quidam\Definitions\Extension\CompanyExtensionInterface;
use Quidam\Definitions\Extension\PaymentExtensionInterface;
use Quidam\Definitions\Extension\PhoneNumberExtensionInterface;
use Quidam\Definitions\Extension\ProfileExtensionInterface;
use Quidam\Definitions\Extension\VehicleExtensionInterface;
use Quidam\Exception\ContainerException;
use Quidam\Quidam;

/**
 * Quidam with the Polish pack applied. Accessors of extensions with own methods return the pack classes, so
 * phoneNumber()->tollFreePhoneNumber() or profile()->citizen() are typed; nationalId() and text() are added:
 *
 *   $generator = PlPlGenerator::create(seed: 2026);
 *   $generator->profile()->citizen();
 *   $generator->nationalId()->pesel();
 *
 * Extensions are still fetched by interface - replacing one with withDefinition() works, as long as the replacement
 * extends the pack class (otherwise its accessor throws ContainerException).
 */
readonly class PlPlGenerator extends Quidam
{
    public static function create(
        ?int $seed = null,
        ?ClockInterface $clock = null,
        ?DefinitionPackInterface $definitionPack = null,
        ?ContainerFactoryInterface $containerFactory = null,
    ): static {
        return parent::create($seed, $clock, $definitionPack, $containerFactory)->withDefinitions(self::packDefinitions());
    }

    /**
     * Definitions of the pack, built once per process - class names, static closures and immutable objects (Locale)
     *
     * @return array<string, mixed>
     */
    private static function packDefinitions(): array
    {
        /** @var array<string, mixed>|null $definitions */
        static $definitions = null;

        return $definitions ??= new PlPlProviderPack()->all();
    }

    public function phoneNumber(): PhoneNumber
    {
        return $this->packExtension(PhoneNumberExtensionInterface::class, PhoneNumber::class);
    }

    public function company(): Company
    {
        return $this->packExtension(CompanyExtensionInterface::class, Company::class);
    }

    public function payment(): Payment
    {
        return $this->packExtension(PaymentExtensionInterface::class, Payment::class);
    }

    public function vehicle(): Vehicle
    {
        return $this->packExtension(VehicleExtensionInterface::class, Vehicle::class);
    }

    public function profile(): Profile
    {
        return $this->packExtension(ProfileExtensionInterface::class, Profile::class);
    }

    /**
     * PESEL, NIP, REGON, ID card, passport and other Polish identifiers
     */
    public function nationalId(): NationalIdInterface
    {
        return $this->ext(NationalIdInterface::class);
    }

    /**
     * Realistic Polish text (Text add-on with Bruno Schulz's corpus)
     */
    public function text(): TextInterface
    {
        return $this->ext(TextInterface::class);
    }

    /**
     * Extension registered under $id, which must be the pack class (or its subclass) - for its own methods
     *
     * @param class-string $id
     * @param class-string<T> $class
     * @return T
     *
     * @template T of object
     */
    private function packExtension(string $id, string $class): object
    {
        $extension = $this->ext($id);

        if (!$extension instanceof $class) {
            throw new ContainerException(sprintf(
                'Entry "%s" resolved to %s - %s accessor needs %s or its subclass, use ext() for other implementations',
                $id,
                get_debug_type($extension),
                self::class,
                $class,
            ));
        }

        return $extension;
    }
}
