<?php

declare(strict_types = 1);

namespace Quidam\Test\Generator;

use DateTimeImmutable;
use Quidam\Core\Uuid;
use Quidam\Definitions\Extension\UuidExtensionInterface;
use Quidam\Exception\InvalidArgumentException;
use Quidam\Quidam;
use Quidam\Test\Fixtures\FrozenClock;
use Quidam\Test\Fixtures\MovableClock;

final class UuidTest extends GeneratorTestCase
{
    public function testFormats(): void
    {
        $uuid = $this->dummy->ext(UuidExtensionInterface::class);

        $this->assertAlwaysMatches('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $uuid->uuid4(...));
        $this->assertAlwaysMatches('/^[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', static fn (): string => $uuid->uuid7());
        $this->assertAlwaysMatches('/^[0-9A-HJKMNP-TV-Z]{26}$/', static fn (): string => $uuid->ulid());
    }

    public function testTimeBasedUseClock(): void
    {
        $now = new DateTimeImmutable('2026-01-01 00:00:00.123');
        $uuid = Quidam::create(clock: new FrozenClock($now))->ext(UuidExtensionInterface::class);

        $msecHex = sprintf('%012x', (int) $now->format('Uv'));
        self::assertStringStartsWith(substr($msecHex, 0, 8) . '-' . substr($msecHex, 8, 4), $uuid->uuid7());
        self::assertSame(substr($uuid->ulid(), 0, 10), substr($uuid->ulid($now), 0, 10));
    }

    public function testUuid7IsMonotonicWithFrozenClock(): void
    {
        $uuid = Quidam::create(clock: new FrozenClock(new DateTimeImmutable('2026-01-01')))->ext(UuidExtensionInterface::class);

        // 10k ids in the same millisecond overflow the 12-bit counter several times
        $ids = [];
        for ($i = 0; $i < 10000; ++$i) {
            $ids[] = $uuid->uuid7();
        }

        $sorted = $ids;
        sort($sorted);
        self::assertSame($sorted, $ids);
        self::assertCount(10000, array_unique($ids));
        $this->assertAlwaysMatches('/^[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', static fn (): string => $uuid->uuid7());
    }

    public function testUlidIsMonotonicWithFrozenClock(): void
    {
        $uuid = Quidam::create(clock: new FrozenClock(new DateTimeImmutable('2026-01-01')))->ext(UuidExtensionInterface::class);

        $ids = [];
        for ($i = 0; $i < 10000; ++$i) {
            $ids[] = $uuid->ulid();
        }

        $sorted = $ids;
        sort($sorted);
        self::assertSame($sorted, $ids);
        self::assertCount(10000, array_unique($ids));
        self::assertCount(1, array_unique(array_map(static fn (string $id): string => substr($id, 0, 10), $ids)), 'one millisecond');
    }

    public function testUlidOverflowMovesToNextMillisecond(): void
    {
        $now = new DateTimeImmutable('2026-01-01 00:00:00.500');
        $uuid = Quidam::create(clock: new FrozenClock($now))->ext(UuidExtensionInterface::class);
        $first = $uuid->ulid();

        // the last possible randomness of this millisecond
        (function (): void {
            $this->lastUlidRandom = array_fill(0, 16, 31); // @phpstan-ignore property.notFound
        })->call($uuid);

        $next = $uuid->ulid();
        self::assertGreaterThan($first, $next);
        self::assertSame(substr($uuid->ulid($now->modify('+1 millisecond')), 0, 10), substr($next, 0, 10));
    }

    public function testUlidStaysMonotonicWhenClockGoesBack(): void
    {
        $clock = new MovableClock(new DateTimeImmutable('2026-01-01 12:00:00'));
        $uuid = Quidam::create(clock: $clock)->ext(UuidExtensionInterface::class);

        $first = $uuid->ulid();
        $clock->now = $clock->now->modify('-1 second');

        self::assertGreaterThan($first, $uuid->ulid());
    }

    public function testUuid7BeforeEpochThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->dummy->ext(UuidExtensionInterface::class)->uuid7(new DateTimeImmutable('1960-01-01'));
    }

    public function testUlidBeforeEpochThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->dummy->ext(UuidExtensionInterface::class)->ulid(new DateTimeImmutable('1969-12-31 23:59:59'));
    }

    public function testEpochIsSupported(): void
    {
        self::assertStringStartsWith('00000000-0000-7', $this->dummy->ext(UuidExtensionInterface::class)->uuid7(new DateTimeImmutable('@0')));
        self::assertStringStartsWith('0000000000', $this->dummy->ext(UuidExtensionInterface::class)->ulid(new DateTimeImmutable('@0')));
    }

    public function testNameBasedUuids(): void
    {
        $uuid = $this->dummy->ext(UuidExtensionInterface::class);

        // RFC / Python uuid module reference values
        self::assertSame('886313e1-3b8a-5372-9b90-0c9aee199e5d', $uuid->uuid5(Uuid::NAMESPACE_DNS, 'python.org'));
        self::assertSame('6fa459ea-ee8a-3ca4-894e-db77e160355e', $uuid->uuid3(Uuid::NAMESPACE_DNS, 'python.org'));
        self::assertSame($uuid->uuid5(Uuid::NAMESPACE_URL, 'a'), Quidam::create()->ext(UuidExtensionInterface::class)->uuid5(Uuid::NAMESPACE_URL, 'a'));
        self::assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-5[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $uuid->uuid5(Uuid::NAMESPACE_OID, 'x'));

        $this->expectException(InvalidArgumentException::class);
        $uuid->uuid5('not-a-uuid', 'x');
    }

    public function testNanoid(): void
    {
        $uuid = $this->dummy->ext(UuidExtensionInterface::class);

        $this->assertAlwaysMatches('/^[A-Za-z0-9_-]{21}$/', static fn (): string => $uuid->nanoid());
        $this->assertAlwaysMatches('/^[0-9a-f]{10}$/', static fn (): string => $uuid->nanoid(10, '0123456789abcdef'));

        $this->expectException(InvalidArgumentException::class);
        $uuid->nanoid(0);
    }

    public function testSnowflake(): void
    {
        $now = new DateTimeImmutable('2026-10-09 12:00:00.123 UTC');
        $uuid = Quidam::create(clock: new FrozenClock($now))->ext(UuidExtensionInterface::class);
        $id = $uuid->snowflake();

        self::assertGreaterThan(0, $id);
        self::assertSame((int) $now->format('Uv'), ($id >> 22) + Uuid::SNOWFLAKE_EPOCH);
        self::assertLessThan($uuid->snowflake(new DateTimeImmutable('2026-10-09 12:00:01 UTC')), $id, 'sortable by time');
        self::assertSame(
            (int) $now->format('Uv') - 1420070400000,
            $uuid->snowflake(epoch: 1420070400000) >> 22,
        );

        $this->expectException(InvalidArgumentException::class);
        $uuid->snowflake(new DateTimeImmutable('2001-01-01'));
    }

    public function testNilAndMaxConstants(): void
    {
        self::assertSame('00000000-0000-0000-0000-000000000000', Uuid::NIL);
        self::assertSame('ffffffff-ffff-ffff-ffff-ffffffffffff', Uuid::MAX);
    }
}
