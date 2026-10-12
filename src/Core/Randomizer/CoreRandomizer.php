<?php

declare(strict_types = 1);

namespace Quidam\Core\Randomizer;

use Quidam\Definitions\Randomizer\RandomizerInterface;
use Quidam\Exception\InvalidArgumentException;
use Random\Engine;
use Random\IntervalBoundary;
use Random\Randomizer as PhpRandomizer;

/**
 * Base for randomizers built on PHP Random\Randomizer - subclass passes the engine:
 *
 *   final class PcgRandomizer extends CoreRandomizer
 *   {
 *       public function __construct(int $seed) { parent::__construct(new Random\Engine\PcgOneseq128XslRr64($seed)); }
 *   }
 *
 * Implement RandomizerInterface directly for randomness from other sources.
 */
abstract class CoreRandomizer implements RandomizerInterface
{
    protected readonly PhpRandomizer $randomizer;

    public function __construct(Engine $engine)
    {
        $this->randomizer = new PhpRandomizer($engine);
    }

    public function getInt(int $min, int $max): int
    {
        if ($min > $max) {
            throw new InvalidArgumentException(sprintf('$min (%d) cannot be higher than $max (%d)', $min, $max));
        }

        return $this->randomizer->getInt($min, $max);
    }

    public function getFloat(float $min, float $max): float
    {
        if ($min > $max) {
            throw new InvalidArgumentException(sprintf('$min (%F) cannot be higher than $max (%F)', $min, $max));
        }

        return $this->randomizer->getFloat($min, $max, IntervalBoundary::ClosedClosed);
    }

    public function getBool(int|float $chanceOfTrue = 50): bool
    {
        if (is_int($chanceOfTrue)) {
            if ($chanceOfTrue < 0 || $chanceOfTrue > 100) {
                throw new InvalidArgumentException(sprintf('Chance as int is a percent and must be within 0..100, %d given', $chanceOfTrue));
            }

            return $this->randomizer->getInt(1, 100) <= $chanceOfTrue;
        }

        if ($chanceOfTrue < 0.0 || $chanceOfTrue > 1.0) {
            throw new InvalidArgumentException(sprintf('Chance as float is a probability and must be within 0.0..1.0, %F given', $chanceOfTrue));
        }

        return $this->randomizer->nextFloat() < $chanceOfTrue;
    }

    public function getBytes(int $length = 16): string
    {
        if ($length < 1) {
            throw new InvalidArgumentException('Length must be 1 or higher');
        }

        return $this->randomizer->getBytes($length);
    }

    public function getBytesFromString(string $string, int $length = 8): string
    {
        if ($string === '') {
            throw new InvalidArgumentException('Empty string given');
        }

        if ($length < 1) {
            throw new InvalidArgumentException('Length must be 1 or higher');
        }

        return $this->randomizer->getBytesFromString($string, $length);
    }

    public function randomLetter(): string
    {
        return $this->randomizer->getBytesFromString('abcdefghijklmnopqrstuvwxyz', 1);
    }

    public function randomElement(array $array): mixed
    {
        return $array[$this->randomKey($array)];
    }

    public function randomElements(array $array, int $count = 1, bool $unique = false): array
    {
        if ($array === []) {
            throw new InvalidArgumentException('Cannot pick elements from empty array');
        }

        if ($count < 1) {
            return [];
        }

        if (!$unique) {
            $result = [];
            for ($i = 0; $i < $count; ++$i) {
                $result[] = $this->randomElement($array);
            }

            return $result;
        }

        $array = array_unique($array, SORT_REGULAR);

        if ($count > count($array)) {
            throw new InvalidArgumentException('Number of unique elements is lower than requested number of elements.');
        }

        $keys = $this->randomizer->pickArrayKeys($array, $count);

        return $this->shuffleElements(array_map(static fn (int|string $key): mixed => $array[$key], $keys));
    }

    public function randomKey(array $array): int|string
    {
        if ($array === []) {
            throw new InvalidArgumentException('Cannot pick element from empty array');
        }

        // slice of one element instead of copying all keys - same index, same result, faster for big arrays
        $index = $this->randomizer->getInt(0, count($array) - 1);

        $key = array_key_first(array_slice($array, $index, 1, true));

        return $key ?? throw new InvalidArgumentException('Cannot pick element from empty array');
    }

    public function weightedElement(array $weights): int|string
    {
        $total = 0.0;
        $lastPositive = null;

        foreach ($weights as $key => $weight) {
            if ($weight < 0) {
                throw new InvalidArgumentException(sprintf('Weight of "%s" cannot be negative', $key));
            }

            if ($weight > 0) {
                $total += $weight;
                $lastPositive = $key;
            }
        }

        if ($lastPositive === null) {
            throw new InvalidArgumentException('At least one weight must be greater than zero');
        }

        $point = $this->randomizer->getFloat(0, $total, IntervalBoundary::ClosedOpen);
        $cumulative = 0.0;

        foreach ($weights as $key => $weight) {
            $cumulative += $weight;

            if ($weight > 0 && $point < $cumulative) {
                return $key;
            }
        }

        // float rounding can leave $point at the very end of the range
        return $lastPositive;
    }

    public function shuffleElements(array $array): array
    {
        return $this->randomizer->shuffleArray($array);
    }
}
