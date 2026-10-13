<?php

declare(strict_types = 1);

namespace Quidam\DeadLanguagePack\ang_GB;

use Quidam\Core\Person as CorePerson;
use Quidam\Definitions\Enum\Gender;

/**
 * Anglo-Saxon names - built from two elements, as they were: Ælf + rēd (elf + counsel), Ēad + gifu (wealth + gift).
 * No surnames - a man is his father's son with "-ing" ("Ēadgār Æþelstāning"), a woman her father's daughter
 * ("Ælfgifu Ēadgāres dohtor").
 */
class Person extends CorePerson
{
    /**
     * First elements, for both genders: Ælf (elf), Æþel (noble), Ēad (wealth), Wulf (wolf), Cyne (royal)...
     *
     * @var list<string>
     */
    protected const array FIRST_ELEMENT = [
        'Ælf', 'Æþel', 'Beorht', 'Cyne', 'Cūþ', 'Ēad', 'Ecg', 'God', 'Here', 'Lēof', 'Ōs', 'Sige', 'Wīg', 'Wulf',
    ];

    /**
     * Second elements of men's names: rēd (counsel), stān (stone), weard (guard), gār (spear), mund (protection)...
     *
     * @var list<string>
     */
    protected const array SECOND_ELEMENT_MALE = [
        'beorht', 'gār', 'helm', 'here', 'mund', 'rēd', 'rīc', 'stān', 'weald', 'weard', 'wine', 'wulf',
    ];

    /**
     * Second elements of women's names: flǣd (beauty), gifu (gift), þrȳþ (strength), burh (fortress)...
     *
     * @var list<string>
     */
    protected const array SECOND_ELEMENT_FEMALE = ['burh', 'flǣd', 'gifu', 'hild', 'rūn', 'swīþ', 'þrȳþ', 'wynn'];

    /** @var list<string> */
    protected const array FIRST_NAME_NEUTRAL = [];

    /** @var list<string> */
    protected const array TITLE_MALE = ['cyning', 'ealdormann', 'þegn', 'gerēfa', 'bisceop', 'abbod', 'scop'];

    /** @var list<string> */
    protected const array TITLE_FEMALE = ['cwēn', 'hlǣfdige', 'abbudisse'];

    /** @var list<string> */
    protected const array SUFFIX = ['se ealda', 'se geonga'];

    /**
     * Two elements of the gender's name stock (random gender when null), never the same twice: "Ælfrēd", "Wulfstān",
     * "Æþelflǣd", "Godgifu" (Godiva)
     */
    public function firstName(?Gender $gender = null): string
    {
        $second = $this->randomizer->randomElement(match ($this->resolveGender($gender)) {
            Gender::Male => static::SECOND_ELEMENT_MALE,
            Gender::Female => static::SECOND_ELEMENT_FEMALE,
        });

        do {
            $first = $this->randomizer->randomElement(static::FIRST_ELEMENT);
        } while (mb_strtolower($first) === $second);

        return $first . $second;
    }

    /**
     * Father's name with "-ing" for men ("Æþelwulfing" - son of Æþelwulf), "{father}es dohtor" for women
     * ("Ælfrēdes dohtor") - random gender when null
     */
    public function lastName(?Gender $gender = null): string
    {
        $father = $this->firstName(Gender::Male);

        return match ($this->resolveGender($gender)) {
            Gender::Male => (str_ends_with($father, 'e') ? mb_substr($father, 0, -1) : $father) . 'ing',
            Gender::Female => $this->genitive($father) . ' dohtor',
        };
    }

    /**
     * Genitive of a man's name - "-es", "-s" after "e": "Ælfrēdes", "Godwines"
     */
    public function genitive(string $name): string
    {
        return $name . (str_ends_with($name, 'e') ? 's' : 'es');
    }
}
