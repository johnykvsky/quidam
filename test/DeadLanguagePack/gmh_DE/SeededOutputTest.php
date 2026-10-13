<?php

declare(strict_types = 1);

namespace Quidam\Test\DeadLanguagePack\gmh_DE;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Quidam\Clock\FrozenClock;
use Quidam\DeadLanguagePack\gmh_DE\Address;
use Quidam\DeadLanguagePack\gmh_DE\Company;
use Quidam\DeadLanguagePack\gmh_DE\DateTime;
use Quidam\DeadLanguagePack\gmh_DE\GmhDeProviderPack;
use Quidam\DeadLanguagePack\gmh_DE\Person;
use Quidam\Quidam;
use Quidam\Testing\SeedSnapshot;
use ReflectionClass;
use ReflectionMethod;

/**
 * Seed stability of the pack - the same seed, clock and calls give the same Middle High German data. Every public method of the
 * pack classes (with inherited ones, which use the pack's data) is covered; methods with required arguments are listed
 * with arguments.
 *
 * After an intended change regenerate the snapshot: UPDATE_SEED_SNAPSHOT=1 vendor-bin/phpunit --filter 'gmh_DE'
 */
final class SeededOutputTest extends TestCase
{
    private const string SNAPSHOT = __DIR__ . '/snapshot.json';

    /** @var list<class-string> */
    private const array CLASSES = [Person::class, Address::class, DateTime::class, Company::class];

    /** Methods which don't depend on randomness and need arguments */
    private const array NOT_RANDOM = [
        Person::class . '::fullName',
    ];

    public function testSeededOutputMatchesSnapshot(): void
    {
        $output = self::snapshot()->generate(self::calls());

        if (getenv('UPDATE_SEED_SNAPSHOT') === '1') {
            self::snapshot()->save($output);
            self::markTestSkipped('Snapshot updated');
        }

        self::assertGreaterThan(100, count($output));
        self::assertSame([], self::snapshot()->changed($output), 'Seeded output of gmh_DE changed. If intended, regenerate the snapshot');
    }

    public function testEveryPublicMethodIsInSnapshot(): void
    {
        self::assertSame([], SeedSnapshot::uncovered(self::CLASSES, self::calls(), self::NOT_RANDOM));
    }

    private static function snapshot(): SeedSnapshot
    {
        $clock = new FrozenClock(new DateTimeImmutable('2026-01-15 10:30:00.123456 UTC'));

        return new SeedSnapshot(self::SNAPSHOT, static fn (int $seed): Quidam => Quidam::create($seed, $clock)->withProvider(new GmhDeProviderPack()));
    }

    /**
     * Every public method without required arguments, plus variants with arguments
     *
     * @return list<array{class-string, string, array<int|string, mixed>}>
     */
    private static function calls(): array
    {
        $calls = [];

        foreach (self::CLASSES as $class) {
            foreach (new ReflectionClass($class)->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                if (!$method->isConstructor() && !$method->isStatic() && $method->getNumberOfRequiredParameters() === 0) {
                    $calls[] = [$class, $method->getName(), []];
                }
            }
        }

        return [
            ...$calls,
            [Address::class, 'cityData', ['OS']],
            [DateTime::class, 'dateTimesBetween', [3, '-1 year']],
        ];
    }
}
