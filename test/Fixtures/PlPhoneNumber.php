<?php

declare(strict_types = 1);

namespace Quidam\Test\Fixtures;

use Quidam\Core\PhoneNumber;

/**
 * Polish numbers - no trunk prefix, 9 digits; calling code comes from the locale
 */
final class PlPhoneNumber extends PhoneNumber
{
    /** @var list<string> */
    protected const array FORMATS = ['%# ### ## ##'];

    /** @var list<string> */
    protected const array MOBILE_FORMATS = ['5## ### ###', '6## ### ###', '7## ### ###', '8## ### ###'];
}
