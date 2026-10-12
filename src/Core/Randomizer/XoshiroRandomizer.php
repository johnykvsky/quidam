<?php

declare(strict_types = 1);

namespace Quidam\Core\Randomizer;

use Random\Engine\Xoshiro256StarStar;

/**
 * Default randomizer - Xoshiro256**: fast, repeatable with seed. Without seed it's seeded with random 256 bits from
 * the OS - different on every run, but not meant for secrets (use Quidam::secure() for those).
 */
class XoshiroRandomizer extends CoreRandomizer
{
    public function __construct(?int $seed = null)
    {
        parent::__construct($seed === null ? new Xoshiro256StarStar() : new Xoshiro256StarStar($seed));
    }
}
