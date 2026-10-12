<?php

declare(strict_types = 1);

namespace Quidam\Test\Fixtures;

use Quidam\Core\Person;

/**
 * One name and title per gender - for checking that values match gender
 */
class GenderedPerson extends Person
{
    /** @var list<string> */
    protected const array FIRST_NAME_MALE = ['John'];

    /** @var list<string> */
    protected const array FIRST_NAME_FEMALE = ['Jane'];

    /** @var list<string> */
    protected const array FIRST_NAME_NEUTRAL = [];

    /** @var list<string> */
    protected const array TITLE_MALE = ['Mr.'];

    /** @var list<string> */
    protected const array TITLE_FEMALE = ['Mrs.'];
}
