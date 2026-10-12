<?php

declare(strict_types = 1);

namespace Quidam\Core\Randomizer;

use Quidam\Definitions\Randomizer\SecureRandomizerInterface;
use Random\Engine\Secure;

/**
 * Cryptographically secure randomizer (Random\Engine\Secure) - unpredictable, never repeatable, a system call per
 * random value. Default implementation of SecureRandomizerInterface, used by Quidam::secure().
 */
class SecureRandomizer extends CoreRandomizer implements SecureRandomizerInterface
{
    public function __construct()
    {
        parent::__construct(new Secure());
    }
}
