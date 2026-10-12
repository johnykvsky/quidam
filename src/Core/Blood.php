<?php

declare(strict_types = 1);

namespace Quidam\Core;

use Quidam\Definitions\Extension\BloodExtensionInterface;
use Quidam\Definitions\Randomizer\RandomizerInterface;

/**
 * ABO blood type with Rh factor, picked with realistic frequency
 */
class Blood implements BloodExtensionInterface
{
    /**
     * Blood group => share in population (percent, approximate - varies by country)
     *
     * @var array<string, float>
     *
     * @see https://en.wikipedia.org/wiki/Blood_type_distribution_by_country
     */
    protected const array GROUPS = [
        'O+' => 38.0, 'A+' => 34.0, 'B+' => 9.0, 'AB+' => 3.0,
        'O-' => 7.0, 'A-' => 6.0, 'B-' => 2.0, 'AB-' => 1.0,
    ];

    public function __construct(
        protected readonly RandomizerInterface $randomizer,
    ) {
    }

    /**
     * ABO type: "A", "B", "AB" or "O"
     */
    public function bloodType(): string
    {
        return substr($this->bloodGroup(), 0, -1);
    }

    /**
     * Rh factor: "+" or "-"
     */
    public function bloodRh(): string
    {
        return substr($this->bloodGroup(), -1);
    }

    /**
     * Type with Rh factor, e.g. "A+", "O-"
     */
    public function bloodGroup(): string
    {
        return (string) $this->randomizer->weightedElement(static::GROUPS);
    }
}
