<?php

declare(strict_types = 1);

namespace Quidam\Test\Generator;

use DateTimeImmutable;
use DateTimeZone;
use Quidam\Core\DateTime;
use Quidam\Definitions\Extension\DateTimeExtensionInterface;
use Quidam\Definitions\Randomizer\RandomizerInterface;
use Quidam\Exception\InvalidArgumentException;
use Quidam\Quidam;
use Quidam\Test\Fixtures\EdgeRandomizer;
use Quidam\Test\Fixtures\FrozenClock;
use Quidam\Test\Fixtures\MovableClock;
use ReflectionProperty;

final class DateTimeTest extends GeneratorTestCase
{
    private DateTimeImmutable $now;
    private DateTimeExtensionInterface $dateTime;

    protected function setUp(): void
    {
        parent::setUp();

        $this->now = new DateTimeImmutable('2026-06-15 12:00:00', new DateTimeZone('Europe/Warsaw'));
        $this->dateTime = Quidam::create(clock: new FrozenClock($this->now))->ext(DateTimeExtensionInterface::class);
    }

    public function testRelativeDatesUseClock(): void
    {
        for ($i = 0; $i < self::REPEAT; ++$i) {
            $past = $this->dateTime->dateTimePast('-10 days');
            self::assertGreaterThanOrEqual($this->now->modify('-10 days'), $past);
            self::assertLessThanOrEqual($this->now, $past);

            $future = $this->dateTime->dateTimeFuture('+1 hour');
            self::assertGreaterThanOrEqual($this->now, $future);
            self::assertLessThanOrEqual($this->now->modify('+1 hour'), $future);

            self::assertLessThanOrEqual($this->now, $this->dateTime->dateTime());
        }
    }

    public function testResultUsesClockTimezone(): void
    {
        self::assertSame('Europe/Warsaw', $this->dateTime->dateTime()->getTimezone()->getName());
    }

    public function testBetweenAcceptsAbsoluteDatesTimestampsAndObjects(): void
    {
        for ($i = 0; $i < self::REPEAT; ++$i) {
            $date = $this->dateTime->dateTimeBetween('2020-01-01', '2020-01-01 00:00:10');
            self::assertGreaterThanOrEqual(new DateTimeImmutable('2020-01-01', new DateTimeZone('Europe/Warsaw')), $date);
            self::assertLessThanOrEqual(new DateTimeImmutable('2020-01-01 00:00:10', new DateTimeZone('Europe/Warsaw')), $date);
        }

        self::assertSame(100, $this->dateTime->dateTimeBetween(100, 100)->getTimestamp());
        self::assertSame(1700000000, $this->dateTime->dateTimeBetween('@1700000000', '@1700000000')->getTimestamp());
        self::assertSame(
            $this->now->getTimestamp(),
            $this->dateTime->dateTimeBetween($this->now, $this->now)->getTimestamp(),
        );
    }

    public function testTimezoneInStringIsRespected(): void
    {
        $date = $this->dateTime->dateTimeBetween('2020-01-01 10:00 UTC', '2020-01-01 10:00 UTC');

        self::assertSame('2020-01-01T11:00:00+01:00', $date->format(DATE_ATOM));
        self::assertSame('Europe/Warsaw', $date->getTimezone()->getName());
    }

    public function testDateOnlyStringMeansMidnightAndRelativeUsesClock(): void
    {
        self::assertSame('2020-01-01 00:00:00', $this->dateTime->dateTimeBetween('2020-01-01', '2020-01-01')->format('Y-m-d H:i:s'));
        self::assertSame('2026-06-16 00:00:00', $this->dateTime->dateTimeBetween('tomorrow', 'tomorrow')->format('Y-m-d H:i:s'));
        self::assertSame('2026-06-14 12:00:00', $this->dateTime->dateTimeBetween('-1 day', '-1 day')->format('Y-m-d H:i:s'));
    }

