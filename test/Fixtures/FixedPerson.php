<?php

declare(strict_types = 1);

namespace Quidam\Test\Fixtures;

use Quidam\Core\Person;
use Quidam\Definitions\Enum\Gender;

final class FixedPerson extends Person
{
    public function lastName(?Gender $gender = null): string
    {
        return 'Fixed';
    }

    public function firstName(?Gender $gender = null): string
    {
        return 'Johny';
    }
}
