<?php

declare(strict_types = 1);

namespace Quidam\Test\Fixtures;

use Quidam\Core\Person;

/**
 * Language writing the family name first (i.e. Hungarian "Kovács Anna")
 */
final class FamilyNameFirstPerson extends Person
{
    protected const string NAME_FORMAT = '{lastName} {firstName}';
}
