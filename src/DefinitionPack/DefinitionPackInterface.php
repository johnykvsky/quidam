<?php

declare(strict_types = 1);

namespace Quidam\DefinitionPack;

/**
 * Default definitions of Quidam container, by id
 */
interface DefinitionPackInterface
{
    /**
     * Randomizers, strategy, clock, locale, transliterator
     *
     * @return array<string, mixed>
     */
    public function coreDefinitions(): array;

    /**
     * Check digit calculators
     *
     * @return array<string, mixed>
     */
    public function calculators(): array;

    /**
     * Extensions (generators)
     *
     * @return array<string, mixed>
     */
    public function extensions(): array;

    /**
     * Opt-in add-ons (Template, Filler, Text, RichText, EdgeCases) - lazy, loaded on first use
     *
     * @return array<string, mixed>
     */
    public function addons(): array;

    /**
     * All of the above
     *
     * @return array<string, mixed>
     */
    public function all(): array;
}
