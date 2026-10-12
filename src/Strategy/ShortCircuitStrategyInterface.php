<?php

declare(strict_types = 1);

namespace Quidam\Strategy;

/**
 * Marker for strategies that may return without invoking the callback (i.e. ChanceStrategy default) -
 * CompositeStrategy wraps such value in ShortCircuitResult, so outer strategies don't validate or remember it.
 */
interface ShortCircuitStrategyInterface extends StrategyInterface
{
}
