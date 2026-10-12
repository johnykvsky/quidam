<?php

declare(strict_types = 1);

namespace Quidam\Test;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;
use Quidam\Core\Person;
use Quidam\Core\Randomizer\SecureRandomizer;
use Quidam\Core\Randomizer\Seed;
use Quidam\Core\Randomizer\XoshiroRandomizer;
use Quidam\Definitions\Extension\CompanyExtensionInterface;
use Quidam\Definitions\Extension\InternetExtensionInterface;
use Quidam\Definitions\Extension\NumberExtensionInterface;
use Quidam\Definitions\Extension\PersonExtensionInterface;
use Quidam\Definitions\Randomizer\RandomizerInterface;
use Quidam\Definitions\Randomizer\SecureRandomizerInterface;
use Quidam\Exception\ContainerException;
use Quidam\Exception\DefinitionNotFoundException;
use Quidam\Exception\InvalidArgumentException;
use Quidam\Exception\OverflowException;
use Quidam\ExtensionAccessorsTrait;
use Quidam\Quidam;
use Quidam\Strategy\UniqueMemory;
use Quidam\Test\Fixtures\CarExtension;
use Quidam\Test\Fixtures\FakeSecureRandomizer;
use Quidam\Test\Fixtures\FixedDefinitionPack;
use Quidam\Test\Fixtures\FixedPerson;
use Quidam\Test\Fixtures\FixedProviderPack;
use Quidam\Test\Fixtures\FrozenClock;
use Quidam\Test\Fixtures\RecordingContainer;
use Quidam\Test\Fixtures\RecordingContainerFactory;
use Quidam\Test\Fixtures\UnresolvableExtension;

final class QuidamTest extends TestCase
{
    public function testExtensionsAreSharedWithinInstance(): void
    {
        $generator = Quidam::create();

        self::assertInstanceOf(Person::class, $generator->ext(PersonExtensionInterface::class));
        self::assertSame($generator->ext(PersonExtensionInterface::class), $generator->ext(PersonExtensionInterface::class));
        self::assertSame($generator->random(), $generator->ext(RandomizerInterface::class));
        self::assertInstanceOf(XoshiroRandomizer::class, $generator->random());
    }

    public function testAccessorsReturnExtensionsFromContainer(): void
    {
        $generator = Quidam::create();
        $accessors = array_column(new \ReflectionClass(ExtensionAccessorsTrait::class)->getMethods(\ReflectionMethod::IS_PUBLIC), 'name');
        self::assertCount(25, $accessors); // 24 extensions + abstract ext()

        foreach (new \ReflectionClass(Quidam::class)->getMethods() as $method) {
            if ($method->getName() === 'ext' || !in_array($method->getName(), $accessors, true)) {
                continue;
            }

            $type = $method->getReturnType();
            self::assertInstanceOf(\ReflectionNamedType::class, $type);
            /** @var class-string $id */
            $id = $type->getName();
            self::assertSame($generator->ext($id), $method->invoke($generator), $method->getName());
        }
    }

    public function testAccessorsFollowReplacedDefinitions(): void
    {
        $generator = Quidam::create()->withDefinition(PersonExtensionInterface::class, FixedPerson::class);

        self::assertInstanceOf(FixedPerson::class, $generator->person());
        self::assertInstanceOf(FixedPerson::class, $generator->fork('a')->person());
    }

    public function testOnlyOwnExceptionsAreThrown(): void
    {
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(dirname(__DIR__) . '/src', \FilesystemIterator::SKIP_DOTS));

