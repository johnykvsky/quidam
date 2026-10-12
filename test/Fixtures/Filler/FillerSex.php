<?php

declare(strict_types = 1);

namespace Quidam\Test\Fixtures\Filler;

enum FillerSex: string
{
    case Male = 'male';
    case Female = 'female';
    case Other = 'other';
}