    public function testNumericStringIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('use int for unix timestamp');
        $this->dateTime->dateTimeBetween('2020', '2021');
    }

    public function testDateTimeIsWithinFiftyYearsBeforeUntil(): void
    {
        for ($i = 0; $i < self::REPEAT; ++$i) {
            $date = $this->dateTime->dateTime('1960-01-01');
            self::assertGreaterThanOrEqual(new DateTimeImmutable('1910-01-01', new DateTimeZone('Europe/Warsaw')), $date);
            self::assertLessThanOrEqual(new DateTimeImmutable('1960-01-01', new DateTimeZone('Europe/Warsaw')), $date);

            self::assertGreaterThanOrEqual($this->now->modify('-50 years'), $this->dateTime->dateTime());
        }
    }

    public function testFormats(): void
    {
        $this->assertAlwaysMatches('/^\d{4}-\d{2}-\d{2}$/', fn (): string => $this->dateTime->date());
        $this->assertAlwaysMatches('/^\d{2}:\d{2}:\d{2}$/', fn (): string => $this->dateTime->time());
        $this->assertAlwaysMatches('/^\d+$/', fn (): int => $this->dateTime->unixTime());
    }

    public function testTimezone(): void
    {
        $this->assertAlwaysMatches('/^[A-Za-z_\/-]+$/', fn (): string => $this->dateTime->timezone());
        self::assertSame('Europe/Warsaw', $this->dateTime->timezone('pl'));
    }

    public function testDateOfBirth(): void
    {
        for ($i = 0; $i < self::REPEAT; ++$i) {
            $birth = $this->dateTime->dateOfBirth(18, 20);
            $age = $birth->diff($this->now)->y;

            self::assertGreaterThanOrEqual(18, $age);
            self::assertLessThanOrEqual(20, $age);
            self::assertSame('00:00:00', $birth->format('H:i:s'));
        }

        $newborn = $this->dateTime->dateOfBirth(0, 0);
        self::assertSame(0, $newborn->diff($this->now)->y);
    }

    public function testDateOfBirthEdges(): void
    {
        $dummy = Quidam::create(clock: new FrozenClock($this->now));

        // aged exactly 1 today: born between 2024-06-16 and 2025-06-15
        $oldest = $dummy->withDefinition(RandomizerInterface::class, new EdgeRandomizer(highest: false))->ext(DateTimeExtensionInterface::class)->dateOfBirth(1, 1);
        $youngest = $dummy->withDefinition(RandomizerInterface::class, new EdgeRandomizer(highest: true))->ext(DateTimeExtensionInterface::class)->dateOfBirth(1, 1);

        self::assertSame('2024-06-16', $oldest->format('Y-m-d'));
        self::assertSame('2025-06-15', $youngest->format('Y-m-d'));
    }

    public function testDateOfBirthOnLeapDay(): void
    {
        $dummy = Quidam::create(clock: new FrozenClock(new DateTimeImmutable('2028-02-29')));
        $oldest = $dummy->withDefinition(RandomizerInterface::class, new EdgeRandomizer(highest: false))->ext(DateTimeExtensionInterface::class)->dateOfBirth(1, 1);
        $youngest = $dummy->withDefinition(RandomizerInterface::class, new EdgeRandomizer(highest: true))->ext(DateTimeExtensionInterface::class)->dateOfBirth(1, 1);

        // aged exactly 1 on 2028-02-29: born between 2026-03-01 and 2027-02-28
        self::assertSame('2026-03-01', $oldest->format('Y-m-d'));
        self::assertSame('2027-02-28', $youngest->format('Y-m-d'));
        self::assertSame(1, $oldest->diff(new DateTimeImmutable('2028-02-29'))->y);
        self::assertSame(1, $youngest->diff(new DateTimeImmutable('2028-02-29'))->y);
    }

    public function testInvalidDateOfBirth(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->dateTime->dateOfBirth(30, 20);
    }

    public function testInvalidRange(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->dateTime->dateTimeBetween('now', '-1 day');
    }

    public function testInvalidString(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->dateTime->dateTimeBetween('not a date');
    }

    public function testInvalidCountry(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->dateTime->timezone('XX');
    }

    public function testTimezonesAreCachedPerCountry(): void
    {
        for ($i = 0; $i < self::REPEAT; ++$i) {
            self::assertContains($this->dateTime->timezone('us'), DateTimeZone::listIdentifiers(DateTimeZone::PER_COUNTRY, 'US'));
            self::assertContains($this->dateTime->timezone('US'), DateTimeZone::listIdentifiers(DateTimeZone::PER_COUNTRY, 'US'));
            self::assertContains($this->dateTime->timezone(), DateTimeZone::listIdentifiers());
        }

        // errors are not cached as empty results
        foreach (['XX', 'XX', 'invalid', 'invalid'] as $countryCode) {
            try {
                $this->dateTime->timezone($countryCode);
                self::fail('Exception expected for ' . $countryCode);
            } catch (InvalidArgumentException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function testDateTimeRange(): void
    {
        $from = $this->now->modify('-10 days');
        $until = $this->now->modify('+10 days');

        for ($i = 0; $i < self::REPEAT; ++$i) {
            [$start, $end] = $this->dateTime->dateTimeRange('-10 days', '+10 days');
            self::assertLessThanOrEqual($end, $start);
            self::assertGreaterThanOrEqual($from, $start);
            self::assertLessThanOrEqual($until, $end);
        }
    }

    public function testDateTimeRangeWithMaxDuration(): void
    {
        $until = $this->now->modify('+30 days');

        for ($i = 0; $i < self::REPEAT; ++$i) {
            [$start, $end] = $this->dateTime->dateTimeRange('now', '+30 days', '3 days');
            self::assertLessThanOrEqual($end, $start);
            self::assertLessThanOrEqual($start->modify('+3 days'), $end);
            self::assertLessThanOrEqual($until, $end);

            [$start, $end] = $this->dateTime->dateTimeRange('now', '+30 days', new \DateInterval('PT2H'));
            self::assertLessThanOrEqual($start->modify('+2 hours'), $end);
        }
    }

    public function testDateTimeRangeInvalidDuration(): void
    {
        foreach (['-2 days', 'not an interval'] as $duration) {
            try {
                $this->dateTime->dateTimeRange('now', '+1 year', $duration);
                self::fail('InvalidArgumentException expected for ' . $duration);
            } catch (InvalidArgumentException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function testDateTimesBetween(): void
    {
        $dates = $this->dateTime->dateTimesBetween(20, '-1 week', 'now');
        $sorted = $dates;
        sort($sorted);

        self::assertCount(20, $dates);
        self::assertSame($sorted, $dates);
        self::assertGreaterThanOrEqual($this->now->modify('-1 week'), $dates[0]);
        self::assertLessThanOrEqual($this->now, $dates[19]);
        self::assertSame([], $this->dateTime->dateTimesBetween(0));

        $this->expectException(InvalidArgumentException::class);
        $this->dateTime->dateTimesBetween(-1);
    }

    public function testCentury(): void
    {
        $this->assertAlwaysMatches('/^(X{0,2}(IX|IV|V?I{0,3})|XX|XXI)$/', fn (): string => $this->dateTime->century());

        $centuries = [];
        for ($i = 0; $i < 500; ++$i) {
            $centuries[$this->dateTime->century()] = true;
        }

        self::assertArrayHasKey('XXI', $centuries, '2026 is in the 21st century');
        self::assertArrayHasKey('I', $centuries);
        self::assertArrayNotHasKey('XXII', $centuries);
        self::assertSame('I', Quidam::create(clock: new FrozenClock(new DateTimeImmutable('0100-12-31')))->ext(DateTimeExtensionInterface::class)->century());
    }

    public function testRelativeStringsFollowMovingClock(): void
    {
        $clock = new MovableClock(new DateTimeImmutable('2026-01-01 12:00:00 UTC'));
        $dateTime = Quidam::create(clock: $clock)->ext(DateTimeExtensionInterface::class);

        self::assertSame('2025-12-31', $dateTime->dateTimeBetween('-1 day', '-1 day')->format('Y-m-d'));

        $clock->now = new DateTimeImmutable('2030-06-15 12:00:00 UTC');
        self::assertSame('2030-06-14', $dateTime->dateTimeBetween('-1 day', '-1 day')->format('Y-m-d'), 'relative string is not cached');
        self::assertSame('2030-06-15 12:00:00', $dateTime->dateTimeBetween('now', 'now')->format('Y-m-d H:i:s'));
    }

    public function testAbsoluteStringsRespectClockTimezone(): void
    {
        $clock = new MovableClock(new DateTimeImmutable('2026-01-01 12:00:00', new DateTimeZone('UTC')));
        $dateTime = Quidam::create(clock: $clock)->ext(DateTimeExtensionInterface::class);

        $utc = $dateTime->dateTimeBetween('2020-01-01 10:00', '2020-01-01 10:00');
        $clock->now = new DateTimeImmutable('2026-01-01 12:00:00', new DateTimeZone('Asia/Tokyo'));
        $tokyo = $dateTime->dateTimeBetween('2020-01-01 10:00', '2020-01-01 10:00');

        self::assertSame('2020-01-01 10:00 UTC', $utc->format('Y-m-d H:i e'));
        self::assertSame('2020-01-01 10:00 Asia/Tokyo', $tokyo->format('Y-m-d H:i e'), 'same string, other timezone - resolved again');
    }

    public function testInvalidStringsKeepFailing(): void
    {
        foreach ([1, 2] as $attempt) {
            try {
                $this->dateTime->dateTimeBetween('not a date', 'now');
                self::fail('Attempt ' . $attempt . ' should throw');
            } catch (InvalidArgumentException $e) {
                self::assertStringContainsString('Invalid date string', $e->getMessage());
            }
        }
    }

    public function testParsedDatesMemoryIsBounded(): void
    {
        for ($day = 0; $day < 700; ++$day) {
            $date = new DateTimeImmutable('2000-01-01')->modify('+' . $day . ' days')->format('Y-m-d');
            self::assertSame($date, $this->dateTime->dateTimeBetween($date, $date)->format('Y-m-d'));
        }

        $parsed = new ReflectionProperty(DateTime::class, 'parsedDates')->getValue($this->dateTime);
        self::assertIsArray($parsed);
        self::assertLessThanOrEqual(512, count($parsed));
    }

    public function testLocalFormats(): void
    {
        $date = new DateTimeImmutable('2026-01-05 19:30');

        self::assertSame('January', $this->dateTime->monthName($date));
        self::assertSame('Monday', $this->dateTime->dayOfWeek($date));
        self::assertSame('2026-01-05', $this->dateTime->localDate($date));
        self::assertSame('2026-01-05 19:30', $this->dateTime->localDateTime($date));
        self::assertSame('5 January 2026', $this->dateTime->longDate($date));

        $this->assertAlwaysMatches('/^\d{4}-\d{2}-\d{2}$/', fn (): string => $this->dateTime->localDate());
        $this->assertAlwaysMatches('/^[1-9]\d? [A-Z][a-z]+ \d{4}$/', fn (): string => $this->dateTime->longDate());
        $this->assertAlwaysMatches('/^[A-Z][a-z]+day$/', fn (): string => $this->dateTime->dayOfWeek());
    }
}
