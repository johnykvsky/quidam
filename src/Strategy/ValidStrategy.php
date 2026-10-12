<?php

declare(strict_types = 1);

namespace Quidam\Strategy;

use Closure;
use Quidam\Exception\InvalidArgumentException;
use Quidam\Exception\OverflowException;

/**
 * Retries until the validator accepts the value
 */
class ValidStrategy implements StrategyInterface
{
    public const int DEFAULT_RETRIES = 10000;

    protected readonly Closure $validator;

    /** @param callable(mixed): bool $validator Called with generated value, valid when returns true */
    public function __construct(
        callable $validator,
        protected readonly int $retries = self::DEFAULT_RETRIES,
    ) {
        if ($retries < 1) {
            throw new InvalidArgumentException('Retries must be at least 1');
        }

        $this->validator = $validator(...);
    }

    public function generate(string $name, callable $callback): mixed
    {
        for ($attempt = 0; $attempt < $this->retries; ++$attempt) {
            $value = $callback();

            if ($value instanceof ShortCircuitResult || ($this->validator)($value) === true) {
                return $value;
            }
        }

        throw new OverflowException(sprintf('Valid value for "%s" not found after %d retries', $name, $this->retries));
    }
}
