<?php

declare(strict_types = 1);

namespace Quidam\Definitions\Extension;

interface LanguageExtensionInterface extends ExtensionInterface
{
    /**
     * English name of a language, e.g. "Polish"
     */
    public function language(): string;

    /**
     * ISO 639-1 language code, e.g. "pl"
     */
    public function languageCode(): string;

    /**
     * Matching code and name of one language, e.g. ['code' => 'pl', 'name' => 'Polish']
     *
     * @return array{code: string, name: string}
     */
    public function languageData(): array;

    /**
     * Locale with language and country code, e.g. "pl_PL", or "pl-PL" (BCP 47 style) with "-" separator
     *
     * @param string $separator "_" or "-"
     */
    public function locale(string $separator = '_'): string;
}
