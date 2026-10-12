<?php

declare(strict_types = 1);

namespace Quidam\Test\Fixtures;

use Quidam\Definitions\Extension\PersonExtensionInterface;
use Quidam\ProviderPack\ProviderPackInterface;

/**
 * Provider pack (like a language pack) replacing Person
 */
final readonly class FixedProviderPack implements ProviderPackInterface
{
    public function all(): array
    {
        return [PersonExtensionInterface::class => FixedPerson::class];
    }
}
