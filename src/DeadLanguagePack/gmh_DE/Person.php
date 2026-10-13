<?php

declare(strict_types = 1);

namespace Quidam\DeadLanguagePack\gmh_DE;

use Quidam\Core\Person as CorePerson;
use Quidam\Definitions\Enum\Gender;

/**
 * Middle High German names, normalized spelling - heroes of the Nibelungenlied and the Arthurian romances, poets of
 * the Minnesang; a byname by place ("Walther von der Vogelweide"), trade ("Kuonrât der Smit", "Uote diu Smidinne")
 * or looks ("Hagene der Lange")
 */
class Person extends CorePerson
{
    /** @var list<string> */
    protected const array FIRST_NAME_MALE = [
        'Albrecht', 'Bertolt', 'Dancwart', 'Dietrîch', 'Eckehart', 'Êrec', 'Etzel', 'Friderîch', 'Gâwân', 'Gêrnôt',
        'Gîselher', 'Gotfrit', 'Gunther', 'Hagene', 'Hartman', 'Heinrîch', 'Hildebrant', 'Îwein', 'Kuonrât', 'Liupolt',
        'Lûdewîc', 'Otte', 'Parzivâl', 'Rûdolf', 'Rüedegêr', 'Sîvrit', 'Tristan', 'Uolrîch', 'Volkêr', 'Walther',
        'Wolfram',
    ];

    /** @var list<string> */
    protected const array FIRST_NAME_FEMALE = [
        'Adelheit', 'Diemuot', 'Elsebêt', 'Enîte', 'Gêrlint', 'Gêrtrût', 'Gotelint', 'Herrât', 'Herzeloyde',
        'Hildegunt', 'Irmengart', 'Isôt', 'Kriemhilt', 'Kundrîe', 'Kunigunt', 'Lûnete', 'Mechthilt', 'Prünhilt',
        'Sigelint', 'Uote',
    ];

    /** @var list<string> */
    protected const array FIRST_NAME_NEUTRAL = [];

    /**
     * Bynames by place - the same for women and men; some are poets' ("von der Vogelweide", "von Eschenbach")
     *
     * @var list<string>
     */
    protected const array LAST_NAME = [
        'von der Vogelweide', 'von Eschenbach', 'von Ouwe', 'von Strâzburc', 'von Kürenberc', 'von Tronege',
        'von Bechelâren', 'von Wormez', 'von Regensburc', 'von der Linden', 'ûf dem Berge', 'an dem Bache',
    ];

    /**
     * Bynames by trade and looks as [masculine, feminine] - "der Smit" / "diu Smidinne"
     *
     * @var list<array{string, string}>
     */
    protected const array BYNAMES = [
        ['der Smit', 'diu Smidinne'], ['der Becke', 'diu Beckinne'], ['der Schrîber', 'diu Schrîberinne'],
        ['der Vischer', 'diu Vischerinne'], ['der Mülner', 'diu Mülnerinne'], ['der Wirt', 'diu Wirtinne'],
        ['der Snîder', 'diu Snîderinne'], ['der Lange', 'diu Lange'], ['der Junge', 'diu Junge'],
        ['der Rôte', 'diu Rôte'], ['der Küene', 'diu Küene'], ['der Wîse', 'diu Wîse'],
    ];

    /** @var list<string> */
    protected const array TITLE_MALE = ['her', 'meister', 'bruoder', 'grâve', 'herzoge', 'künec'];

    /** @var list<string> */
    protected const array TITLE_FEMALE = ['vrouwe', 'juncvrouwe', 'swester', 'eppetissinne', 'küneginne'];

    /** @var list<string> */
    protected const array SUFFIX = ['der alte', 'der junge'];

    /**
     * Byname by place (both genders) or by trade or looks in the form of the gender, e.g. "von Ouwe", "der Smit" /
     * "diu Smidinne" - random gender when null
     */
    public function lastName(?Gender $gender = null): string
    {
        $gender = $this->resolveGender($gender);

        if ($this->randomizer->getBool()) {
            return $this->randomizer->randomElement(static::LAST_NAME);
        }

        $byname = $this->randomizer->randomElement(static::BYNAMES);

        return $gender === Gender::Male ? $byname[0] : $byname[1];
    }
}
