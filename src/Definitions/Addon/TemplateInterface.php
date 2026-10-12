<?php

declare(strict_types = 1);

namespace Quidam\Definitions\Addon;

/**
 * Opt-in add-on - registered in DefinitionPack::addons(), loaded on first ext()
 */
interface TemplateInterface extends AddonInterface
{
    public function parse(string $template): string;

    /**
     * Result of one expression as returned by the generator (not converted to string), i.e.
     *   evaluate('number.numberBetween(1, 10)') → 7, evaluate('profile.person') → array
     */
    public function evaluate(string $expression): mixed;

    /**
     * Aliases usable in tokens and expressions - alias => container id, i.e. 'person' => PersonExtensionInterface::class
     *
     * @return array<string, string>
     */
    public function aliases(): array;
}
