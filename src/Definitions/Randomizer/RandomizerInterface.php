<?php

declare(strict_types = 1);

namespace Quidam\Definitions\Randomizer;

use Quidam\Definitions\DefinitionInterface;
use Quidam\Exception\InvalidArgumentException;

interface RandomizerInterface extends DefinitionInterface
{
    public function getInt(int $min, int $max): int;

    public function getFloat(float $min, float $max): float;

    /**
     * Mind the type: int is a percent, float is a probability - getBool(1) is 1% chance, getBool(1.0) is 100%
     *
     * @param int|float $chanceOfTrue int as percent (0-100), float as probability (0.0-1.0)
     * @throws InvalidArgumentException When chance is out of range
     */
    public function getBool(int|float $chanceOfTrue = 50): bool;

    public function getBytes(int $length = 16): string;

    public function getBytesFromString(string $string, int $length = 8): string;

    /**
     * Random lowercase letter (a-z)
     */
    public function randomLetter(): string;

    /**
     * @param array<T> $array
     * @return T
     * @throws InvalidArgumentException When array is empty
     *
     * @template T
     */
    public function randomElement(array $array): mixed;

    /**
     * Returns randomly ordered $count elements from $array
     *
     * @param array<T> $array
     * @param bool $unique If true, every element (by value) can be picked only once
     * @return list<T>
     * @throws InvalidArgumentException When array is empty or has not enough unique elements
     *
     * @template T
     */
    public function randomElements(array $array, int $count = 1, bool $unique = false): array;

    /**
     * @param array<TKey, mixed> $array
     * @return TKey
     * @throws InvalidArgumentException When array is empty
     *
     * @template TKey of array-key
     */
    public function randomKey(array $array): int|string;

    /**
     * Returns key of $weights, picked with probability proportional to its weight,
     * e.g. ['new' => 70, 'paid' => 25, 'cancelled' => 5]
     *
     * @param array<TKey, int|float> $weights
     * @return TKey
     * @throws InvalidArgumentException When array is empty, has negative weight or all weights are zero
     *
     * @template TKey of array-key
     */
    public function weightedElement(array $weights): int|string;

    /**
     * @param array<TKey, TValue> $array
     * @return list<TValue>
     *
     * @template TKey of array-key
     * @template TValue
     */
    public function shuffleElements(array $array): array;
}
