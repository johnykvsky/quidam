<?php

declare(strict_types = 1);

namespace Quidam\Test\Fixtures;

use DateTimeImmutable;
use Psr\Clock\ClockInterface;

/**
 * Clock whose "now" can be moved - for checking that nothing depending on "now" is cached
 */
final class MovableClock implements ClockInterface
{
    public function __construct(
        public DateTimeImmutable $now,
    ) {
    }

    public function now(): DateTimeImmutable
    {
        return $this->now;
    }
}
