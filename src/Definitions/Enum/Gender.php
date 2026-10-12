<?php

declare(strict_types = 1);

namespace Quidam\Definitions\Enum;

/**
 * Gender of a person - selects names, titles and, in languages that have them, gendered surnames (Kowalski / Kowalska)
 */
enum Gender: string
{
    case Male = 'male';
    case Female = 'female';
}
