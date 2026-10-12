<?php

declare(strict_types = 1);

namespace Quidam\Test\Fixtures\Filler;

use Countable;
use Psr\Clock\ClockInterface;

final class FillerService
{
    public function __construct(
        public ?ClockInterface $optionalClock,
        public ClockInterface $clock,
        public ?Countable $counter = null,
    ) {
    }
}
