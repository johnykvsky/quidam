<?php

declare(strict_types = 1);

namespace Quidam\Core;

use Quidam\Definitions\Extension\ColorExtensionInterface;
use Quidam\Definitions\Randomizer\RandomizerInterface;

class Color implements ColorExtensionInterface
{
    /**
     * 16 basic HTML 4 / CSS colors
     *
     * @var list<string>
     */
    protected const array SAFE_COLOR_NAMES = [
        'aqua', 'black', 'blue', 'fuchsia', 'gray', 'green', 'lime', 'maroon',
        'navy', 'olive', 'purple', 'red', 'silver', 'teal', 'white', 'yellow',
    ];

    /**
     * All 148 CSS named colors (CSS Color Module Level 4)
     *
     * @var list<string>
     *
     * @see https://www.w3.org/TR/css-color-4/#named-colors
     */
    protected const array COLOR_NAMES = [
        'AliceBlue', 'AntiqueWhite', 'Aqua', 'Aquamarine', 'Azure', 'Beige', 'Bisque', 'Black', 'BlanchedAlmond',
        'Blue', 'BlueViolet', 'Brown', 'BurlyWood', 'CadetBlue', 'Chartreuse', 'Chocolate', 'Coral', 'CornflowerBlue',
        'Cornsilk', 'Crimson', 'Cyan', 'DarkBlue', 'DarkCyan', 'DarkGoldenRod', 'DarkGray', 'DarkGreen', 'DarkGrey',
        'DarkKhaki', 'DarkMagenta', 'DarkOliveGreen', 'DarkOrange', 'DarkOrchid', 'DarkRed', 'DarkSalmon',
        'DarkSeaGreen', 'DarkSlateBlue', 'DarkSlateGray', 'DarkSlateGrey', 'DarkTurquoise', 'DarkViolet', 'DeepPink',
        'DeepSkyBlue', 'DimGray', 'DimGrey', 'DodgerBlue', 'FireBrick', 'FloralWhite', 'ForestGreen', 'Fuchsia',
        'Gainsboro', 'GhostWhite', 'Gold', 'GoldenRod', 'Gray', 'Green', 'GreenYellow', 'Grey', 'HoneyDew', 'HotPink',
        'IndianRed', 'Indigo', 'Ivory', 'Khaki', 'Lavender', 'LavenderBlush', 'LawnGreen', 'LemonChiffon', 'LightBlue',
        'LightCoral', 'LightCyan', 'LightGoldenRodYellow', 'LightGray', 'LightGreen', 'LightGrey', 'LightPink',
        'LightSalmon', 'LightSeaGreen', 'LightSkyBlue', 'LightSlateGray', 'LightSlateGrey', 'LightSteelBlue',
        'LightYellow', 'Lime', 'LimeGreen', 'Linen', 'Magenta', 'Maroon', 'MediumAquaMarine', 'MediumBlue',
        'MediumOrchid', 'MediumPurple', 'MediumSeaGreen', 'MediumSlateBlue', 'MediumSpringGreen', 'MediumTurquoise',
        'MediumVioletRed', 'MidnightBlue', 'MintCream', 'MistyRose', 'Moccasin', 'NavajoWhite', 'Navy', 'OldLace',
        'Olive', 'OliveDrab', 'Orange', 'OrangeRed', 'Orchid', 'PaleGoldenRod', 'PaleGreen', 'PaleTurquoise',
        'PaleVioletRed', 'PapayaWhip', 'PeachPuff', 'Peru', 'Pink', 'Plum', 'PowderBlue', 'Purple', 'RebeccaPurple',
        'Red', 'RosyBrown', 'RoyalBlue', 'SaddleBrown', 'Salmon', 'SandyBrown', 'SeaGreen', 'SeaShell', 'Sienna',
        'Silver', 'SkyBlue', 'SlateBlue', 'SlateGray', 'SlateGrey', 'Snow', 'SpringGreen', 'SteelBlue', 'Tan', 'Teal',
        'Thistle', 'Tomato', 'Turquoise', 'Violet', 'Wheat', 'White', 'WhiteSmoke', 'Yellow', 'YellowGreen',
    ];

    public function __construct(
        protected readonly RandomizerInterface $randomizer,
    ) {
    }

    /**
     * e.g. "#a3c2f0"
     */
    public function hexColor(): string
    {
        return sprintf('#%06x', $this->randomizer->getInt(0, 0xffffff));
    }

    /**
     * Color expressible in short #rgb form, written in full, e.g. "#aa33ff"
     */
    public function safeHexColor(): string
    {
        [$r, $g, $b] = str_split(sprintf('%03x', $this->randomizer->getInt(0, 0xfff)));

        return '#' . $r . $r . $g . $g . $b . $b;
    }

    /** @return array{int, int, int} red, green, blue (0-255 each) */
    public function rgbColorAsArray(): array
    {
        return [
            $this->randomizer->getInt(0, 255),
            $this->randomizer->getInt(0, 255),
            $this->randomizer->getInt(0, 255),
        ];
    }

    /**
     * e.g. "163,194,240"
     */
    public function rgbColor(): string
    {
        return implode(',', $this->rgbColorAsArray());
    }

    /**
     * e.g. "rgb(163,194,240)"
     */
    public function rgbCssColor(): string
    {
        return sprintf('rgb(%s)', $this->rgbColor());
    }

    /**
     * e.g. "rgba(163,194,240,0.45)"
     */
    public function rgbaCssColor(): string
    {
        return sprintf('rgba(%s,%s)', $this->rgbColor(), round($this->randomizer->getFloat(0.0, 1.0), 2));
    }

    /**
     * Hue (0-359), saturation (0-100), lightness (0-100)
     *
     * @return array{int, int, int}
     */
    public function hslColorAsArray(): array
    {
        return [
            $this->randomizer->getInt(0, 359),
            $this->randomizer->getInt(0, 100),
            $this->randomizer->getInt(0, 100),
        ];
    }

    /**
     * e.g. "210,75,80"
     */
    public function hslColor(): string
    {
        return implode(',', $this->hslColorAsArray());
    }

    /**
     * e.g. "hsl(210, 75%, 80%)"
     */
    public function hslCssColor(): string
    {
        return vsprintf('hsl(%d, %d%%, %d%%)', $this->hslColorAsArray());
    }

    /**
     * One of 16 basic colors, lowercase, e.g. "teal"
     */
    public function safeColorName(): string
    {
        return $this->randomizer->randomElement(static::SAFE_COLOR_NAMES);
    }

    /**
     * One of 148 CSS named colors, e.g. "CornflowerBlue"
     */
    public function colorName(): string
    {
        return $this->randomizer->randomElement(static::COLOR_NAMES);
    }
}
