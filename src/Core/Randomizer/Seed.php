<?php

declare(strict_types = 1);

namespace Quidam\Core\Randomizer;

/**
 * Seed of the built-in randomizer: a value (repeatable), or null (random seed - different on every run).
 * Quidam::fork() replaces it with a seed derived from the key.
 */
final readonly class Seed
{
    public function __construct(
        public ?int $value = null,
    ) {
    }

    /**
     * Seed depending only on this seed and the key - the same pair always gives the same seed. Null stays null.
     */
    public function derive(string $key): self
    {
        if ($this->value === null) {
            return new self();
        }

        /** @var array{1: int} $unpacked */
        $unpacked = unpack('J', hash('xxh3', $this->value . "\0" . $key, true));

        return new self($unpacked[1]);
    }
}
