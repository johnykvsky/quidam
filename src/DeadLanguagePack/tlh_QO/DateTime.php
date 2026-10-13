<?php

declare(strict_types = 1);

namespace Quidam\DeadLanguagePack\tlh_QO;

use DateTimeInterface;
use Quidam\Core\DateTime as CoreDateTime;

/**
 * Stardates - the year and the day of the year: "Stardate 2026.285" (12 October 2026). The Empire's own calendar
 * isn't known, so the pack uses the one starships log in.
 */
class DateTime extends CoreDateTime
{
    /**
     * Stardate of given or random date, e.g. "Stardate 2026.285" - the locale's long date format doesn't apply
     */
    public function longDate(?DateTimeInterface $date = null): string
    {
        $date ??= $this->dateTime();

        return sprintf('Stardate %s.%d', $date->format('Y'), (int) $date->format('z') + 1);
    }
}
