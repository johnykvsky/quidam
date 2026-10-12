<?php

declare(strict_types = 1);

namespace Quidam\Test\Generator;

use Quidam\Definitions\Extension\SystemExtensionInterface;

final class SystemTest extends GeneratorTestCase
{
    public function testCronExpression(): void
    {
        $system = $this->dummy->ext(SystemExtensionInterface::class);

        for ($i = 0; $i < self::REPEAT * 4; ++$i) {
            $fields = explode(' ', $system->cronExpression());
            self::assertCount(5, $fields);
            [$minute, $hour, $dayOfMonth, $month, $dayOfWeek] = $fields;

            self::assertTrue(self::validField($minute, 0, 59), 'minute ' . $minute);
            self::assertTrue(self::validField($hour, 0, 23), 'hour ' . $hour);
            self::assertTrue(self::validField($dayOfMonth, 1, 31), 'day ' . $dayOfMonth);
            self::assertTrue(self::validField($month, 1, 12), 'month ' . $month);
            self::assertTrue(self::validField($dayOfWeek, 0, 6), 'weekday ' . $dayOfWeek);
            self::assertTrue($dayOfMonth === '*' || $dayOfWeek === '*', 'only one of day fields restricted');
        }
    }

    public function testLogLevelAndEnvironmentVariable(): void
    {
        $system = $this->dummy->ext(SystemExtensionInterface::class);

        $this->assertAlwaysMatches('/^(debug|info|notice|warning|error|critical|alert|emergency)$/', $system->logLevel(...));
        $this->assertAlwaysMatches('/^[A-Z][A-Z0-9]*_[A-Z]+$/', $system->environmentVariable(...));
    }

    private static function validField(string $field, int $min, int $max): bool
    {
        foreach (explode(',', $field) as $part) {
            if (preg_match('/^(\*|(\d+)(-(\d+))?)(\/(\d+))?$/', $part, $match) !== 1) {
                return false;
            }

            foreach ([$match[2] ?? '', $match[4] ?? ''] as $number) {
                if ($number !== '' && ((int) $number < $min || (int) $number > $max)) {
                    return false;
                }
            }
        }

        return true;
    }
}
