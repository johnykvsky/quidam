<?php

declare(strict_types = 1);

namespace Quidam\Test\Fixtures;

use Quidam\DefinitionPack\DefinitionPack;
use Quidam\Definitions\Extension\PersonExtensionInterface;
use Quidam\Definitions\Randomizer\SecureRandomizerInterface;

/**
 * Definition pack with own Person and own "secure" randomizer
 */
final class FixedDefinitionPack extends DefinitionPack
{
    public function coreDefinitions(): array
    {
        return [...parent::coreDefinitions(), SecureRandomizerInterface::class => FakeSecureRandomizer::class];
    }

    public function extensions(): array
    {
        return [...parent::extensions(), PersonExtensionInterface::class => FixedPerson::class];
    }
}
