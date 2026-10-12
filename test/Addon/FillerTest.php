<?php

declare(strict_types = 1);

namespace Quidam\Test\Addon;

use DateTime;
use DateTimeImmutable;
use JsonSerializable;
use PHPUnit\Framework\TestCase;
use Quidam\Definitions\Addon\FillerInterface;
use Quidam\Definitions\Enum\Gender;
use Quidam\Definitions\Extension\PersonExtensionInterface;
use Quidam\Exception\InvalidArgumentException;
use Quidam\Quidam;
use Quidam\Test\Fixtures\Filler\FillerAddress;
use Quidam\Test\Fixtures\Filler\FillerGenderEnum;
use Quidam\Test\Fixtures\Filler\FillerGenders;
use Quidam\Test\Fixtures\Filler\FillerLoop;
use Quidam\Test\Fixtures\Filler\FillerNode;
use Quidam\Test\Fixtures\Filler\FillerService;
use Quidam\Test\Fixtures\Filler\FillerUser;
use Quidam\Test\Fixtures\Filler\ShopFiller;
use Quidam\Test\Fixtures\FrozenClock;
use Quidam\Test\Fixtures\Suit;
use Quidam\Test\Fixtures\UnitSuit;

final class FillerTest extends TestCase
{
    private FillerInterface $filler;

    private DateTimeImmutable $now;

    protected function setUp(): void
    {
        $this->now = new DateTimeImmutable('2026-06-15 12:00:00 UTC');
        $this->filler = Quidam::create(clock: new FrozenClock($this->now))->ext(FillerInterface::class);
    }

