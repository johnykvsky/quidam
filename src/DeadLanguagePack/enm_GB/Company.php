<?php

declare(strict_types = 1);

namespace Quidam\DeadLanguagePack\enm_GB;

use Quidam\Core\Company as CoreCompany;

/**
 * Medieval businesses - "Johan le Smyth and his felawes", "Alis atte Welle and Water Longe", craft guilds ("the
 * Mistere of Goldsmythes"); Chaucer's pilgrims as job titles, his lines as catch phrases
 */
class Company extends CoreCompany
{
    /** @var list<string> */
    protected const array SUFFIX = ['and his felawes', 'and his sones', 'and Compaignye'];

    /**
     * Crafts with a guild (mistery) of their own
     *
     * @var list<string>
     */
    protected const array NAME_WORD = [
        'Mercers', 'Grocers', 'Drapers', 'Fisshmongers', 'Goldsmythes', 'Skynners', 'Taillours', 'Vynteners',
        'Brewers', 'Bakers', 'Cordwaners', 'Sadelers', 'Bocheres',
    ];

    /**
     * The pilgrims of the Canterbury Tales
     *
     * @var list<string>
     */
    protected const array JOB_TITLE = [
        'Knyght', 'Squyer', 'Yeman', 'Prioresse', 'Monk', 'Frere', 'Marchant', 'Clerk', 'Sergeant of the Lawe',
        'Frankeleyn', 'Haberdasshere', 'Carpenter', 'Webbe', 'Dyere', 'Tapycer', 'Cook', 'Shipman',
        'Doctour of Phisik', 'Wyf of Bathe', 'Persoun', 'Plowman', 'Millere', 'Maunciple', 'Reve', 'Somonour',
        'Pardoner', 'Hoost',
    ];

    /** @var list<string> */
    protected const array INDUSTRY = ['Wolle', 'Clooth', 'Wyn', 'Ale', 'Lawe', 'Phisik', 'Pilgrymage', 'Chivalrie', 'Husbondrie'];

    /**
     * One list - catch phrases are lines of Chaucer, whole
     *
     * @var list<list<string>>
     */
    protected const array CATCH_PHRASE_WORDS = [[
        'Whan that Aprill with his shoures soote', 'The lyf so short, the craft so long to lerne', 'Mordre wol out',
        'Trouthe is the hyeste thyng that man may kepe', 'Ful wys is he that kan hymselven knowe',
        'Gladly wolde he lerne and gladly teche', 'Hit is nat al gold that glareth', 'Pacience is an heigh vertu',
    ]];

    /**
     * "Johan le Smyth and his felawes", "Alis atte Welle and Water Longe" or a guild: "the Mistere of Goldsmythes"
     */
    public function company(): string
    {
        $name = $this->person->name();

        return match ($this->randomizer->getInt(0, 2)) {
            0 => $name . ' ' . $this->companySuffix(),
            1 => $name . ' and ' . $this->person->name(),
            default => 'the Mistere of ' . $this->randomizer->randomElement(static::NAME_WORD),
        };
    }
}
