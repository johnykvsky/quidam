<?php

declare(strict_types = 1);

namespace Quidam\Test\Fixtures\Filler;

/**
 * Gender filled into a string and into an application's own enum - one person per object
 */
final class FillerGenders
{
    public function __construct(
        public string $gender,
        public FillerSex $sex,
    ) {
    }
}
