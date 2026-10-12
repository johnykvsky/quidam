<?php

declare(strict_types = 1);

namespace Quidam\Clock;

use DateTimeImmutable;
use DateTimeZone;
use Psr\Clock\ClockInterface;

/**
 * PSR-20 clock. Timezone of now() is used for all generated dates.
 */
class SystemClock implements ClockInterface
{
    private readonly DateTimeZone $timezone;

    public function __construct(DateTimeZone|string|null $timezone = null)
    {
        $timezone ??= date_default_timezone_get();

        $this->timezone = is_string($timezone) ? new DateTimeZone($timezone) : $timezone;
    }

    public function now(): DateTimeImmutable
    {
        return new DateTimeImmutable('now', $this->timezone);
    }
}
