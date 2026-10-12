<?php

declare(strict_types = 1);

namespace Quidam\Test\Clock;

use DateTime;
use PHPUnit\Framework\TestCase;
use Quidam\Clock\FrozenClock;
use Quidam\Definitions\Extension\DateTimeExtensionInterface;
use Quidam\Exception\InvalidArgumentException;
use Quidam\Quidam;

final class FrozenClockTest extends TestCase
{
    public function testTimeDoesNotMove(): void
    {
        $clock = new FrozenClock('2026-10-08 12:00:00 Europe/Warsaw');

        self::assertSame('2026-10-08T12:00:00+02:00', $clock->now()->format(DATE_ATOM));
        self::assertEquals($clock->now(), $clock->now());
    }

    public function testFromDateTimeIsNotAffectedByLaterChanges(): void
    {
        $date = new DateTime('2026-01-01 00:00:00 UTC');
        $clock = new FrozenClock($date);
        $date->modify('+1 day');

        self::assertSame('2026-01-01', $clock->now()->format('Y-m-d'));
    }

    public function testMakesDatesRepeatable(): void
    {
        $clock = new FrozenClock('2026-01-01 00:00:00 UTC');

        self::assertEquals(
            Quidam::create(seed: 1, clock: $clock)->ext(DateTimeExtensionInterface::class)->dateTimePast(),
            Quidam::create(seed: 1, clock: $clock)->ext(DateTimeExtensionInterface::class)->dateTimePast(),
        );
    }

    public function testInvalidDate(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new FrozenClock('not a date');
    }
}
