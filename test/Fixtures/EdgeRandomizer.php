<?php

declare(strict_types = 1);

namespace Quidam\Test\Fixtures;

use Quidam\Core\Randomizer\XoshiroRandomizer;

/**
 * Always returns lowest or highest possible int - for testing range boundaries
 */
final class EdgeRandomizer extends XoshiroRandomizer
{
    public function __construct(
        private readonly bool $highest,
    ) {
        parent::__construct();
    }

    public function getInt(int $min, int $max): int
    {
        return $this->highest ? $max : $min;
    }
}
