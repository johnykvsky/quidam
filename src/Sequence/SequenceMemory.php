<?php

declare(strict_types = 1);

namespace Quidam\Sequence;

use Quidam\Exception\InvalidArgumentException;

/**
 * Counters of Quidam::sequence(), per key. One instance per Quidam (with() starts empty one).
 */
class SequenceMemory
{
    /** @var array<string, int> last returned value per key */
    private array $values = [];

    /**
     * Next value under given key - $start on first call (or after reset), previous value + 1 later
     */
    public function next(string $key, int $start = 1): int
    {
        if (trim($key) === '') {
            throw new InvalidArgumentException('Sequence key cannot be empty');
        }

        return $this->values[$key] = isset($this->values[$key]) ? $this->values[$key] + 1 : $start;
    }

    /**
     * Forgets given key, or all when null - next call starts again
     */
    public function reset(?string $key = null): void
    {
        if ($key === null) {
            $this->values = [];

            return;
        }

        unset($this->values[$key]);
    }
}
