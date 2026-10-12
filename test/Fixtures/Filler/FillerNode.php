<?php

declare(strict_types = 1);

namespace Quidam\Test\Fixtures\Filler;

/**
 * Self reference - nullable, so recursion stops at max depth with null
 */
final class FillerNode
{
    public function __construct(
        public string $label,
        public ?FillerNode $parent,
    ) {
    }
}
