<?php

declare(strict_types = 1);

namespace Quidam\Test\Fixtures;

final class UnresolvableExtension
{
    public function __construct(public string $required)
    {
    }
}
