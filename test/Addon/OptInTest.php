<?php

declare(strict_types = 1);

namespace Quidam\Test\Addon;

use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use Quidam\Addon\Text;
use Quidam\Definitions\Addon\TextInterface;
use Quidam\Definitions\Extension\PersonExtensionInterface;
use Quidam\Quidam;

final class OptInTest extends TestCase
{
    #[RunInSeparateProcess]
    public function testAddonsAreLoadedOnlyWhenUsed(): void
    {
        $generator = Quidam::create();
        $generator->ext(PersonExtensionInterface::class)->name();

        self::assertFalse(class_exists(Text::class, false), 'registered, but not loaded');

        $generator->ext(TextInterface::class)->realText(20, 60);
        self::assertTrue(class_exists(Text::class, false));
    }
}
