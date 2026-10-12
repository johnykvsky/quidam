<?php

declare(strict_types = 1);

namespace Quidam\Test\LanguagePack\pl_PL;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Quidam\Clock\FrozenClock;
use Quidam\Definitions\Enum\Gender;
use Quidam\LanguagePack\pl_PL\Address;
use Quidam\LanguagePack\pl_PL\Commerce;
use Quidam\LanguagePack\pl_PL\Company;
use Quidam\LanguagePack\pl_PL\DateTime;
use Quidam\LanguagePack\pl_PL\Internet;
use Quidam\LanguagePack\pl_PL\NationalId;
use Quidam\LanguagePack\pl_PL\Payment;
use Quidam\LanguagePack\pl_PL\Person;
use Quidam\LanguagePack\pl_PL\PhoneNumber;
use Quidam\LanguagePack\pl_PL\PlPlGenerator;
use Quidam\LanguagePack\pl_PL\Profile;
use Quidam\LanguagePack\pl_PL\Text;
use Quidam\LanguagePack\pl_PL\Vehicle;
use Quidam\Testing\SeedSnapshot;
use ReflectionClass;
use ReflectionMethod;

/**
 * Seed stability of the pack - the same seed, clock and calls give the same Polish data. Every public method of the
 * pack classes (with inherited ones, which use the pack's data) is covered; methods with required arguments are listed
 * with arguments.
 *
 * After an intended change regenerate the snapshot: UPDATE_SEED_SNAPSHOT=1 vendor-bin/phpunit --filter 'pl_PL'
 */
final class SeededOutputTest extends TestCase
{
    private const string SNAPSHOT = __DIR__ . '/snapshot.json';

    /** @var list<class-string> */
    private const array CLASSES = [
        Person::class, Address::class, PhoneNumber::class, DateTime::class, Internet::class, Company::class,
        Commerce::class, Payment::class, Vehicle::class, Profile::class, NationalId::class, Text::class,
    ];

    /** Methods which don't depend on randomness and need arguments */
    private const array NOT_RANDOM = [
        Person::class . '::fullName',
        Payment::class . '::currencyDecimals',
        Payment::class . '::formatPrice',
        Text::class . '::withCorpus',
    ];

    public function testSeededOutputMatchesSnapshot(): void
    {
        $output = self::snapshot()->generate(self::calls());

        if (getenv('UPDATE_SEED_SNAPSHOT') === '1') {
            self::snapshot()->save($output);
            self::markTestSkipped('Snapshot updated');
        }

        self::assertGreaterThan(400, count($output));
        self::assertSame([], self::snapshot()->changed($output), 'Seeded output of pl_PL changed. If intended, regenerate the snapshot');
    }

    public function testEveryPublicMethodIsInSnapshot(): void
    {
        self::assertSame([], SeedSnapshot::uncovered(self::CLASSES, self::calls(), self::NOT_RANDOM));
    }

    private static function snapshot(): SeedSnapshot
    {
        $clock = new FrozenClock(new DateTimeImmutable('2026-01-15 10:30:00.123456 UTC'));

        return new SeedSnapshot(self::SNAPSHOT, static fn (int $seed): PlPlGenerator => PlPlGenerator::create($seed, $clock));
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
            [Address::class, 'cityData', ['PL-14']],
            [DateTime::class, 'dateTimesBetween', [3, '-1 year']],
            [PhoneNumber::class, 'e164PhoneNumber', ['DE']],
            [Payment::class, 'iban', ['DE']],
            [Payment::class, 'sortCode', ['102']],
            [Payment::class, 'bankAccountNumber', [12]],
            [Vehicle::class, 'licensePlate', ['mazowieckie', true]],
            [NationalId::class, 'pesel', [new DateTimeImmutable('2004-02-29'), Gender::Female]],
            [NationalId::class, 'nip', [true]],
            [Profile::class, 'citizen', [Gender::Male, 30, 40]],
        ];
    }
}
