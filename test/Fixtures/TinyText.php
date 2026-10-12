<?php

declare(strict_types = 1);

namespace Quidam\Test\Fixtures;

use Quidam\Addon\Text;

/**
 * Corpus too small for long texts, with brackets and quotes cut by generation
 */
class TinyText extends Text
{
    protected const string CORPUS = <<<'TEXT'
        Alpha said "beta gamma. Delta epsilon" and (zeta eta. Theta iota) kappa. Lambda ‘mu nu. Xi won’t omicron’ pi.
        Rho sigma.
        TEXT;
}
