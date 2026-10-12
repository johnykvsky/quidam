<?php

declare(strict_types = 1);

namespace Quidam\Test\Fixtures;

use Quidam\Core\Randomizer\CoreRandomizer;
use Quidam\Definitions\Randomizer\SecureRandomizerInterface;
use Random\Engine\PcgOneseq128XslRr64;

/**
 * Own implementation of SecureRandomizerInterface - shows "secure" can point to anything (here: repeatable PCG)
 */
final class FakeSecureRandomizer extends CoreRandomizer implements SecureRandomizerInterface
{
    public function __construct()
    {
        parent::__construct(new PcgOneseq128XslRr64(1));
    }
}
