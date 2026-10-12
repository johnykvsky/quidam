<?php

declare(strict_types = 1);

namespace Quidam\Test\Fixtures;

use Quidam\Strategy\StrategyInterface;

/**
 * Custom strategy changing generated value
 */
final readonly class PrefixStrategy implements StrategyInterface
{
    public function __construct(
        private string $prefix,
    ) {
    }

    public function generate(string $name, callable $callback): mixed
    {
        $value = $callback();

        return is_string($value) ? $this->prefix . $value : $value;
    }
}
