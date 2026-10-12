<?php

declare(strict_types = 1);

namespace Quidam\Core;

use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Psr\Clock\ClockInterface;
use Quidam\Definitions\Extension\DateTimeExtensionInterface;
use Quidam\Definitions\Locale\LocaleInterface;
use Quidam\Definitions\Randomizer\RandomizerInterface;
use Quidam\Exception\InvalidArgumentException;

/**
 * All dates are DateTimeImmutable in clock timezone.
 *
 * Date params accept:
 * - DateTimeInterface
 * - int - unix timestamp
 * - string - absolute date ("2020-01-01", "2020-01-01 10:00 UTC", "@1733785884") or relative one
 *   ("-30 days", "next monday", "tomorrow"), which is relative to the clock "now"
 */
class DateTime implements DateTimeExtensionInterface
{
    /** @var list<string> */
    protected const array MONTHS = [
        'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November',
        'December',
    ];

    /**
     * Month names in long dates - languages that inflect them override it (Polish "9 października")
     *
     * @var list<string>
     */
    protected const array MONTHS_IN_DATES = self::MONTHS;

    /**
     * Days of week, Monday first
     *
     * @var list<string>
     */
    protected const array DAYS = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

    /**
     * Timezone identifiers per country code ('' for all) - timezone database doesn't change during the process
     *
     * @var array<string, list<string>>
     */
    private static array $timezones = [];

    /** Remembered date strings - more distinct strings than this and the memory starts again */
    private const int MAX_PARSED_DATES = 512;

    /**
     * Date strings already seen, by string and timezone: resolved date for absolute strings (they don't depend on
     * "now"), false for relative ones (resolved against "now" on every call)
     *
     * @var array<string, DateTimeImmutable|false>
     */
    private array $parsedDates = [];

    public function __construct(
        protected readonly RandomizerInterface $randomizer,
        protected readonly ClockInterface $clock,
        protected readonly LocaleInterface $locale,
    ) {
    }

    /**
     * Random date within 50 years before $until
     */
    public function dateTime(DateTimeInterface|string|int $until = 'now'): DateTimeImmutable
    {
        $now = $this->clock->now();
        $end = $this->toDateTime($until, $now);

        return $this->between($end->modify('-50 years')->getTimestamp(), $end->getTimestamp(), $now);
    }

    /**
     * Random date within [$from, $until]. Date objects (or timestamps) are the fastest - strings are parsed, see
     * the class description for accepted formats.
     */
    public function dateTimeBetween(
        DateTimeInterface|string|int $from = '-30 years',
        DateTimeInterface|string|int $until = 'now',
    ): DateTimeImmutable {
        // one "now" for the whole call - both ends relative to the same moment
        $now = $this->clock->now();

        return $this->between($this->timestamp($from, $now), $this->timestamp($until, $now), $now);
    }

    /**
     * Start and end within [$from, $until], start <= end - i.e. bookings, subscriptions, events.
     *
     * Without $maxDuration both are random points of the range. With it, start is random and end is at most
     * $maxDuration after start (and not after $until), i.e. dateTimeRange('now', '+1 year', '14 days').
     *
     * @param DateInterval|string|null $maxDuration DateInterval or relative string ('14 days', '2 hours')
     * @return array{DateTimeImmutable, DateTimeImmutable}
     */
    public function dateTimeRange(
        DateTimeInterface|string|int $from = '-30 years',
        DateTimeInterface|string|int $until = 'now',
        DateInterval|string|null $maxDuration = null,
    ): array {
        $start = $this->toDateTime($from);
        $end = $this->toDateTime($until);

        if ($maxDuration === null) {
            $first = $this->dateTimeBetween($start, $end);
            $second = $this->dateTimeBetween($start, $end);

            return $first <= $second ? [$first, $second] : [$second, $first];
        }

        $interval = $this->toInterval($maxDuration);
        $rangeStart = $this->dateTimeBetween($start, $end);
        $latestEnd = $rangeStart->add($interval);

        if ($latestEnd < $rangeStart) {
            throw new InvalidArgumentException('$maxDuration cannot be negative');
        }

        return [$rangeStart, $this->dateTimeBetween($rangeStart, min($latestEnd, $end))];
    }

    /**
     * $count dates within [$from, $until], sorted from the oldest - i.e. order history, log entries, versions
     *
     * @return list<DateTimeImmutable>
     */
    public function dateTimesBetween(
        int $count,
        DateTimeInterface|string|int $from = '-30 years',
        DateTimeInterface|string|int $until = 'now',
    ): array {
        if ($count < 0) {
            throw new InvalidArgumentException('$count cannot be negative');
        }

        $now = $this->clock->now();
        $start = $this->timestamp($from, $now);
        $end = $this->timestamp($until, $now);
        $dates = [];

        for ($i = 0; $i < $count; ++$i) {
            $dates[] = $this->between($start, $end, $now);
        }

        sort($dates);

        return $dates;
    }

