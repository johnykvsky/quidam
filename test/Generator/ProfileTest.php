<?php

declare(strict_types = 1);

namespace Quidam\Test\Generator;

use DateTimeImmutable;
use DateTimeZone;
use Quidam\Definitions\Enum\Gender;
use Quidam\Definitions\Extension\PersonExtensionInterface;
use Quidam\Definitions\Extension\ProfileExtensionInterface;
use Quidam\Quidam;
use Quidam\Test\Fixtures\FamilyNameFirstPerson;
use Quidam\Test\Fixtures\FrozenClock;
use Quidam\Test\Fixtures\GenderedPerson;
use Quidam\Test\Fixtures\PlProviderPack;

final class ProfileTest extends GeneratorTestCase
{
    public function testPersonValuesBelongTogether(): void
    {
        $now = new DateTimeImmutable('2026-06-15 12:00:00', new DateTimeZone('Europe/Warsaw'));
        $profile = Quidam::create(clock: new FrozenClock($now))->ext(ProfileExtensionInterface::class);

        for ($i = 0; $i < self::REPEAT; ++$i) {
            $person = $profile->person(minAge: 20, maxAge: 30);
            $first = strtolower($person['firstName']);
            $last = strtolower($person['lastName']);

            self::assertSame($person['firstName'] . ' ' . $person['lastName'], $person['name']);
            self::assertMatchesRegularExpression('/' . $first . '|' . $last . '/', $person['userName']);
            self::assertMatchesRegularExpression('/^' . preg_quote($person['userName'], '/') . '@example\.(com|org|net)$/', $person['email'], 'email is the user name');
            self::assertMatchesRegularExpression('/^0[1-9][\d ]{9,11}$/', $person['phone']);
            self::assertSame($person['address']['street'] . ', ' . $person['address']['postcode'] . ' ' . $person['address']['city'], $person['address']['address']);
            self::assertSame($person['dateOfBirth']->diff($now)->y, $person['age']);
            self::assertGreaterThanOrEqual(20, $person['age']);
            self::assertLessThanOrEqual(30, $person['age']);
        }
    }

    public function testNameAndTitleMatchGender(): void
    {
        $profile = $this->dummy->withDefinition(PersonExtensionInterface::class, GenderedPerson::class)->ext(ProfileExtensionInterface::class);

        $male = $profile->person(Gender::Male);
        self::assertSame(Gender::Male, $male['gender']);
        self::assertSame('John', $male['firstName']);
        self::assertSame('Mr.', $male['title']);

        $female = $profile->person(Gender::Female);
        self::assertSame(Gender::Female, $female['gender']);
        self::assertSame('Jane', $female['firstName']);
        self::assertSame('Mrs.', $female['title']);
    }

    public function testSeedIsRepeatable(): void
    {
        $clock = new FrozenClock(new DateTimeImmutable('2026-01-01'));

        self::assertEquals(
            Quidam::create(seed: 3, clock: $clock)->ext(ProfileExtensionInterface::class)->person(),
            Quidam::create(seed: 3, clock: $clock)->ext(ProfileExtensionInterface::class)->person(),
        );
    }

    public function testNameFollowsLanguageOrder(): void
    {
        $person = $this->dummy->withDefinition(PersonExtensionInterface::class, FamilyNameFirstPerson::class)
            ->ext(ProfileExtensionInterface::class)->person();

        self::assertSame($person['lastName'] . ' ' . $person['firstName'], $person['name']);
    }

    public function testPhoneAndAddressFollowLocale(): void
    {
        $person = $this->dummy->withProvider(new PlProviderPack())->ext(ProfileExtensionInterface::class)->person();

        self::assertMatchesRegularExpression('/^[5-8]\d{2} \d{3} \d{3}$/', $person['phone']);
        self::assertMatchesRegularExpression('/^[A-ZŁŚŻŹ][\p{L}]+ [\p{L}]+$/u', $person['name']);
        self::assertSame(
            ['street', 'city', 'state', 'stateCode', 'county', 'postcode', 'latitude', 'longitude', 'address'],
            array_keys($person['address']),
        );
    }
}
