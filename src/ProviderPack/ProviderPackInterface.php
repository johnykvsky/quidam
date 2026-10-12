<?php

declare(strict_types = 1);

namespace Quidam\ProviderPack;

/**
 * Set of definitions replacing default ones - i.e. a language pack:
 *
 *   final class PlProviderPack implements ProviderPackInterface
 *   {
 *       public function all(): array
 *       {
 *           return [
 *               PersonExtensionInterface::class => PlPerson::class,
 *               AddressExtensionInterface::class => PlAddress::class,
 *           ];
 *       }
 *   }
 *
 *   $generator = Quidam::create()->withProvider(new PlProviderPack());
 *
 * Extensions depend on each other by interface, so a replaced one is used everywhere (i.e. PlPerson in emails).
 */
interface ProviderPackInterface
{
    /**
     * Definitions by id - the same forms as Quidam::withDefinition() accepts
     *
     * @return array<string, mixed>
     */
    public function all(): array;
}