    public function dateTimePast(DateTimeInterface|string|int $from = '-30 days'): DateTimeImmutable
    {
        return $this->dateTimeBetween($from, 'now');
    }

    public function dateTimeFuture(DateTimeInterface|string|int $until = '+30 days'): DateTimeImmutable
    {
        return $this->dateTimeBetween('now', $until);
    }

    /**
     * Birth date (midnight) of a person who is between $minAge and $maxAge years old today.
     * Person born on February 29 has birthday on March 1 in non-leap years.
     */
    public function dateOfBirth(int $minAge = 18, int $maxAge = 80): DateTimeImmutable
    {
        if ($minAge < 0 || $minAge > $maxAge) {
            throw new InvalidArgumentException('dateOfBirth() requires 0 <= $minAge <= $maxAge');
        }

        $now = $this->clock->now();
        $today = $now->setTime(0, 0);

        // born after this day - otherwise already older than $maxAge
        $from = $this->sameDayYearsAgo($today, $maxAge + 1)->modify('+1 day');
        // born on or before this day - otherwise younger than $minAge
        $until = $this->sameDayYearsAgo($today, $minAge)->setTime(23, 59, 59);

        return $this->between($from->getTimestamp(), $until->getTimestamp(), $now)->setTime(0, 0);
    }

    public function date(string $format = 'Y-m-d', DateTimeInterface|string|int $until = 'now'): string
    {
        return $this->dateTime($until)->format($format);
    }

    public function time(string $format = 'H:i:s', DateTimeInterface|string|int $until = 'now'): string
    {
        return $this->dateTime($until)->format($format);
    }

    public function unixTime(DateTimeInterface|string|int $until = 'now'): int
    {
        return $this->dateTime($until)->getTimestamp();
    }

    /**
     * Month name of given or random date, e.g. "October"
     */
    public function monthName(?DateTimeInterface $date = null): string
    {
        return static::MONTHS[(int) ($date ?? $this->dateTime())->format('n') - 1];
    }

    /**
     * Day of week of given or random date, e.g. "Friday"
     */
    public function dayOfWeek(?DateTimeInterface $date = null): string
    {
        return static::DAYS[(int) ($date ?? $this->dateTime())->format('N') - 1];
    }

    /**
     * Numeric date as written locally (LocaleInterface, ISO 8601 by default), e.g. "2026-10-09" - of given or random date
     */
    public function localDate(?DateTimeInterface $date = null): string
    {
        return ($date ?? $this->dateTime())->format($this->locale->dateFormat());
    }

    /**
     * Numeric date and time as written locally (LocaleInterface), e.g. "2026-10-09 14:05"
     */
    public function localDateTime(?DateTimeInterface $date = null): string
    {
        $date ??= $this->dateTime();

        return $date->format($this->locale->dateFormat()) . ' ' . $date->format($this->locale->timeFormat());
    }

    /**
     * Date with month name (LocaleInterface layout, MONTHS_IN_DATES names), e.g. "9 October 2026"
     */
    public function longDate(?DateTimeInterface $date = null): string
    {
        $date ??= $this->dateTime();

        return strtr($this->locale->longDateFormat(), [
            '{day}' => $date->format('j'),
            '{month}' => static::MONTHS_IN_DATES[(int) $date->format('n') - 1],
            '{year}' => $date->format('Y'),
        ]);
    }

    /**
     * Century in Roman numerals, from "I" up to the current one (clock "now"), e.g. "XIX"
     */
    public function century(): string
    {
        $current = intdiv((int) $this->clock->now()->format('Y') - 1, 100) + 1;

        return $this->roman($this->randomizer->getInt(1, max(1, $current)));
    }

    /**
     * Random timezone identifier of given ISO 3166-1 alpha-2 country - when null the locale country, or any
     * timezone when locale has no country
     */
    public function timezone(?string $countryCode = null): string
    {
        $countryCode ??= $this->locale->countryCode();
        $identifiers = $this->timezoneIdentifiers($countryCode === null ? '' : strtoupper($countryCode));

        if ($identifiers === []) {
            throw new InvalidArgumentException(sprintf('No timezones found for country code "%s"', $countryCode));
        }

        return $this->randomizer->randomElement($identifiers);
    }

    /**
     * Cached list of timezone identifiers, all for empty country code
     *
     * @return list<string>
     */
    protected function timezoneIdentifiers(string $countryCode): array
    {
        if (isset(self::$timezones[$countryCode])) {
            return self::$timezones[$countryCode];
        }

        try {
            $identifiers = $countryCode === ''
                ? DateTimeZone::listIdentifiers()
                : DateTimeZone::listIdentifiers(DateTimeZone::PER_COUNTRY, $countryCode);
        } catch (\ValueError $e) {
            throw new InvalidArgumentException(sprintf('Invalid country code "%s"', $countryCode), 0, $e);
        }

        return self::$timezones[$countryCode] = $identifiers;
    }

