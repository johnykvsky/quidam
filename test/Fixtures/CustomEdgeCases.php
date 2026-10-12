<?php

declare(strict_types = 1);

namespace Quidam\Test\Fixtures;

use Quidam\Addon\EdgeCases;

class CustomEdgeCases extends EdgeCases
{
    /** @var array<string, list<string>> */
    protected const array CASES = [...parent::CASES, 'sku' => ['SKU-']];
}
