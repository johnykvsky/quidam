<?php

declare(strict_types = 1);

namespace Quidam\LanguagePack\pl_PL;

use Quidam\Core\DateTime as CoreDateTime;

/**
 * Polish month and day names - "9 października 2026"; date formats ("09.10.2026") come from the locale
 */
class DateTime extends CoreDateTime
{
    /** @var list<string> */
    protected const array MONTHS = [
        'styczeń', 'luty', 'marzec', 'kwiecień', 'maj', 'czerwiec', 'lipiec', 'sierpień', 'wrzesień',
        'październik', 'listopad', 'grudzień',
    ];

    /**
     * Months in long dates - genitive: "9 października"
     *
     * @var list<string>
     */
    protected const array MONTHS_IN_DATES = [
        'stycznia', 'lutego', 'marca', 'kwietnia', 'maja', 'czerwca', 'lipca', 'sierpnia', 'września', 'października',
        'listopada', 'grudnia',
    ];

    /** @var list<string> */
    protected const array DAYS = ['poniedziałek', 'wtorek', 'środa', 'czwartek', 'piątek', 'sobota', 'niedziela'];
}
