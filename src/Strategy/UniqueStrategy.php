<?php

declare(strict_types = 1);

namespace Quidam\Strategy;

use Quidam\Exception\InvalidArgumentException;
use Quidam\Exception\OverflowException;

/**
 * Retries until the value wasn't returned before under the same key - $key when given, otherwise name passed to
 * generate(). Memory can be shared between strategies (Quidam::unique() uses the generator's one).
 */
class UniqueStrategy implements StrategyInterface
{
    public const int DEFAULT_RETRIES = 10000;

    protected readonly UniqueMemory $memory;

    public function __construct(
        ?UniqueMemory $memory = null,
        protected readonly ?string $key = null,
        protected readonly int $retries = self::DEFAULT_RETRIES,
    ) {
        if ($key !== null && trim($key) === '') {
            throw new InvalidArgumentException('Unique key cannot be empty');
        }

        if ($retries < 1) {
            throw new InvalidArgumentException('Retries must be at least 1');
        }

        $this->memory = $memory ?? new UniqueMemory();
    }

    public function generate(string $name, callable $callback): mixed
    {
        $key = $this->key ?? $name;

        if (trim($key) === '') {
            throw new InvalidArgumentException('Unique key cannot be empty - pass it to UniqueStrategy or as generated value name');
        }

        for ($attempt = 0; $attempt < $this->retries; ++$attempt) {
            $value = $callback();

            if ($value instanceof ShortCircuitResult || $this->memory->add($key, $value)) {
                return $value;
            }
        }

        throw new OverflowException(sprintf(
            'Unique value for "%s" not found after %d retries (%d unique values generated)',
            $key,
            $this->retries,
            $this->memory->count($key),
        ));
    }
}
