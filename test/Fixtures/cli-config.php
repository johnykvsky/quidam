<?php

declare(strict_types = 1);

use Psr\Clock\ClockInterface;
use Quidam\Addon\Template;
use Quidam\Definitions\Extension\PersonExtensionInterface;
use Quidam\Definitions\Randomizer\RandomizerInterface;
use Quidam\Quidam;
use Quidam\Test\Fixtures\CarExtension;

// CLI config (quidam --config): own extension built with an argument, with its own template alias
return static fn (?int $seed, ClockInterface $clock): Quidam => Quidam::create($seed, $clock)
    ->withDefinition(
        CarExtension::class,
        static fn (RandomizerInterface $randomizer, PersonExtensionInterface $person): CarExtension => new CarExtension($randomizer, $person, 'Auto'),
    )
    ->withDefinition(Template::alias('car'), CarExtension::class);
