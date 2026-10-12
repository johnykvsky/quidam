<?php

declare(strict_types = 1);

namespace Quidam\Definitions\Extension;

use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;

interface DateTimeExtensionInterface extends ExtensionInterface
{
    /**
     * Random date within 50 years before $until
     */
    public function dateTime(DateTimeInterface|string|int $until = 'now'): DateTimeImmutable;

    /**
     * Random date within [$from, $until]. Date objects (or timestamps) are the fastest - strings are parsed, see
     * the class description for accepted formats.
     */
    public function dateTimeBetween(
        DateTimeInterface|string|int $from = '-30 years',
        DateTimeInterface|string|int $until = 'now',
    ): DateTimeImmutable;

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
    ): array;

    /**
     * $count dates within [$from, $until], sorted from the oldest - i.e. order history, log entries, versions
     *
     * @return list<DateTimeImmutable>
     */
    public function dateTimesBetween(
        int $count,
        DateTimeInterface|string|int $from = '-30 years',
        DateTimeInterface|string|int $until = 'now',
    ): array;

    public function dateTimePast(DateTimeInterface|string|int $from = '-30 days'): DateTimeImmutable;

    public function dateTimeFuture(DateTimeInterface|string|int $until = '+30 days'): DateTimeImmutable;

    /**
     * Birth date (midnight) of a person who is between $minAge and $maxAge years old today.
     * Person born on February 29 has birthday on March 1 in non-leap years.
     */
    public function dateOfBirth(int $minAge = 18, int $maxAge = 80): DateTimeImmutable;

    public function date(string $format = 'Y-m-d', DateTimeInterface|string|int $until = 'now'): string;

    public function time(string $format = 'H:i:s', DateTimeInterface|string|int $until = 'now'): string;

    public function unixTime(DateTimeInterface|string|int $until = 'now'): int;

    /**
     * Month name of given or random date, e.g. "October"
     */
    public function monthName(?DateTimeInterface $date = null): string;

    /**
     * Day of week of given or random date, e.g. "Friday"
     */
    public function dayOfWeek(?DateTimeInterface $date = null): string;

    /**
     * Numeric date as written locally (LocaleInterface, ISO 8601 by default), e.g. "2026-10-09" - of given or random date
     */
    public function localDate(?DateTimeInterface $date = null): string;

    /**
     * Numeric date and time as written locally (LocaleInterface), e.g. "2026-10-09 14:05"
     */
    public function localDateTime(?DateTimeInterface $date = null): string;

    /**
     * Date with month name (LocaleInterface layout, MONTHS_IN_DATES names), e.g. "9 October 2026"
     */
    public function longDate(?DateTimeInterface $date = null): string;

    /**
     * Century in Roman numerals, from "I" up to the current one (clock "now"), e.g. "XIX"
     */
    public function century(): string;

    /**
     * Random timezone identifier of given ISO 3166-1 alpha-2 country - when null the locale country, or any
     * timezone when locale has no country
     */
    public function timezone(?string $countryCode = null): string;
}
