<?php

declare(strict_types = 1);

namespace Quidam\Test\Fixtures;

use DateTimeImmutable;
use Stringable;

/**
 * Returns fixed values of different types - for testing template conversion to string
 */
class TemplateValues
{
    public static function staticMethod(): string
    {
        return 'static';
    }

    public function nothing(): null
    {
        return null;
    }

    public function stringable(): Stringable
    {
        return new class implements Stringable {
            public function __toString(): string
            {
                return 'stringable';
            }
        };
    }

    public function object(): object
    {
        return new \stdClass();
    }

    public function date(): DateTimeImmutable
    {
        return new DateTimeImmutable('2026-01-02 03:04:05');
    }

    public function suit(): Suit
    {
        return Suit::Hearts;
    }

    public function unitSuit(): UnitSuit
    {
        return UnitSuit::Spades;
    }

    /** @return list<mixed> */
    public function list(): array
    {
        return [1, 'two', true, Suit::Spades];
    }

    public function float(): float
    {
        return 1.5;
    }

    /** Returns its arguments with their types, e.g. "int:1|string:a" */
    public function echo(mixed ...$arguments): string
    {
        $parts = [];
        foreach ($arguments as $key => $argument) {
            $parts[] = (is_string($key) ? $key . '=' : '') . get_debug_type($argument) . ':' . var_export($argument, true);
        }

        return implode('|', $parts);
    }

    protected function hidden(): string
    {
        return 'hidden';
    }

    /**
     * A bug inside a generator - not a template mistake
     */
    public function broken(int $value): int
    {
        throw new \TypeError(sprintf('internal bug with %d', $value));
    }
}
