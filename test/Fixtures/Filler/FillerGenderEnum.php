<?php

declare(strict_types = 1);

namespace Quidam\Test\Fixtures\Filler;

use Quidam\Definitions\Enum\Gender;

final class FillerGenderEnum
{
    public function __construct(public Gender $gender)
    {
    }
}
