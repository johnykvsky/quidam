<?php

declare(strict_types = 1);

namespace Quidam\Test\Fixtures\Filler;

/**
 * Required self reference - can't be filled
 */
final class FillerLoop
{
    public function __construct(
        public FillerLoop $next,
    ) {
    }
}
