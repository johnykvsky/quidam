<?php

declare(strict_types = 1);

namespace Quidam\Definitions\Addon;

/**
 * Opt-in add-on - registered in DefinitionPack::addons(), loaded on first ext()
 */
interface RichTextInterface extends AddonInterface
{
    /**
     * HTML fragment of $blocks block elements (h2/h3, p, ul/ol, blockquote, pre, table, figure)
     */
    public function html(int $blocks = 5): string;

    /**
     * Complete HTML5 document with title, h1 and $blocks block elements
     */
    public function htmlDocument(int $blocks = 5): string;

    /**
     * CommonMark / GitHub flavoured Markdown of $blocks blocks (tables use GFM syntax)
     */
    public function markdown(int $blocks = 5): string;
}
