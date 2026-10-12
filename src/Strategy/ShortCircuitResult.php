<?php

declare(strict_types = 1);

namespace Quidam\Strategy;

/**
 * Final value returned by a strategy without generating (i.e. ChanceStrategy default).
 * Other strategies pass it through - it's not validated, retried or remembered as unique.
 */
final readonly class ShortCircuitResult
{
    public function __construct(
        public mixed $value,
    ) {
    }
}
