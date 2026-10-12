<?php

declare(strict_types = 1);

namespace Quidam\Test\Generator;

use Quidam\Definitions\Extension\BloodExtensionInterface;

final class BloodTest extends GeneratorTestCase
{
    public function testFormats(): void
    {
        $blood = $this->dummy->ext(BloodExtensionInterface::class);

        $this->assertAlwaysMatches('/^(A|B|AB|O)$/', $blood->bloodType(...));
        $this->assertAlwaysMatches('/^[+-]$/', $blood->bloodRh(...));
        $this->assertAlwaysMatches('/^(A|B|AB|O)[+-]$/', $blood->bloodGroup(...));
    }

    public function testRealisticFrequency(): void
    {
        $groups = [];
        for ($i = 0; $i < 10000; ++$i) {
            $group = $this->dummy->ext(BloodExtensionInterface::class)->bloodGroup();
            $groups[$group] = ($groups[$group] ?? 0) + 1;
        }

        self::assertCount(8, $groups);
        // O+ ~38%, AB- ~1%
        self::assertGreaterThan(3400, $groups['O+']);
        self::assertLessThan(250, $groups['AB-']);

        $negative = 0;
        for ($i = 0; $i < 10000; ++$i) {
            $negative += $this->dummy->ext(BloodExtensionInterface::class)->bloodRh() === '-' ? 1 : 0;
        }

        // Rh- ~16%
        self::assertGreaterThan(1300, $negative);
        self::assertLessThan(1900, $negative);
    }
}