        foreach ($files as $file) {
            self::assertInstanceOf(\SplFileInfo::class, $file);
            if ($file->getExtension() !== 'php' || str_contains($file->getPathname(), '/Tests/')) {
                continue;
            }

            // QuidamExceptionInterface lets callers catch everything the library throws
            self::assertDoesNotMatchRegularExpression('/throw new \\\\/', (string) file_get_contents($file->getPathname()), $file->getPathname());
        }
    }

    public function testGeneratorsShareNoInstances(): void
    {
        // default definitions are built once per process - every generator still gets its own instances
        $first = Quidam::create(seed: 7);
        $second = Quidam::create(seed: 7);
        $unseeded = Quidam::create();

        self::assertNotSame($first->random(), $second->random());
        self::assertNotSame($first->person(), $second->person());
        self::assertNotSame($first->ext(UniqueMemory::class), $second->ext(UniqueMemory::class));
        self::assertSame($first->person()->name(), $second->person()->name(), 'same seed, same values');
        self::assertSame(7, $first->ext(Seed::class)->value);
        self::assertNull($unseeded->ext(Seed::class)->value, 'seed of one generator does not leak into the next');

        $first->unique('k', static fn (): int => 1);
        self::assertSame(1, $second->unique('k', static fn (): int => 1), 'unique memory is per generator');
    }

    public function testCustomExtensionWorksWithoutRegistration(): void
    {
        self::assertStringStartsWith('Car ', Quidam::create()->ext(CarExtension::class)->brand());
    }

    public function testWithDefinitionFactoryGetsDependenciesByType(): void
    {
        $generator = Quidam::create()->withDefinition(
            CarExtension::class,
            static fn (RandomizerInterface $randomizer, PersonExtensionInterface $person): CarExtension => new CarExtension($randomizer, $person, 'Auto'),
        );

        self::assertStringStartsWith('Auto ', $generator->ext(CarExtension::class)->brand());
    }

    public function testAliasSharesInstanceWithClass(): void
    {
        $generator = Quidam::create();

        self::assertSame($generator->ext(PersonExtensionInterface::class), $generator->ext(Person::class));
    }

    public function testOwnContainerFactory(): void
    {
        $factory = new RecordingContainerFactory();
        $generator = Quidam::create(seed: 1, containerFactory: $factory);

        self::assertSame(1, $factory->created);
        self::assertInstanceOf(RecordingContainer::class, $generator->container());
        self::assertSame($this->names(Quidam::create(seed: 1)), $this->names($generator), 'same output as built-in');

        $generator->withProvider(new FixedProviderPack())->fork('a')->secure();
        self::assertSame(4, $factory->created, 'with*(), fork() and secure() rebuild through the same factory');
        self::assertInstanceOf(SecureRandomizerInterface::class, Quidam::createSecure(containerFactory: $factory)->random());
    }

    public function testWithDefinitionReplacesExtensionEverywhere(): void
    {
        $generator = Quidam::create();
        $fixed = $generator->withDefinition(PersonExtensionInterface::class, FixedPerson::class);

        self::assertNotSame($generator, $fixed);
        self::assertNotInstanceOf(FixedPerson::class, $generator->ext(PersonExtensionInterface::class));
        self::assertSame('Johny Fixed', $fixed->ext(PersonExtensionInterface::class)->name());
        self::assertSame('Johny Fixed', $fixed->ext(CarExtension::class)->owner());
        self::assertStringStartsWith('Fixed', $fixed->ext(CompanyExtensionInterface::class)->company());
        self::assertMatchesRegularExpression('/johny|fixed/', $fixed->ext(InternetExtensionInterface::class)->email());
    }

    public function testWithDefinitionsAndObjectDefinition(): void
    {
        $person = new FixedPerson(new XoshiroRandomizer());
        $generator = Quidam::create()->withDefinitions([PersonExtensionInterface::class => $person]);

        self::assertSame($person, $generator->ext(PersonExtensionInterface::class));
        self::assertSame($person, $generator->withDefinition('foo', 'bar')->ext(PersonExtensionInterface::class), 'objects are shared');
    }

    public function testWithProvider(): void
    {
        $generator = Quidam::create(seed: 1)->withProvider(new FixedProviderPack());

        self::assertSame('Johny Fixed', $generator->ext(PersonExtensionInterface::class)->name());
        self::assertSame(1, $generator->ext(Seed::class)->value, 'other definitions kept');
    }

    public function testExtThrowsForUnknownId(): void
    {
        $this->expectException(DefinitionNotFoundException::class);
        Quidam::create()->ext('NotExisting\\Extension'); // @phpstan-ignore argument.type
    }

    public function testExtThrowsWhenEntryHasWrongType(): void
    {
        $generator = Quidam::create()->withDefinition(PersonExtensionInterface::class, new \stdClass());

        $this->expectException(ContainerException::class);
        $generator->ext(PersonExtensionInterface::class);
    }

    public function testUnresolvableExtension(): void
    {
        $this->expectException(ContainerException::class);
        $this->expectExceptionMessageMatches('/UnresolvableExtension" cannot be built/');
        Quidam::create()->ext(UnresolvableExtension::class);
    }

    public function testSubclassKeepsType(): void
    {
        $generator = new readonly class (Quidam::create()->container()) extends Quidam {
            public function car(): CarExtension
            {
                return $this->ext(CarExtension::class);
            }
        };

        $generator = $generator::create()->withDefinition('foo', new \stdClass())->fork('a');
        self::assertStringStartsWith('Car ', $generator->car()->brand());
    }

    public function testOptional(): void
    {
        $generator = Quidam::create();

        self::assertNull($generator->optional(static fn (): string => 'value', 0));
        self::assertSame('value', $generator->optional(static fn (): string => 'value', 100));
        self::assertSame($generator, $generator->optional(static fn (Quidam $g): Quidam => $g, 1.0));
    }

    public function testSeedAndClockGiveRepeatableData(): void
    {
        $clock = new FrozenClock(new DateTimeImmutable('2026-01-01 12:00:00 UTC'));

        self::assertSame($this->names(Quidam::create(123, $clock)), $this->names(Quidam::create(123, $clock)));
        self::assertNotSame($this->names(Quidam::create(123, $clock)), $this->names(Quidam::create(124, $clock)));
        self::assertSame($clock, Quidam::create(clock: $clock)->ext(ClockInterface::class));
    }

    public function testCreateSeedsDefaultRandomizer(): void
    {
        self::assertSame(new XoshiroRandomizer(42)->getInt(0, PHP_INT_MAX), Quidam::create(42)->random()->getInt(0, PHP_INT_MAX));
        self::assertSame(42, Quidam::create(42)->ext(Seed::class)->value);
        self::assertNull(Quidam::create()->ext(Seed::class)->value);
    }

    public function testUnseededIsRandom(): void
    {
        self::assertNotSame($this->names(Quidam::create()), $this->names(Quidam::create()));
    }

    public function testForkDependsOnlyOnSeedAndKey(): void
    {
        $first = Quidam::create(seed: 10);
        $second = Quidam::create(seed: 10);
        $second->ext(PersonExtensionInterface::class)->name(); // extra calls before the fork don't matter
        $this->names($second->fork('other'));

        self::assertSame($this->names($first->fork('users.1')), $this->names($second->fork('users.1')));
        self::assertSame($this->names($first->fork(7)), $this->names($second->fork('7')));
        self::assertNotSame($this->names($first->fork('users.1')), $this->names($first->fork('users.2')));
        self::assertSame($this->names($first->fork('a')->fork('b')), $this->names($second->fork('a')->fork('b')));
        self::assertNull(Quidam::create()->fork('a')->ext(Seed::class)->value);
    }

    public function testForkSharesUniqueAndSequenceMemory(): void
    {
        $generator = Quidam::create(seed: 1);
        $digit = static fn (Quidam $g): int => $g->ext(NumberExtensionInterface::class)->randomDigit();

        $digits = [];
        for ($i = 0; $i < 10; ++$i) {
            $digits[] = $generator->fork('row.' . $i)->unique('digit', $digit);
        }

        sort($digits);
        self::assertSame(range(0, 9), $digits);
        self::assertSame(1, $generator->fork('a')->sequence('ids'));
        self::assertSame(2, $generator->sequence('ids'));
        self::assertNotSame($generator->ext(PersonExtensionInterface::class), $generator->fork('a')->ext(PersonExtensionInterface::class));

        $this->expectException(OverflowException::class);
        $generator->fork('row.10')->unique('digit', $digit, 50);
    }

    public function testForkEmptyKey(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Quidam::create()->fork(' ');
    }

    public function testManyAndSequence(): void
    {
        $generator = Quidam::create();
        $rows = $generator->many(3, static fn (Quidam $g, int $i): array => ['id' => $i + 1]);

        self::assertSame([1, 2, 3], array_column($rows, 'id'));
        self::assertSame([1, 2], [$generator->sequence('users'), $generator->sequence('users')]);
        self::assertSame(1000, $generator->sequence('invoices', 1000));
        $generator->resetSequence('users');
        self::assertSame(1, $generator->sequence('users'));
        self::assertSame(1, $generator->withDefinition('foo', 'bar')->sequence('users'), 'with*() starts again');

        $this->expectException(InvalidArgumentException::class);
        $generator->many(-1, static fn (): int => 1);
    }

    public function testSecure(): void
    {
        $clock = new FrozenClock(new DateTimeImmutable('2026-01-01 12:00:00 UTC'));
        $secure = Quidam::createSecure($clock);

        self::assertInstanceOf(SecureRandomizer::class, $secure->random());
        self::assertSame($secure->random(), $secure->ext(SecureRandomizerInterface::class));
        self::assertSame($clock, $secure->ext(ClockInterface::class), 'clock passed on');
        self::assertInstanceOf(SecureRandomizerInterface::class, $secure->fork('a')->random(), 'fork stays secure');
        self::assertInstanceOf(SecureRandomizerInterface::class, $secure->withProvider(new FixedProviderPack())->random(), 'with*() stays secure');
        self::assertInstanceOf(SecureRandomizerInterface::class, Quidam::create(seed: 5)->secure()->random());
        self::assertMatchesRegularExpression('/^\S+ \S+$/', $secure->ext(PersonExtensionInterface::class)->name());
    }

    public function testSecureUsesWhatSecureRandomizerInterfacePointsTo(): void
    {
        $replaced = Quidam::create()->withDefinition(SecureRandomizerInterface::class, FakeSecureRandomizer::class)->secure();
        self::assertInstanceOf(FakeSecureRandomizer::class, $replaced->random());

        $pack = Quidam::createSecure(definitionPack: new FixedDefinitionPack());
        self::assertInstanceOf(FakeSecureRandomizer::class, $pack->random());
        self::assertSame('Johny Fixed', $pack->ext(PersonExtensionInterface::class)->name(), 'definition pack kept');
        self::assertSame($this->names($pack), $this->names(Quidam::createSecure(definitionPack: new FixedDefinitionPack())));
    }

    /** @return list<string> */
    private function names(Quidam $generator): array
    {
        return $generator->many(5, static fn (Quidam $g): string => $g->ext(PersonExtensionInterface::class)->name());
    }
}
