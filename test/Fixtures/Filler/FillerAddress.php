<?php

declare(strict_types = 1);

namespace Quidam\Test\Fixtures\Filler;

final readonly class FillerAddress
{
    public function __construct(
        public string $street,
        public string $city,
        public string $zipCode,
        public string $country,
    ) {
    }
}
