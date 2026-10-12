<?php

declare(strict_types = 1);

namespace Quidam\Definitions\Extension;

interface ColorExtensionInterface extends ExtensionInterface
{
    /**
     * e.g. "#a3c2f0"
     */
    public function hexColor(): string;

    /**
     * Color expressible in short #rgb form, written in full, e.g. "#aa33ff"
     */
    public function safeHexColor(): string;

    /** @return array{int, int, int} red, green, blue (0-255 each) */
    public function rgbColorAsArray(): array;

    /**
     * e.g. "163,194,240"
     */
    public function rgbColor(): string;

    /**
     * e.g. "rgb(163,194,240)"
     */
    public function rgbCssColor(): string;

    /**
     * e.g. "rgba(163,194,240,0.45)"
     */
    public function rgbaCssColor(): string;

    /**
     * Hue (0-359), saturation (0-100), lightness (0-100)
     *
     * @return array{int, int, int}
     */
    public function hslColorAsArray(): array;

    /**
     * e.g. "210,75,80"
     */
    public function hslColor(): string;

    /**
     * e.g. "hsl(210, 75%, 80%)"
     */
    public function hslCssColor(): string;

    /**
     * One of 16 basic colors, lowercase, e.g. "teal"
     */
    public function safeColorName(): string;

    /**
     * One of 148 CSS named colors, e.g. "CornflowerBlue"
     */
    public function colorName(): string;
}
