<?php

declare(strict_types = 1);

namespace Quidam\Test\Fixtures;

use Quidam\Core\Person;

/**
 * Language pack style extension - only data is overridden
 */
class PlPerson extends Person
{
    /** @var list<string> */
    protected const array FIRST_NAME_MALE = ['Jan', 'Piotr', 'Krzysztof', 'Andrzej', 'Tomasz'];

    /** @var list<string> */
    protected const array FIRST_NAME_FEMALE = ['Anna', 'Maria', 'Katarzyna', 'Małgorzata', 'Agnieszka'];

    /** @var list<string> */
    protected const array FIRST_NAME_NEUTRAL = [];

    /** @var list<string> */
    protected const array LAST_NAME = ['Nowak', 'Kowalski', 'Wiśniewski', 'Wójcik', 'Kowalczyk'];

    /** @var list<string> */
    protected const array TITLE_MALE = ['Pan'];

    /** @var list<string> */
    protected const array TITLE_FEMALE = ['Pani'];
}
