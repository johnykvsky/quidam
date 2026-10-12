<?php

declare(strict_types = 1);

namespace Quidam\Definitions\Addon;

/**
 * Opt-in add-on - registered in DefinitionPack::addons(), loaded on first ext()
 */
interface FillerInterface extends AddonInterface
{
    /**
     * Copy which leaves nullable values null with given chance (int as percent, float as probability) - by default
     * nullable values are always filled
     */
    public function withNullChance(int|float $chance): static;

    /**
     * Object of given class - constructor parameters and uninitialized public properties filled
     *
     * @param class-string<T> $class
     * @param array<string, mixed> $overrides name => value, or Closure returning the value
     * @return T
     *
     * @template T of object
     */
    public function make(string $class, array $overrides = []): object;

    /**
     * Array with given keys, values guessed by key name (lorem word when no rule matches), i.e. for API payloads:
     *   array(['id', 'email', 'firstName', 'createdAt'])
     *
     * @param list<string> $keys
     * @param array<string, mixed> $overrides key => value, or Closure returning the value
     * @return array<string, mixed>
     */
    public function array(array $keys, array $overrides = []): array;
}
