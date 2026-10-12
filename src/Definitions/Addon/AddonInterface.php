<?php

declare(strict_types = 1);

namespace Quidam\Definitions\Addon;

use Quidam\Definitions\DefinitionInterface;

/**
 * Marker of opt-in add-ons - registered as lazy definitions, nothing is loaded until ext() asks for one
 */
interface AddonInterface extends DefinitionInterface
{
}
