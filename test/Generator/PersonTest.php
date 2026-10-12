<?php

declare(strict_types = 1);

namespace Quidam\Test\Generator;

use Quidam\Definitions\Enum\Gender;
use Quidam\Definitions\Extension\PersonExtensionInterface;
use Quidam\Definitions\Extension\ProfileExtensionInterface;
use Quidam\Exception\InvalidArgumentException;
use Quidam\Quidam;
use Quidam\Test\Fixtures\FamilyNameFirstPerson;
use Quidam\Test\Fixtures\GenderedPerson;
use Quidam\Test\Fixtures\GenderedSurnamePerson;

final class PersonTest extends GeneratorTestCase
{
    public function testNames(): void
    {
        $person = $this->dummy->ext(PersonExtensionInterface::class);

        $this->assertAlwaysMatches('/^[A-Z][A-Za-z]+ [A-Z][A-Za-z]+$/', $person->name(...));
        $this->assertAlwaysMatches('/^[A-Z][a-z]+$/', $person->firstName(...));
        $this->assertAlwaysMatches('/^[A-Z][A-Za-z]+$/', $person->lastName(...));
    }

    public function testNamesAreVaried(): void
    {
        $names = [];
        for ($i = 0; $i < 1000; ++$i) {
            $names[$this->dummy->ext(PersonExtensionInterface::class)->name()] = true;
        }

        self::assertGreaterThan(900, count($names));
    }

    public function testGender(): void
    {
        $person = $this->dummy->ext(PersonExtensionInterface::class);

        $this->assertAlwaysMatches('/^(male|female)$/', static fn (): string => $person->gender()->value);
        $female = [];
        for ($i = 0; $i < 500; ++$i) {
            $female[$person->firstName(Gender::Female)] = true;
        }

        $unisex = array_flip(self::constant('FIRST_NAME_NEUTRAL'));
        for ($i = 0; $i < self::REPEAT; ++$i) {
            $male = $person->firstName(Gender::Male);
            if (!isset($unisex[$male])) {
                self::assertArrayNotHasKey($male, $female);
            }
        }

        $this->assertAlwaysMatches('/^(Mrs\.|Ms\.|Miss|Dr\.|Prof\.)$/', static fn (): string => $person->title(Gender::Female));
        $this->assertAlwaysMatches('/^(Mr\.|Dr\.|Prof\.)$/', static fn (): string => $person->title(Gender::Male));
    }

    public function testUnisexNamesAreGivenToBothGenders(): void
    {
        $person = $this->dummy->person();
        $unisex = self::constant('FIRST_NAME_NEUTRAL');
        $male = [];
        $female = [];

        for ($i = 0; $i < 2000; ++$i) {
            $male[$person->firstName(Gender::Male)] = true;
            $female[$person->firstName(Gender::Female)] = true;
        }

        self::assertNotSame([], array_intersect($unisex, array_keys($male)), 'unisex names for men');
        self::assertNotSame([], array_intersect($unisex, array_keys($female)), 'unisex names for women');
        self::assertSame(array_values(array_intersect(array_keys($male), array_keys($female))), array_values(array_intersect(array_keys($male), array_keys($female), $unisex)), 'only unisex names are shared');
    }

    /** @return list<string> */
    private static function constant(string $name): array
    {
        $value = new \ReflectionClassConstant(\Quidam\Core\Person::class, $name)->getValue();
        self::assertIsArray($value);

        return array_values(array_filter($value, is_string(...)));
    }

    public function testMiddleNameAndSuffix(): void
    {
        $person = $this->dummy->withDefinition(PersonExtensionInterface::class, GenderedPerson::class)->ext(PersonExtensionInterface::class);

        self::assertSame('John', $person->middleName(Gender::Male));
        self::assertSame('Jane', $person->middleName(Gender::Female));
        $this->assertAlwaysMatches('/^(Jr\.|Sr\.|II|III|IV|PhD|MD|DDS)$/', $this->dummy->ext(PersonExtensionInterface::class)->suffix(...));
    }

    public function testAgeAndInitials(): void
    {
        $person = $this->dummy->ext(PersonExtensionInterface::class);

        $this->assertAlwaysMatches('/^(1[89]|[2-7]\d|80)$/', static fn (): int => $person->age());
        $this->assertAlwaysMatches('/^[A-Z]{2}$/', static fn (): string => $person->initials());
        self::assertSame(5, $person->age(5, 5));
        self::assertSame('DN', $person->initials('Diana Norris'));
        self::assertSame('A.M.Ż', $person->initials('Anna-Maria  żółkiewska', '.'));
        self::assertSame('Ł', $person->initials('łucja'));

        $this->expectException(InvalidArgumentException::class);
        $person->age(30, 20);
    }

    public function testGenderedSurnames(): void
    {
        $dummy = Quidam::create()->withDefinition(PersonExtensionInterface::class, GenderedSurnamePerson::class);

        for ($i = 0; $i < 20; ++$i) {
            self::assertStringEndsWith(' Kowalska', $dummy->ext(PersonExtensionInterface::class)->name(Gender::Female));
            self::assertStringEndsWith(' Kowalski', $dummy->ext(PersonExtensionInterface::class)->name(Gender::Male));

            $person = $dummy->ext(ProfileExtensionInterface::class)->person();
            self::assertSame($person['gender'] === Gender::Female ? 'Kowalska' : 'Kowalski', $person['lastName']);
        }
    }

    public function testFullName(): void
    {
        self::assertSame('Anna Kowalska', $this->dummy->ext(PersonExtensionInterface::class)->fullName('Anna', 'Kowalska'));

        $familyFirst = $this->dummy->withDefinition(PersonExtensionInterface::class, FamilyNameFirstPerson::class)->ext(PersonExtensionInterface::class);
        self::assertSame('Kovács Anna', $familyFirst->fullName('Anna', 'Kovács'));
        $this->assertAlwaysMatches('/^[A-Z][A-Za-z]+ [A-Z][a-z]+$/', $familyFirst->name(...));
    }
}
