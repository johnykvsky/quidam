<?php

declare(strict_types = 1);

namespace Quidam\Strategy;

use Quidam\Exception\InvalidArgumentException;

/**
 * Values already returned by unique strategies, per key. One instance per Quidam container (with() starts empty one, fork() shares it).
 */
class UniqueMemory
{
    /** @var array<string, array<string, true>> */
    private array $values = [];

    /**
     * Remembers the value, returns false when it was already remembered under given key
     */
    public function add(string $key, mixed $value): bool
    {
        $this->validateKey($key);
        $hash = $this->hash($value);

        if (isset($this->values[$key][$hash])) {
            return false;
        }

        $this->values[$key][$hash] = true;

        return true;
    }

    public function has(string $key, mixed $value): bool
    {
        return isset($this->values[$key][$this->hash($value)]);
    }

    /**
     * Number of values remembered under given key
     */
    public function count(string $key): int
    {
        return count($this->values[$key] ?? []);
    }

    /**
     * Forgets values of given key, or all when null
     */
    public function reset(?string $key = null): void
    {
        if ($key === null) {
            $this->values = [];

            return;
        }

        unset($this->values[$key]);
    }

    protected function validateKey(string $key): void
    {
        if (trim($key) === '') {
            throw new InvalidArgumentException('Unique key cannot be empty');
        }
    }

    /**
     * Strict comparison - 1, '1' and 1.0 are different values
     */
    protected function hash(mixed $value): string
    {
        return serialize($value);
    }
}
