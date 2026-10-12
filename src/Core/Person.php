<?php

declare(strict_types = 1);

namespace Quidam\Core;

use Quidam\Definitions\Enum\Gender;
use Quidam\Definitions\Extension\PersonExtensionInterface;
use Quidam\Definitions\Randomizer\RandomizerInterface;
use Quidam\Exception\InvalidArgumentException;

class Person implements PersonExtensionInterface
{
    /** @var list<string> */
    protected const array FIRST_NAME_MALE = [
        'John', 'Harry', 'Olivier', 'Garett', 'Hank', 'Paul', 'Vernon', 'Fred', 'James', 'David', 'Michael', 'Ian',
        'Vincent', 'Edmund', 'Bill', 'Kevin', 'Adam', 'Aaron', 'Alan', 'Albert', 'Andrew', 'Anthony', 'Arthur',
        'Austin', 'Benjamin', 'Bernard', 'Brandon', 'Brian', 'Bruce', 'Carl', 'Charles', 'Christian', 'Christopher',
        'Colin', 'Daniel', 'Dennis', 'Derek', 'Dominic', 'Douglas', 'Dylan', 'Edward', 'Eric', 'Ethan', 'Evan', 'Felix',
        'Frank', 'Gabriel', 'George', 'Gordon', 'Graham', 'Gregory', 'Henry', 'Howard', 'Isaac', 'Jack', 'Jacob',
        'Jason', 'Jeffrey', 'Jeremy', 'Joel', 'Jonathan', 'Joseph', 'Joshua', 'Julian', 'Keith', 'Kenneth', 'Lawrence',
        'Leo', 'Leonard', 'Liam', 'Louis', 'Lucas', 'Luke', 'Marcus', 'Mark', 'Martin', 'Matthew', 'Max', 'Nathan',
        'Neil', 'Nicholas', 'Noah', 'Oliver', 'Oscar', 'Patrick', 'Peter', 'Philip', 'Ralph', 'Raymond', 'Richard',
        'Robert', 'Roger', 'Ronald', 'Ryan', 'Samuel', 'Scott', 'Simon', 'Stephen', 'Steven', 'Thomas', 'Timothy',
        'Victor', 'Walter', 'William',
    ];

    /** @var list<string> */
    protected const array FIRST_NAME_FEMALE = [
        'Jane', 'Katy', 'Anna', 'Patricia', 'Daisy', 'Wendy', 'Heather', 'Emily', 'Julia', 'Mary', 'Victoria', 'Abby',
        'Jackie', 'Hannah', 'Susan', 'Wilma', 'Abigail', 'Alice', 'Alexandra', 'Amanda', 'Amber', 'Amelia', 'Amy',
        'Andrea', 'Angela', 'Barbara', 'Beatrice', 'Bella', 'Brenda', 'Caroline', 'Catherine', 'Charlotte', 'Chloe',
        'Christine', 'Claire', 'Clara', 'Diana', 'Donna', 'Dorothy', 'Eleanor', 'Elizabeth', 'Ella', 'Emma', 'Erin',
        'Eva', 'Evelyn', 'Fiona', 'Florence', 'Grace', 'Helen', 'Holly', 'Irene', 'Isabel', 'Jasmine', 'Jennifer',
        'Jessica', 'Joan', 'Joanna', 'Karen', 'Kate', 'Laura', 'Lauren', 'Lily', 'Linda', 'Lisa', 'Lucy', 'Madison',
        'Margaret', 'Maria', 'Martha', 'Megan', 'Melissa', 'Mia', 'Michelle', 'Molly', 'Monica', 'Nancy', 'Natalie',
        'Nicole', 'Olivia', 'Paula', 'Rachel', 'Rebecca', 'Rose', 'Ruth', 'Samantha', 'Sandra', 'Sarah', 'Sophie',
        'Stella', 'Teresa', 'Tracy', 'Valerie', 'Vanessa', 'Violet', 'Naomi', 'Hazel', 'Iris', 'Sylvia', 'Ivy',
    ];

    /**
     * Unisex names - given to both genders, together with FIRST_NAME_MALE / FIRST_NAME_FEMALE. Empty in languages
     * without them.
     *
     * @var list<string>
     */
    protected const array FIRST_NAME_NEUTRAL = [
        'Alex', 'Avery', 'Bailey', 'Blake', 'Cameron', 'Casey', 'Charlie', 'Dakota', 'Drew', 'Emerson', 'Finley',
        'Hayden', 'Jamie', 'Jesse', 'Jordan', 'Kendall', 'Logan', 'Morgan', 'Parker', 'Peyton', 'Quinn', 'Reese',
        'Riley', 'Robin', 'Rowan', 'Sam', 'Sawyer', 'Skyler', 'Taylor',
    ];

