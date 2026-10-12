<?php

declare(strict_types = 1);

namespace Quidam\Test\Fixtures;

use Quidam\Strategy\StrategyInterface;

/**
 * Remembers names it was called with
 */
final class NameRecordingStrategy implements StrategyInterface
{
    /** @var list<string> */
    private array $names = [];

    /** @return list<string> */
    public function names(): array
    {
        return $this->names;
    }

    public function generate(string $name, callable $callback): mixed
    {
        $this->names[] = $name;

        return $callback();
    }
}
