<?php

declare(strict_types = 1);

namespace Quidam\Test\Fixtures;

final class CircularA
{
    public function __construct(public CircularB $b)
    {
    }
}