    /** @var list<string> */
    protected const array LAST_NAME = [
        'Doe', 'Smith', 'White', 'Adams', 'Carter', 'Morgan', 'Robinson', 'Thompson', 'Walker', 'Harris', 'McKenzie',
        'Anderson', 'Spencer', 'Fisher', 'Green', 'Murphy', 'Allen', 'Bailey', 'Baker', 'Barnes', 'Bell', 'Bennett',
        'Brooks', 'Brown', 'Butler', 'Campbell', 'Clark', 'Collins', 'Cook', 'Cooper', 'Cox', 'Davies', 'Davis',
        'Dixon', 'Edwards', 'Ellis', 'Evans', 'Foster', 'Fox', 'Graham', 'Gray', 'Griffin', 'Hall', 'Hamilton', 'Hart',
        'Hayes', 'Henderson', 'Hill', 'Holmes', 'Howard', 'Hughes', 'Hunt', 'Jackson', 'James', 'Jenkins', 'Johnson',
        'Jones', 'Kelly', 'Kennedy', 'King', 'Knight', 'Lee', 'Lewis', 'Lloyd', 'Marshall', 'Martin', 'Mason',
        'Matthews', 'Miller', 'Mitchell', 'Moore', 'Morris', 'Murray', 'Nelson', 'Newman', 'Owen', 'Palmer', 'Parker',
        'Patterson', 'Payne', 'Perry', 'Phillips', 'Porter', 'Powell', 'Price', 'Reed', 'Reynolds', 'Richards',
        'Richardson', 'Roberts', 'Rogers', 'Ross', 'Russell', 'Scott', 'Shaw', 'Simpson', 'Stevens', 'Stewart', 'Stone',
        'Sullivan', 'Taylor', 'Turner', 'Wallace', 'Ward', 'Watson', 'Webb', 'Wells', 'West', 'Wilson', 'Wood', 'Woods',
        'Wright', 'Young', 'Atkinson', 'Barker', 'Bradley', 'Burton', 'Chapman', 'Cole', 'Crawford', 'Dawson',
        'Douglas', 'Ferguson', 'Fletcher', 'Ford', 'Gibson', 'Gordon', 'Hudson', 'Hunter', 'Lawson', 'Lynch', 'Mills',
        'Norris', 'Oliver', 'Pearson', 'Pierce', 'Quinn', 'Riley', 'Sanders', 'Saunders', 'Sharp', 'Steele', 'Tucker',
        'Walsh', 'Warren', 'Watts', 'Whitaker', 'Willis', 'Winter', 'Bishop',
    ];

    /** @var list<string> */
    protected const array TITLE_MALE = ['Mr.', 'Dr.', 'Prof.'];

    /** @var list<string> */
    protected const array TITLE_FEMALE = ['Mrs.', 'Ms.', 'Miss', 'Dr.', 'Prof.'];

    /** @var list<string> */
    protected const array SUFFIX = ['Jr.', 'Sr.', 'II', 'III', 'IV', 'PhD'];

    /** Full name - "{firstName}" and "{lastName}" are replaced; family name first in some languages (Hungarian) */
    protected const string NAME_FORMAT = '{firstName} {lastName}';

    /** @var array<class-string, array<string, list<string>>> first names by class and gender */
    private static array $firstNames = [];

    public function __construct(
        protected readonly RandomizerInterface $randomizer,
    ) {
    }

    /**
     * First and last name of the same gender (matters for languages with gendered surnames, i.e. Polish)
     */
    public function name(?Gender $gender = null): string
    {
        $gender = $this->resolveGender($gender);

        return $this->fullName($this->firstName($gender), $this->lastName($gender));
    }

    /**
     * Full name of given parts in this language's order (NAME_FORMAT), e.g. fullName('Anna', 'Kowalska') -> 'Anna Kowalska'
     */
    public function fullName(string $firstName, string $lastName): string
    {
        return strtr(static::NAME_FORMAT, ['{firstName}' => $firstName, '{lastName}' => $lastName]);
    }

    public function firstName(?Gender $gender = null): string
    {
        return $this->randomizer->randomElement($this->firstNames($this->resolveGender($gender)));
    }

    /**
     * Middle name - a first name of the same gender
     */
    public function middleName(?Gender $gender = null): string
    {
        return $this->firstName($gender);
    }

    /**
     * e.g. "Jr.", "III", "PhD"
     */
    /**
     * Age in years, e.g. 34 - for an age matching a birth date use profile()->person()
     */
    public function age(int $min = 18, int $max = 80): int
    {
        if ($min < 0 || $min > $max) {
            throw new InvalidArgumentException('age() requires 0 <= $min <= $max');
        }

        return $this->randomizer->getInt($min, $max);
    }

    /**
     * First letters of name parts, uppercase, e.g. "Diana Norris" → "DN", "Anna-Maria Kowalska" → "AMK" -
     * of given name, or of a random one when null
     */
    public function initials(?string $name = null, string $separator = ''): string
    {
        $parts = preg_split('/[\s\-]+/u', trim($name ?? $this->name()), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return implode($separator, array_map(static fn (string $part): string => mb_strtoupper(mb_substr($part, 0, 1)), $parts));
    }

    public function suffix(): string
    {
        return $this->randomizer->randomElement(static::SUFFIX);
    }

    /**
     * Gender is ignored by default data - English surnames don't depend on it. Language packs with gendered surnames
     * (Kowalski / Kowalska) override this method and use it.
     */
    public function lastName(?Gender $gender = null): string
    {
        return $this->randomizer->randomElement(static::LAST_NAME);
    }

    public function title(?Gender $gender = null): string
    {
        return $this->randomizer->randomElement(match ($this->resolveGender($gender)) {
            Gender::Male => static::TITLE_MALE,
            Gender::Female => static::TITLE_FEMALE,
        });
    }

    public function gender(): Gender
    {
        return $this->randomizer->getBool() ? Gender::Male : Gender::Female;
    }

    /**
     * First names of given gender with the unisex ones - merged once per class (constants don't change at runtime)
     *
     * @return list<string>
     */
    protected function firstNames(Gender $gender): array
    {
        return self::$firstNames[static::class][$gender->value] ??= match ($gender) {
            Gender::Male => [...static::FIRST_NAME_MALE, ...static::FIRST_NAME_NEUTRAL],
            Gender::Female => [...static::FIRST_NAME_FEMALE, ...static::FIRST_NAME_NEUTRAL],
        };
    }

    /**
     * Given gender, or a random one when null
     */
    protected function resolveGender(?Gender $gender): Gender
    {
        return $gender ?? $this->gender();
    }
}
