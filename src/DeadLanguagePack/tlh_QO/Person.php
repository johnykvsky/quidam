<?php

declare(strict_types = 1);

namespace Quidam\DeadLanguagePack\tlh_QO;

use Quidam\Core\Person as CorePerson;
use Quidam\Definitions\Enum\Gender;

/**
 * Klingon names - a warrior is known by the father: "Kargh, son of Torvek", "Vekra, daughter of Duvok". Names are
 * made up for the pack, in the sound of the language - hard consonants, apostrophes (glottal stops).
 */
class Person extends CorePerson
{
    /** @var list<string> */
    protected const array FIRST_NAME_MALE = [
        'Akhor', 'Brakk', "D'Varak", 'Dorgath', 'Duvok', 'Ghorek', 'Gorlath', 'Grokh', 'Hurqan', 'Jorvak', "K'Tarq",
        'Kargh', 'Kethor', 'Korvath', 'Kruvok', 'Lorgath', "M'Rokh", 'Makh', 'Morqath', 'Nagor', 'Qaxor', 'Qorvak',
        'Rakhar', "T'Gorr", 'Tarqon', 'Torvek', 'Varok', 'Vorgh',
    ];

    /** @var list<string> */
    protected const array FIRST_NAME_FEMALE = [
        "B'Rela", "D'Kassa", 'Ghessa', 'Ghorra', 'Huqra', "K'Vira", 'Kelvra', 'Kethra', 'Kortha', "L'Tess", "M'Ressa",
        'Marqa', "N'Garra", 'Qavra', 'Qorra', 'Sertha', 'Sorgha', "T'Rella", 'Vekra', 'Vorna', 'Zhorra',
    ];

    /** @var list<string> */
    protected const array FIRST_NAME_NEUTRAL = [];

    /** @var list<string> */
    protected const array TITLE_MALE = ['General', 'Captain', 'Commander', 'Lieutenant', 'Councillor', 'Dahar Master'];

    /** @var list<string> */
    protected const array TITLE_FEMALE = ['General', 'Captain', 'Commander', 'Lieutenant', 'Councillor', 'Lady'];

    /** @var list<string> */
    protected const array SUFFIX = ['the Elder', 'the Younger', 'the Bold', 'the Fierce'];

    protected const string NAME_FORMAT = '{firstName}, {lastName}';

    /**
     * "son of Torvek" / "daughter of Torvek" - random gender when null
     */
    public function lastName(?Gender $gender = null): string
    {
        return match ($this->resolveGender($gender)) {
            Gender::Male => 'son of ',
            Gender::Female => 'daughter of ',
        } . $this->firstName(Gender::Male);
    }
}
