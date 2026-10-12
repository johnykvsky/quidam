<?php

declare(strict_types = 1);

namespace Quidam\Definitions\Transliterator;

use Quidam\Definitions\DefinitionInterface;

/**
 * Converts text to ASCII - used for user names, emails and domains built from names of language packs
 * ("Łukasz Wójcik" gives "lukasz.wojcik@example.com")
 */
interface TransliteratorInterface extends DefinitionInterface
{
    /**
     * ASCII version of the text, e.g. "Łukasz Żółć" gives "Lukasz Zolc" - characters without ASCII equivalent removed
     */
    public function transliterate(string $text): string;

    /**
     * Lowercase ASCII letters and digits, groups joined with $separator, e.g. "Żółta Łódź!" gives "zolta-lodz"
     */
    public function slug(string $text, string $separator = '-'): string;
}
