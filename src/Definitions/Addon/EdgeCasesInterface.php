<?php

declare(strict_types = 1);

namespace Quidam\Definitions\Addon;

/**
 * Opt-in add-on - registered in DefinitionPack::addons(), loaded on first ext()
 */
interface EdgeCasesInterface extends AddonInterface
{
    /**
     * Random edge case string, from given category or from all of them
     */
    public function string(?string $category = null): string;

    /**
     * All strings of given category (or all categories) - i.e. for data providers
     *
     * @return list<string>
     */
    public function strings(?string $category = null): array;

    /** @return list<string> */
    public function categories(): array;

    /**
     * String of exactly $length characters - ASCII letters, or multibyte UTF-8 ones (then byte length is 2-4 times
     * higher) - for length limits, column sizes, truncation
     */
    public function longString(int $length = 10000, bool $multibyte = false): string;
}
