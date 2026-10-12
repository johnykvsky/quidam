<?php

declare(strict_types = 1);

namespace Quidam\Test\Fixtures;

use Quidam\Core\Person;
use Quidam\Definitions\Enum\Gender;

/**
 * Surname depends on gender - like Polish Kowalski / Kowalska
 */
class GenderedSurnamePerson extends Person
{
    public function lastName(?Gender $gender = null): string
    {
        return $this->resolveGender($gender) === Gender::Female ? 'Kowalska' : 'Kowalski';
    }
}
