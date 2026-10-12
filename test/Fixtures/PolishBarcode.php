<?php

declare(strict_types = 1);

namespace Quidam\Test\Fixtures;

use Quidam\Core\Barcode;

/**
 * Only Polish GS1 prefix (590) and Polish ISBN group (83)
 */
class PolishBarcode extends Barcode
{
    /** @var list<array{int, int}> */
    protected const array GS1_PREFIXES = [[590, 590]];

    /** @var list<string> */
    protected const array ISBN_978_GROUPS = ['83'];

    /** @var list<string> */
    protected const array ISBN_979_GROUPS = ['83'];
}
