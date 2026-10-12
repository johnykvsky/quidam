<?php

declare(strict_types = 1);

namespace Quidam\Definitions\Randomizer;

/**
 * Randomizer for values which must be unpredictable (passwords, tokens). Container id used by Quidam::secure() -
 * point it to own implementation to change what "secure" means:
 *
 *   Quidam::create()->withDefinition(SecureRandomizerInterface::class, MyHsmRandomizer::class)->secure()
 */
interface SecureRandomizerInterface extends RandomizerInterface
{
}
