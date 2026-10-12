<?php

declare(strict_types = 1);

namespace Quidam\Test\Fixtures;

use Quidam\Definitions\Extension\PersonExtensionInterface;
use Quidam\Definitions\Randomizer\RandomizerInterface;

/**
 * Example of user extension - depends on randomizer and other generator, autowired by container
 */
class CarExtension
{
    private const array BRANDS = ['Toyota', 'Ford', 'Fiat'];

    public function __construct(
        private readonly RandomizerInterface $randomizer,
        private readonly PersonExtensionInterface $person,
        private readonly string $prefix = 'Car',
    ) {
    }

    public function brand(): string
    {
        return $this->prefix . ' ' . $this->randomizer->randomElement(self::BRANDS);
    }

    public function owner(): string
    {
        return $this->person->name();
    }
}
