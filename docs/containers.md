# Containers

`Quidam` doesn't depend on any container library. It uses two interfaces:

* `ContainerFactoryInterface::create(array $definitions): QuidamContainerInterface`
* `QuidamContainerInterface` - PSR-11 (`get()`, `has()`) plus `definitions()` and `withDefinitions(array $definitions)`

The built-in `SimpleContainer` is the default. Another container is plugged in with an adapter - no change in Quidam:

```php
$generator = Quidam::create(seed: 1, containerFactory: new PhpDiContainerFactory());
```

`with*()`, `withProvider()`, `fork()` and `secure()` call `withDefinitions()`, so every container built from one generator comes from the same factory.

## What an adapter must do

* **Immutability** - `withDefinitions()` returns a new container with definitions added or replaced, and fresh instances. Objects given as definitions are shared (that's how `fork()` shares unique / sequence memory).
* **Definitions** - understand what Quidam and language packs pass:
  * class-string - autowired; when it differs from the id it's an alias (`PersonExtensionInterface => Person::class`)
  * `Closure` - factory, parameters resolved from the container by type (`fn (Seed $seed): RandomizerInterface => new XoshiroRandomizer($seed->value)`); `secure()` uses one too
  * object - returned as is
  * any other value - returned as is
* **Autowiring** - classes without a definition are built on first use (`ext(MyExtension::class)`).
* **Singletons** - every entry once per container.
* **Exceptions** - PSR-11 `NotFoundExceptionInterface` / `ContainerExceptionInterface`; `ext()` wraps them in Quidam exceptions.

The built-in container shares one instance between an alias and its class (`person()` and `ext(Person::class)`); an adapter doesn't have to.

`test/Container/ContainerTest.php` is the contract every container passes - copy it to test an adapter.

## Example - PHP-DI adapter

Quidam shipped this adapter until PHP-DI was dropped as a dependency (`composer require php-di/php-di` to use it). It passed the whole test suite then.

```php
use Closure;
use DI\Container;
use DI\ContainerBuilder;
use DI\Definition\Definition;
use DI\Definition\Helper\DefinitionHelper;
use Quidam\Container\ContainerFactoryInterface;
use Quidam\Container\QuidamContainerInterface;

use function DI\autowire;
use function DI\factory;
use function DI\value;

final class PhpDiContainerFactory implements ContainerFactoryInterface
{
    /** @var array<string, DefinitionHelper> autowire helpers are reusable - creating them is half of create() */
    private static array $autowired = [];

    public function create(array $definitions): QuidamContainerInterface
    {
        $builder = new ContainerBuilder();
        $builder->useAutowiring(true);
        $builder->useAttributes(false);
        $builder->addDefinitions(array_map(self::normalize(...), $definitions));

        return new PhpDiContainer($builder->build(), $definitions, $this);
    }

    private static function normalize(mixed $definition): mixed
    {
        if (is_string($definition)) {
            return self::$autowired[$definition] ?? (class_exists($definition) ? self::$autowired[$definition] = autowire($definition) : $definition);
        }

        return match (true) {
            $definition instanceof DefinitionHelper, $definition instanceof Definition => $definition, // PHP-DI's own
            $definition instanceof Closure => factory($definition), // typed parameters resolved by PHP-DI
            is_object($definition) => value($definition),
            default => $definition,
        };
    }
}

final readonly class PhpDiContainer implements QuidamContainerInterface
{
    /** @param array<string, mixed> $definitions */
    public function __construct(
        private Container $container,
        private array $definitions,
        private ContainerFactoryInterface $factory,
    ) {
    }

    public function get(string $id): mixed
    {
        return $this->container->get($id);
    }

    public function has(string $id): bool
    {
        return $this->container->has($id);
    }

    public function definitions(): array
    {
        return $this->definitions;
    }

    public function withDefinitions(array $definitions): QuidamContainerInterface
    {
        return $this->factory->create([...$this->definitions, ...$definitions]);
    }
}
```

With it, definitions can also be PHP-DI helpers: `withDefinition(Car::class, DI\autowire()->constructorParameter('prefix', 'Auto'))`.

### Is it worth it?

Measured (see [performance](performance.md), "Quidam (PHP-DI)" vs "Quidam (simple)" columns): the same speed per generated value, but startup 8.9 ms instead of 3.1 ms, a new instance 5.4 µs instead of 1.2 µs, and the first value of a new instance 25 µs instead of 7.5 µs - PHP-DI's compilation can't help, because Quidam builds a new container on every `with*()` / `fork()`. Use an adapter when you need PHP-DI features in definitions; to use Quidam in an application with PHP-DI, Symfony or Laravel container, register `Quidam` as a service (`Quidam::create(...)` in a factory) - no adapter needed.
