<?php

declare(strict_types = 1);

namespace Quidam\Test\Generator;

use PHPUnit\Framework\TestCase;
use Quidam\Quidam;

abstract class GeneratorTestCase extends TestCase
{
    protected const int REPEAT = 50;

    protected Quidam $dummy;

    protected function setUp(): void
    {
        $this->dummy = Quidam::create();
    }

    /**
     * Runs $callback REPEAT times and asserts every result matches $pattern
     *
     * @param callable(): (string|int) $callback
     */
    protected function assertAlwaysMatches(string $pattern, callable $callback): void
    {
        for ($i = 0; $i < static::REPEAT; ++$i) {
            self::assertMatchesRegularExpression($pattern, (string) $callback());
        }
    }
}
