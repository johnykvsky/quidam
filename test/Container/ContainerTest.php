<?php

declare(strict_types = 1);

namespace Quidam\Test\Container;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Quidam\Container\ContainerFactoryInterface;
use Quidam\Container\QuidamContainerInterface;
use Quidam\Container\SimpleContainer;
use Quidam\Container\SimpleContainerFactory;
use Quidam\Core\Person;
use Quidam\Core\Randomizer\CoreRandomizer;
use Quidam\Core\Randomizer\XoshiroRandomizer;
use Quidam\Definitions\Extension\PersonExtensionInterface;
use Quidam\Definitions\Randomizer\RandomizerInterface;
use Quidam\Exception\ContainerException;
use Quidam\Test\Fixtures\CarExtension;
use Quidam\Test\Fixtures\CircularA;
use Quidam\Test\Fixtures\FixedPerson;
use Quidam\Test\Fixtures\RecordingContainerFactory;
use Quidam\Test\Fixtures\UnresolvableExtension;

/**
 * Contract of Quidam containers - the built-in one, and an adapter delegating to it (shape of a third-party adapter)
 */
final class ContainerTest extends TestCase
{
    /** @return iterable<string, array{ContainerFactoryInterface}> */
    public static function factories(): iterable
    {
        yield 'built-in' => [new SimpleContainerFactory()];
        yield 'adapter' => [new RecordingContainerFactory()];
    }

    private static function container(ContainerFactoryInterface $factory): QuidamContainerInterface
    {
        return $factory->create([
            RandomizerInterface::class => XoshiroRandomizer::class,
            PersonExtensionInterface::class => Person::class,
        ]);
    }

    #[DataProvider('factories')]
    public function testAutowiresClassWithDependenciesAndDefaults(ContainerFactoryInterface $factory): void
    {
        $car = self::container($factory)->get(CarExtension::class);

        self::assertInstanceOf(CarExtension::class, $car);
        self::assertStringStartsWith('Car ', $car->brand());
    }

    #[DataProvider('factories')]
    public function testReturnsSameInstance(ContainerFactoryInterface $factory): void
    {
        $container = self::container($factory);

        self::assertSame($container->get(PersonExtensionInterface::class), $container->get(PersonExtensionInterface::class));
    }

    #[DataProvider('factories')]
    public function testObjectDefinition(ContainerFactoryInterface $factory): void
    {
        $randomizer = new XoshiroRandomizer(1);

        self::assertSame($randomizer, $factory->create([RandomizerInterface::class => $randomizer])->get(RandomizerInterface::class));
    }

    #[DataProvider('factories')]
    public function testClosureParametersResolvedByType(ContainerFactoryInterface $factory): void
    {
        $container = self::container($factory)->withDefinitions([
            CarExtension::class => static fn (RandomizerInterface $r, PersonExtensionInterface $p): CarExtension => new CarExtension($r, $p, 'Auto'),
            'self' => static fn (ContainerInterface $c): ContainerInterface => $c,
        ]);

        $car = $container->get(CarExtension::class);
        self::assertInstanceOf(CarExtension::class, $car);
        self::assertStringStartsWith('Auto ', $car->brand());
        self::assertInstanceOf(ContainerInterface::class, $container->get('self'));
    }

    #[DataProvider('factories')]
    public function testScalarDefinition(ContainerFactoryInterface $factory): void
    {
        self::assertSame('bar', $factory->create(['foo' => 'bar'])->get('foo'));
    }

    #[DataProvider('factories')]
    public function testWithDefinitionsIsImmutableAndReplacesDependencies(ContainerFactoryInterface $factory): void
    {
        $container = self::container($factory);
        $person = $container->get(PersonExtensionInterface::class);
        $replaced = $container->withDefinitions([PersonExtensionInterface::class => FixedPerson::class]);

        self::assertNotSame($container, $replaced);
        self::assertSame($person, $container->get(PersonExtensionInterface::class));
        self::assertInstanceOf(FixedPerson::class, $replaced->get(PersonExtensionInterface::class));

        $car = $replaced->get(CarExtension::class);
        self::assertInstanceOf(CarExtension::class, $car);
        self::assertSame('Johny Fixed', $car->owner(), 'dependents get the replaced definition');
        self::assertArrayHasKey(RandomizerInterface::class, $replaced->definitions());
    }

    #[DataProvider('factories')]
    public function testHas(ContainerFactoryInterface $factory): void
    {
        $container = self::container($factory);

        self::assertTrue($container->has(RandomizerInterface::class));
        self::assertTrue($container->has(Person::class));
        self::assertFalse($container->has('unknown'));
    }

    #[DataProvider('factories')]
    public function testUnknownIdThrowsNotFound(ContainerFactoryInterface $factory): void
    {
        $this->expectException(NotFoundExceptionInterface::class);
        self::container($factory)->get('unknown');
    }

    #[DataProvider('factories')]
    public function testUnresolvableScalarThrows(ContainerFactoryInterface $factory): void
    {
        $this->expectException(ContainerExceptionInterface::class);
        $this->expectExceptionMessage('$required');
        self::container($factory)->get(UnresolvableExtension::class);
    }

    #[DataProvider('factories')]
    public function testCircularDependencyThrows(ContainerFactoryInterface $factory): void
    {
        $this->expectException(ContainerExceptionInterface::class);
        $this->expectExceptionMessageMatches('/[Cc]ircular dependency/');
        self::container($factory)->get(CircularA::class);
    }

    public function testSimpleContainerInjectsItself(): void
    {
        $container = new SimpleContainer(['self' => static fn (SimpleContainer $c): SimpleContainer => $c]);

        self::assertSame($container, $container->get('self'));
    }

    public function testSimpleContainerUnboundInterface(): void
    {
        $this->expectException(NotFoundExceptionInterface::class);
        new SimpleContainer()->get(PersonExtensionInterface::class);
    }

    public function testSimpleContainerNotInstantiable(): void
    {
        $this->expectException(ContainerException::class);
        new SimpleContainer()->get(CoreRandomizer::class);
    }
}
