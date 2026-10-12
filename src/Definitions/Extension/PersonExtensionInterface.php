<?php

declare(strict_types = 1);

namespace Quidam\Definitions\Extension;

use Quidam\Definitions\Enum\Gender;

interface PersonExtensionInterface extends ExtensionInterface
{
    /**
     * First and last name of the same gender (matters for languages with gendered surnames, i.e. Polish)
     */
    public function name(?Gender $gender = null): string;

    /**
     * Full name of given parts in this language's order (NAME_FORMAT), e.g. fullName('Anna', 'Kowalska') -> 'Anna Kowalska'
     */
    public function fullName(string $firstName, string $lastName): string;

    public function firstName(?Gender $gender = null): string;

    /**
     * Middle name - a first name of the same gender
     */
    public function middleName(?Gender $gender = null): string;

    /**
     * Age in years, e.g. 34 - for an age matching a birth date use profile()->person()
     */
    public function age(int $min = 18, int $max = 80): int;

    /**
     * First letters of name parts, uppercase, e.g. "Diana Norris" → "DN", "Anna-Maria Kowalska" → "AMK" -
     * of given name, or of a random one when null
     */
    public function initials(?string $name = null, string $separator = ''): string;

    public function suffix(): string;

    /**
     * Gender is ignored by default data - English surnames don't depend on it. Language packs with gendered surnames
     * (Kowalski / Kowalska) override this method and use it.
     */
    public function lastName(?Gender $gender = null): string;

    public function title(?Gender $gender = null): string;

    public function gender(): Gender;
}
