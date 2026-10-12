<?php

declare(strict_types = 1);

namespace Quidam\Test\Generator;

use Quidam\Definitions\Extension\ColorExtensionInterface;
use Quidam\Definitions\Randomizer\RandomizerInterface;
use Quidam\Quidam;
use Quidam\Test\Fixtures\EdgeRandomizer;

final class ColorTest extends GeneratorTestCase
{
    public function testHexColors(): void
    {
        $color = $this->dummy->ext(ColorExtensionInterface::class);

        $this->assertAlwaysMatches('/^#[0-9a-f]{6}$/', $color->hexColor(...));
        $this->assertAlwaysMatches('/^#([0-9a-f])\1([0-9a-f])\2([0-9a-f])\3$/', $color->safeHexColor(...));
    }

    public function testFullRangeIsReachable(): void
    {
        $lowest = Quidam::create()->withDefinition(RandomizerInterface::class, new EdgeRandomizer(highest: false))->ext(ColorExtensionInterface::class);
        $highest = Quidam::create()->withDefinition(RandomizerInterface::class, new EdgeRandomizer(highest: true))->ext(ColorExtensionInterface::class);

        self::assertSame('#000000', $lowest->hexColor());
        self::assertSame('#ffffff', $highest->hexColor());
        self::assertSame('#000000', $lowest->safeHexColor());
        self::assertSame('#ffffff', $highest->safeHexColor());
        self::assertSame('hsl(359, 100%, 100%)', $highest->hslCssColor());
    }

    public function testSafeHexUsesAllChannels(): void
    {
        $reds = [];
        for ($i = 0; $i < 500; ++$i) {
            $reds[$this->dummy->ext(ColorExtensionInterface::class)->safeHexColor()[1]] = true;
        }

        self::assertGreaterThan(10, count($reds));
    }

    public function testRgbAndHsl(): void
    {
        $color = $this->dummy->ext(ColorExtensionInterface::class);

        for ($i = 0; $i < self::REPEAT; ++$i) {
            foreach ($color->rgbColorAsArray() as $channel) {
                self::assertGreaterThanOrEqual(0, $channel);
                self::assertLessThanOrEqual(255, $channel);
            }

            [$hue, $saturation, $lightness] = $color->hslColorAsArray();
            self::assertLessThanOrEqual(359, $hue);
            self::assertLessThanOrEqual(100, $saturation);
            self::assertLessThanOrEqual(100, $lightness);
        }

        $this->assertAlwaysMatches('/^\d{1,3},\d{1,3},\d{1,3}$/', $color->rgbColor(...));
        $this->assertAlwaysMatches('/^rgb\(\d{1,3},\d{1,3},\d{1,3}\)$/', $color->rgbCssColor(...));
        $this->assertAlwaysMatches('/^rgba\(\d{1,3},\d{1,3},\d{1,3},(0|1|0\.\d{1,2})\)$/', $color->rgbaCssColor(...));
        $this->assertAlwaysMatches('/^\d{1,3},\d{1,3},\d{1,3}$/', $color->hslColor(...));
        $this->assertAlwaysMatches('/^hsl\(\d{1,3}, \d{1,3}%, \d{1,3}%\)$/', $color->hslCssColor(...));
    }

    public function testNames(): void
    {
        $color = $this->dummy->ext(ColorExtensionInterface::class);

        $this->assertAlwaysMatches('/^(aqua|black|blue|fuchsia|gray|green|lime|maroon|navy|olive|purple|red|silver|teal|white|yellow)$/', $color->safeColorName(...));
        $this->assertAlwaysMatches('/^([A-Z][a-z]+)+$/', $color->colorName(...));

        $names = [];
        for ($i = 0; $i < 3000; ++$i) {
            $names[$color->colorName()] = true;
        }

        self::assertCount(148, $names);
        self::assertArrayHasKey('RebeccaPurple', $names);
        self::assertArrayHasKey('DarkOrange', $names);
    }
}
