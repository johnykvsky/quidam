<?php

declare(strict_types = 1);

namespace Quidam\Definitions\Addon;

/**
 * Opt-in add-on - registered in DefinitionPack::addons(), loaded on first ext()
 */
interface TextInterface extends AddonInterface
{
    /**
     * New instance generating from given corpus - this one stays unchanged
     */
    public function withCorpus(string $corpus): static;

    /**
     * Text from $min to $max characters, starting at a sentence start and ending at a sentence end when possible,
     * e.g. "Thus I have lived on for many a year – alone, and weary of myself."
     *
     * @param int $indexSize How many previous words decide the next one (1-5): 1 - more random, often nonsense;
     *                       higher - more correct, but closer to the corpus (with small corpus mostly quoting it)
     */
    public function realText(int $min = 50, int $max = 200, int $indexSize = 2): string;
}