    public function testFillsByNameAndType(): void
    {
        for ($i = 0; $i < 30; ++$i) {
            $user = $this->filler->make(FillerUser::class);

            self::assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-4/', $user->id);
            self::assertMatchesRegularExpression('/@example\.(com|org|net)$/', $user->email);
            self::assertGreaterThanOrEqual(18, $user->age);
            self::assertContains($user->suit, Suit::cases());
            self::assertContains($user->unitSuit, UnitSuit::cases());
            self::assertInstanceOf(FillerAddress::class, $user->address);
            self::assertMatchesRegularExpression('/^\d{4,5}$/', $user->address->zipCode);
            self::assertLessThanOrEqual($this->now, $user->createdAt);
            self::assertInstanceOf(DateTime::class, $user->updatedAt);
            self::assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $user->publishedAt, 'date rule converted to string');
            self::assertGreaterThanOrEqual(1990, $user->year);
            self::assertLessThanOrEqual(1000.0, $user->balance);
            self::assertNotSame('', $user->nickname);
            self::assertIsString($user->bio);
        }
    }

    public function testPersonFieldsOfOneObjectBelongTogether(): void
    {
        for ($i = 0; $i < 30; ++$i) {
            $user = $this->filler->make(FillerUser::class);
            $names = strtolower($user->firstName . '|' . $user->lastName);

            self::assertMatchesRegularExpression('/(' . $names . ')/', $user->email);
            self::assertMatchesRegularExpression('/(' . $names . '|^[a-z])/', $user->nickname);
        }
    }

    public function testGenderGoesIntoStringsAndEnums(): void
    {
        for ($i = 0; $i < 30; ++$i) {
            $genders = $this->filler->make(FillerGenders::class);

            self::assertContains($genders->gender, ['male', 'female']);
            self::assertSame($genders->gender, $genders->sex->value, 'own enum with the same values, one person per object');
        }

        $gender = Quidam::create()->ext(FillerInterface::class)->make(FillerGenderEnum::class)->gender;
        self::assertInstanceOf(Gender::class, $gender);
    }

    public function testDefaultsAreKeptUnlessNameRuleMatches(): void
    {
        $user = $this->filler->make(FillerUser::class);

        self::assertSame([], $user->tags);
        self::assertSame('user', $user->role);
        self::assertSame('default', $user->untouched);
        self::assertNull($user->billingAddress, 'no name rule - default kept');
        self::assertInstanceOf(DateTimeImmutable::class, $user->deletedAt, '"At" name rule wins over default');
    }

    public function testOverrides(): void
    {
        $calls = 0;
        $email = static function () use (&$calls): string {
            return 'user' . ++$calls . '@example.com';
        };
        $users = array_map(fn (): FillerUser => $this->filler->make(FillerUser::class, [
            'role' => 'admin',
            'email' => $email,
            'nickname' => 'nick',
        ]), range(1, 3));

        self::assertSame(['admin', 'admin', 'admin'], array_column($users, 'role'));
        self::assertSame(['user1@example.com', 'user2@example.com', 'user3@example.com'], array_column($users, 'email'));
        self::assertSame('nick', $users[0]->nickname);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown override "emial"');
        $this->filler->make(FillerUser::class, ['emial' => 'x']);
    }

    public function testRecursionStopsAtMaxDepth(): void
    {
        $node = $this->filler->make(FillerNode::class);
        $depth = 0;

        while ($node->parent !== null) {
            $node = $node->parent;
            ++$depth;
        }

        self::assertSame(3, $depth);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('FillerLoop::__construct() parameter $next');
        $this->filler->make(FillerLoop::class);
    }

    public function testInterfacesAreNotGuessed(): void
    {
        $clock = new FrozenClock($this->now);
        $service = $this->filler->make(FillerService::class, ['clock' => $clock]);

        self::assertNull($service->optionalClock);
        self::assertSame($clock, $service->clock);
        self::assertNull($service->counter);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('parameter $clock of type Psr\Clock\ClockInterface - pass it in $overrides');
        $this->filler->make(FillerService::class);
    }

    public function testNotInstantiable(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->filler->make(JsonSerializable::class);
    }

    public function testNullChance(): void
    {
        $always = $this->filler->withNullChance(100);
        $user = $always->make(FillerUser::class);

        self::assertNull($user->bio);
        self::assertNull($user->deletedAt);
        self::assertNotNull($this->filler->make(FillerUser::class)->bio, 'original filler unchanged');

        $this->expectException(InvalidArgumentException::class);
        $this->filler->withNullChance(1.5);
    }

    public function testWithNullChanceDoesNotConsumeRandomness(): void
    {
        $dummy = Quidam::create(seed: 5);
        $dummy->ext(FillerInterface::class)->withNullChance(30);

        self::assertSame(Quidam::create(seed: 5)->ext(PersonExtensionInterface::class)->name(), $dummy->ext(PersonExtensionInterface::class)->name());
    }

    public function testArray(): void
    {
        $payload = $this->filler->array(['id', 'first_name', 'Last-Name', 'emailAddress', 'createdAt', 'isActive', 'price', 'whatever'], ['price' => 9.99]);

        self::assertIsString($payload['id']);
        self::assertIsString($payload['first_name']);
        self::assertIsString($payload['Last-Name']);
        self::assertIsString($payload['emailAddress']);
        self::assertMatchesRegularExpression('/^[0-9a-f-]{36}$/', $payload['id']);
        self::assertMatchesRegularExpression('/(' . strtolower($payload['first_name'] . '|' . $payload['Last-Name']) . ')/', $payload['emailAddress'], 'one profile per array');
        self::assertInstanceOf(DateTimeImmutable::class, $payload['createdAt']);
        self::assertIsBool($payload['isActive']);
        self::assertSame(9.99, $payload['price']);
        self::assertIsString($payload['whatever']);

        $this->expectException(InvalidArgumentException::class);
        $this->filler->array(['id'], ['name' => 'x']);
    }

    public function testSeedGivesSameObjects(): void
    {
        $clock = new FrozenClock($this->now);

        self::assertEquals(
            Quidam::create(seed: 9, clock: $clock)->ext(FillerInterface::class)->make(FillerUser::class),
            Quidam::create(seed: 9, clock: $clock)->ext(FillerInterface::class)->make(FillerUser::class),
        );
    }

    public function testOwnRules(): void
    {
        $code = Quidam::create()->ext(ShopFiller::class)->array(['productCode'])['productCode'];

        self::assertIsString($code);
        self::assertMatchesRegularExpression('/^[A-Z0-9-]{6,10}$/', $code);
    }

    public function testAddressPartsAndPhoneComeFromOneProfile(): void
    {
        for ($i = 0; $i < 20; ++$i) {
            [$address, $city, $postcode, $region, $phone, $email, $userName] = array_values(
                $this->filler->array(['address', 'city', 'postcode', 'region', 'phone', 'email', 'username']),
            );
            self::assertIsString($address);
            self::assertIsString($city);
            self::assertIsString($postcode);
            self::assertIsString($phone);
            self::assertIsString($email);
            self::assertIsString($userName);

            self::assertStringEndsWith(', ' . $postcode . ' ' . $city, $address, 'address line of the same place');
            self::assertContains($region, ['Northern Region', 'Southern Region', 'Eastern Region', 'Western Region', 'Central Region', 'Capital District', 'Coastal Region', 'Highlands']);
            self::assertMatchesRegularExpression('/^0[1-9][\d ]{9,11}$/', $phone);
            self::assertStringStartsWith($userName . '@', $email);
        }
    }
}
