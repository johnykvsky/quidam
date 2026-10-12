<?php

declare(strict_types = 1);

namespace Quidam\Definitions\Extension;

interface LoremExtensionInterface extends ExtensionInterface
{
    public function word(): string;

    /** @return list<string> */
    public function words(int $wordCount = 3): array;

    /** @param bool $variableWordCount If true, word count is randomized by +/- 40% */
    public function sentence(int $wordCount = 6, bool $variableWordCount = true): string;

    /** @return list<string> */
    public function sentences(int $sentenceCount = 3): array;

    /** @param bool $variableSentenceCount If true, sentence count is randomized by +/- 40% */
    public function paragraph(int $sentenceCount = 3, bool $variableSentenceCount = true): string;

    /** @return list<string> */
    public function paragraphs(int $paragraphCount = 3): array;

    /**
     * Text no longer than $maxCharacters. Built from paragraphs (>= 100 chars), sentences (>= 25 chars) or words.
     */
    public function text(int $maxCharacters = 200): string;
}
