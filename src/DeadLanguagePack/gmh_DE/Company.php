<?php

declare(strict_types = 1);

namespace Quidam\DeadLanguagePack\gmh_DE;

use Quidam\Core\Company as CoreCompany;
use Quidam\Definitions\Enum\Gender;

/**
 * Medieval businesses - "Kuonrât der Smit und sîne süne", "Uote von Ouwe unde Hagene der Lange", craft guilds ("diu
 * zunft der Becken"); the offices of the Burgundian court and town trades as job titles, lines of the Nibelungenlied
 * and the Minnesang as catch phrases
 */
class Company extends CoreCompany
{
    /** @var list<string> */
    protected const array SUFFIX = ['und sîne süne', 'und sîne gesellen', 'und sîne bruoder'];

    /**
     * Crafts with a guild (zunft) of their own
     *
     * @var list<string>
     */
    protected const array NAME_WORD = [
        'Smide', 'Becken', 'Vischer', 'Snîder', 'Kürsenære', 'Goltsmide', 'Koufliute', 'Wollensleher', 'Schuochwürhten',
    ];

    /** @var list<string> */
    protected const array JOB_TITLE = [
        'ritter', 'knappe', 'minnesingære', 'spilman', 'truhsæze', 'schenke', 'kamerære', 'marschalc', 'vogt',
        'koufman', 'smit', 'schrîber', 'becke', 'vischer', 'mülner', 'wirt', 'arzât', 'pfaffe', 'münech',
        'schuolmeister',
    ];

    /** @var list<string> */
    protected const array INDUSTRY = ['Wîn', 'Salz', 'Tuoch', 'Îsen', 'Silber', 'Koufmanschaft', 'Minnesanc', 'Ritterschaft'];

    /**
     * One list - catch phrases are lines of the Nibelungenlied, Walther von der Vogelweide and the Minnesang, whole
     *
     * @var list<list<string>>
     */
    protected const array CATCH_PHRASE_WORDS = [[
        'Uns ist in alten mæren wunders vil geseit', 'Ich saz ûf eime steine', 'Under der linden an der heide',
        'Owê war sint verswunden alliu mîniu jâr', 'Dû bist mîn, ich bin dîn', 'Tandaradei',
    ]];

    /**
     * "Kuonrât der Smit und sîne süne", "Uote von Ouwe unde Hagene der Lange" or a guild: "diu zunft der Becken"
     */
    public function company(): string
    {
        return match ($this->randomizer->getInt(0, 2)) {
            0 => $this->person->name(Gender::Male) . ' ' . $this->companySuffix(),
            1 => $this->person->name() . ' unde ' . $this->person->name(),
            default => 'diu zunft der ' . $this->randomizer->randomElement(static::NAME_WORD),
        };
    }
}
