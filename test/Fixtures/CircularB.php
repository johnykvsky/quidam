<?php

declare(strict_types = 1);

namespace Quidam\Test\Fixtures;

final class CircularB
{
    public function __construct(public CircularA $a)
    {
    }
}
