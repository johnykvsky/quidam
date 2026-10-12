<?php

declare(strict_types = 1);

namespace Quidam\Test\Fixtures;

use Quidam\Core\DateTime;

/**
 * Polish dates - data only, as a language pack does it
 */
final class PlDateTime extends DateTime
{
    /** @var list<string> */
    protected const array MONTHS = [
        'styczeń', 'luty', 'marzec', 'kwiecień', 'maj', 'czerwiec', 'lipiec', 'sierpień', 'wrzesień', 'październik',
        'listopad', 'grudzień',
    ];

    /** @var list<string> */
    protected const array MONTHS_IN_DATES = [
        'stycznia', 'lutego', 'marca', 'kwietnia', 'maja', 'czerwca', 'lipca', 'sierpnia', 'września', 'października',
        'listopada', 'grudnia',
    ];
}