    protected function roman(int $number): string
    {
        $roman = '';
        foreach (['M' => 1000, 'CM' => 900, 'D' => 500, 'CD' => 400, 'C' => 100, 'XC' => 90, 'L' => 50, 'XL' => 40, 'X' => 10, 'IX' => 9, 'V' => 5, 'IV' => 4, 'I' => 1] as $symbol => $value) {
            while ($number >= $value) {
                $roman .= $symbol;
                $number -= $value;
            }
        }

        return $roman;
    }

    /**
     * Random date between two timestamps, in the timezone of $now (the clock)
     */
    protected function between(int $start, int $end, DateTimeImmutable $now): DateTimeImmutable
    {
        if ($start > $end) {
            throw new InvalidArgumentException('"$from" must be anterior to "$until".');
        }

        return $this->fromTimestamp($this->randomizer->getInt($start, $end), $now);
    }

    /**
     * Timestamp of a date argument - objects and ints directly, strings through toDateTime()
     */
    protected function timestamp(DateTimeInterface|string|int $date, DateTimeImmutable $now): int
    {
        return match (true) {
            $date instanceof DateTimeInterface => $date->getTimestamp(),
            is_int($date) => $date,
            default => $this->toDateTime($date, $now)->getTimestamp(),
        };
    }

    /** @param DateTimeImmutable|null $now Clock "now" for relative strings and the timezone - read from the clock when null */
    protected function toDateTime(DateTimeInterface|string|int $date, ?DateTimeImmutable $now = null): DateTimeImmutable
    {
        if ($date instanceof DateTimeInterface) {
            return DateTimeImmutable::createFromInterface($date);
        }

        $now ??= $this->clock->now();

        if (is_int($date)) {
            return $this->fromTimestamp($date, $now);
        }

        // "now" is a no-op relative format
        if ($date === 'now') {
            return $now;
        }

        $key = $date . "\0" . $now->getTimezone()->getName();
        $parsed = $this->parsedDates[$key] ?? null;

        if ($parsed instanceof DateTimeImmutable) {
            return $parsed;
        }

        try {
            if ($parsed === null) {
                $parsed = $this->parse($date, $now);

                if (count($this->parsedDates) >= self::MAX_PARSED_DATES) {
                    $this->parsedDates = [];
                }

                $this->parsedDates[$key] = $parsed;

                if ($parsed instanceof DateTimeImmutable) {
                    return $parsed;
                }
            }

            // relative date - relative to clock "now"
            return $now->modify($date);
        } catch (\DateMalformedStringException $e) {
            throw new InvalidArgumentException(sprintf('Invalid date string "%s"', $date), 0, $e);
        }
    }

    /**
     * Absolute date string resolved in the clock timezone, or false for a relative one
     *
     * @throws \DateMalformedStringException
     */
    protected function parse(string $date, DateTimeImmutable $now): DateTimeImmutable|false
    {
        if (is_numeric($date)) {
            throw new InvalidArgumentException(sprintf(
                'Numeric string "%s" is ambiguous - use int for unix timestamp or "@%1$s" string',
                $date,
            ));
        }

        // absolute date (has year) - constructor respects timezone given in string and uses midnight for date-only
        if (date_parse($date)['year'] !== false) {
            return new DateTimeImmutable($date, $now->getTimezone())->setTimezone($now->getTimezone());
        }

        return false;
    }

    protected function toInterval(DateInterval|string $interval): DateInterval
    {
        if ($interval instanceof DateInterval) {
            return $interval;
        }

        try {
            return DateInterval::createFromDateString($interval);
        } catch (\DateMalformedIntervalStringException $e) {
            throw new InvalidArgumentException(sprintf('Invalid interval string "%s"', $interval), 0, $e);
        }
    }

    protected function fromTimestamp(int $timestamp, ?DateTimeImmutable $now = null): DateTimeImmutable
    {
        return new DateTimeImmutable('@' . $timestamp)->setTimezone(($now ?? $this->clock->now())->getTimezone());
    }

    /**
     * Same month and day $years ago, February 29 becomes February 28 in non-leap year
     */
    protected function sameDayYearsAgo(DateTimeImmutable $date, int $years): DateTimeImmutable
    {
        $year = (int) $date->format('Y') - $years;
        $month = (int) $date->format('n');
        $daysInMonth = (int) $date->setDate($year, $month, 1)->format('t');

        return $date->setDate($year, $month, min((int) $date->format('j'), $daysInMonth));
    }
}
