<?php

declare(strict_types = 1);

namespace Quidam\Test\LanguagePack\la_VA;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Quidam\Clock\FrozenClock;
use Quidam\LanguagePack\la_VA\Address;
use Quidam\LanguagePack\la_VA\Company;
use Quidam\LanguagePack\la_VA\DateTime;
use Quidam\LanguagePack\la_VA\LaVaProviderPack;
use Quidam\LanguagePack\la_VA\Person;
use Quidam\Quidam;
use Quidam\Testing\SeedSnapshot;
use ReflectionClass;
use ReflectionMethod;

/**
 * Seed stability of the pack - the same seed, clock and calls give the same Latin data. Every public method of the
 * pack classes (with inherited ones, which use the pack's data) is covered; methods with required arguments are listed
 * with arguments.
 *
 * After an intended change regenerate the snapshot: UPDATE_SEED_SNAPSHOT=1 vendor-bin/phpunit --filter 'la_VA'
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
        self::assertSame([], self::snapshot()->changed($output), 'Seeded output of la_VA changed. If intended, regenerate the snapshot');
    }

    public function testEveryPublicMethodIsInSnapshot(): void
    {
        self::assertSame([], SeedSnapshot::uncovered(self::CLASSES, self::calls(), self::NOT_RANDOM));
    }

    private static function snapshot(): SeedSnapshot
    {
        $clock = new FrozenClock(new DateTimeImmutable('2026-01-15 10:30:00.123456 UTC'));

        return new SeedSnapshot(self::SNAPSHOT, static fn (int $seed): Quidam => Quidam::create($seed, $clock)->withProvider(new LaVaProviderPack()));
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
            [Address::class, 'cityData', ['BR']],
            [DateTime::class, 'dateTimesBetween', [3, '-1 year']],
        ];
    }
}
