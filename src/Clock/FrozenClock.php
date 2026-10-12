<?php

declare(strict_types = 1);

namespace Quidam\Clock;

use DateTimeImmutable;
use DateTimeInterface;
use Psr\Clock\ClockInterface;
use Quidam\Exception\InvalidArgumentException;

/**
 * PSR-20 clock which always returns the same time - for repeatable dates in tests:
 *   Quidam::create(seed: 1, clock: new FrozenClock('2026-01-15 12:00:00 UTC'))
 */
final readonly class FrozenClock implements ClockInterface
{
    private DateTimeImmutable $now;

    /**
     * @param DateTimeInterface|string $now Date, or date string - relative strings ("tomorrow") are resolved once,
     *                                      when the clock is created
     */
    public function __construct(DateTimeInterface|string $now)
    {
        if ($now instanceof DateTimeInterface) {
            $this->now = DateTimeImmutable::createFromInterface($now);

            return;
        }

        try {
            $this->now = new DateTimeImmutable($now);
        } catch (\DateMalformedStringException $e) {
            throw new InvalidArgumentException(sprintf('Invalid date "%s"', $now), 0, $e);
        }
    }

    public function now(): DateTimeImmutable
    {
        return $this->now;
    }
}
