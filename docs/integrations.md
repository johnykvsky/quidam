# Integrations

Quidam needs no framework integration - `Quidam::create()` works anywhere. This page shows how to wire it into PHPUnit, Laravel, Symfony (with Doctrine fixtures and Foundry) and Slim, so that tests get a fresh instance, seeds are configurable and dates follow the framework's (mockable) clock.

* [PHPUnit - random seed you can reproduce](#phpunit---random-seed-you-can-reproduce)
* [Laravel](#laravel)
* [Symfony](#symfony)
* [Doctrine fixtures](#doctrine-fixtures)
* [Foundry](#foundry)
* [Slim](#slim)
* [Rules of thumb](#rules-of-thumb)

Quidam is usually a `require-dev` dependency - register it only in environments where it's installed (shown in every example).

The framework's container only holds the `Quidam` service - Quidam keeps its own container inside, so no [container adapter](containers.md) is needed for any framework. Examples omit `use` statements of extension interfaces (`Quidam\Definitions\Extension\PersonExtensionInterface`...). The framework snippets follow the frameworks' APIs; they aren't run by Quidam's test suite.

## PHPUnit - random seed you can reproduce

Random data finds more bugs than fixed data, but a failure has to be reproducible. Use a new seed on every run and print it when a test fails:

```php
use Quidam\Quidam;
use PHPUnit\Framework\TestCase;

abstract class QuidamTestCase extends TestCase
{
    protected Quidam $generator;

    private int $seed;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed = (int) (getenv('QUIDAM_SEED') ?: random_int(1, PHP_INT_MAX));
        $this->generator = Quidam::create(seed: $this->seed);
    }

    protected function onNotSuccessfulTest(Throwable $t): never
    {
        fwrite(STDERR, sprintf("\n%s failed with Quidam seed %d - rerun with QUIDAM_SEED=%d\n", $this->name(), $this->seed, $this->seed));

        parent::onNotSuccessfulTest($t);
    }
}
```

```text
$ vendor/bin/phpunit
testImportsUsers failed with Quidam seed 4127843901 - rerun with QUIDAM_SEED=4127843901

$ QUIDAM_SEED=4127843901 vendor/bin/phpunit --filter testImportsUsers     # same data, same failure
```

Every test gets a fresh instance (fresh unique memory and sequences). Add a frozen clock (`new FrozenClock('2026-01-01 12:00 UTC')`) to `create()` when the test depends on dates - see [Clock](quidam.md#clock). The same base class works for Pest (`uses(QuidamTestCase::class)`).

## Laravel

Register the instance in a service provider:

```php
// app/Providers/AppServiceProvider.php
use Quidam\Quidam;

public function register(): void
{
    if (class_exists(Quidam::class)) {      // dev dependency - not installed in production
        $this->app->scoped(Quidam::class, fn () => Quidam::create(
            seed: config('quidam.seed'),
            clock: new LaravelClock(),
        ));
    }
}
```

```php
// config/quidam.php
return [
    'seed' => env('QUIDAM_SEED') !== null ? (int) env('QUIDAM_SEED') : null,
];
```

`scoped()` gives one instance per request / job / test - Laravel creates a new application for every test, so each test starts with fresh unique memory and sequences.

### Clock - travelTo() and freezeTime() apply

Quidam's default clock reads system time, Laravel tests move time with `travelTo()` / `freezeTime()` (Carbon test now). A 6-line PSR-20 clock connects them:

```php
use DateTimeImmutable;
use Psr\Clock\ClockInterface;

final class LaravelClock implements ClockInterface
{
    public function now(): DateTimeImmutable
    {
        return now()->toImmutable();    // CarbonImmutable is a DateTimeImmutable
    }
}
```

Now `dateTimePast()`, `dateOfBirth()`, `uuid7()`, `jwt()`... follow the test time and the app timezone.

### Factories

Laravel factories come with `$this->faker`. Use Quidam next to it (or instead of it) through the container:

```php
use Quidam\Quidam;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserFactory extends Factory
{
    public function definition(): array
    {
        $generator = app(Quidam::class);
        $person = $generator->profile()->person();

        return [
            'name' => $person['name'],
            'email' => $generator->unique('users.email', fn (Quidam $d) => $d->internet()->email()),
            'birth_date' => $person['dateOfBirth'],
            'password' => bcrypt('password'),
        ];
    }
}
```

With many factories, move `app(Quidam::class)` to a trait (`protected function generator(): Quidam`). Quidam's `unique()` is per key and lives as long as the scoped instance - the whole test, so emails stay unique across all factories that use `'users.email'`.

### Seeders - stable data with forks

```php
use Quidam\Quidam;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(Quidam $generator): void        // seeders are resolved from the container
    {
        foreach (range(1, 100) as $id) {
            $d = $generator->fork('users.' . $id);          // user 42 is the same on every seeding

            User::create([
                'name' => $d->person()->name(),
                'email' => $d->unique('users.email', fn (Quidam $x) => $x->internet()->email()),
                'password' => bcrypt('password'),
            ]);
        }
    }
}
```

Run with `QUIDAM_SEED=2026 php artisan db:seed` for the same database every time. Forks keep each user stable when you add columns or change the number of users - see [Forks](quidam.md#forks---stable-records).

## Symfony

Register the service for `dev` and `test` only:

```yaml
# config/services.yaml
when@dev: &quidam
    services:
        Quidam\Quidam:
            factory: ['Quidam\Quidam', 'create']
            arguments:
                $seed: '%env(default::int:QUIDAM_SEED)%'
                $clock: '@Psr\Clock\ClockInterface'

when@test: *quidam
```

* `default::` makes the seed `null` (random) when `QUIDAM_SEED` isn't set
* `Psr\Clock\ClockInterface` is the `clock` service of FrameworkBundle (needs `symfony/clock`). Tests using `ClockSensitiveTrait::mockTime()` move Quidam dates too. Without `symfony/clock` remove the `$clock` line
* the service is shared within a kernel; `KernelTestCase` / `WebTestCase` boot a new kernel per test, so each test starts with fresh unique memory and sequences

Inject it like any service:

```php
use Quidam\Quidam;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class ImportTest extends KernelTestCase
{
    public function testImport(): void
    {
        $generator = self::getContainer()->get(Quidam::class);
        $csv = $generator->file()->csv(100);
        // ...
    }
}
```

## Doctrine fixtures

Fixture classes of `doctrine/doctrine-fixtures-bundle` are services - inject Quidam into the constructor:

```php
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Quidam\Quidam;

final class UserFixtures extends Fixture
{
    public function __construct(
        private readonly Quidam $generator,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        foreach (range(1, 50) as $id) {
            $d = $this->generator->fork('users.' . $id);
            $person = $d->profile()->person();

            $user = new User(
                name: $person['name'],
                email: $d->unique('users.email', fn (Quidam $x) => $x->internet()->email($person['firstName'], $person['lastName'])),
            );

            $manager->persist($user);
            $this->addReference('user-' . $id, $user);
        }

        $manager->flush();
    }
}
```

`QUIDAM_SEED=2026 bin/console doctrine:fixtures:load` loads the same data every time.

## Foundry

Foundry (`zenstruck/foundry`) gives factories a Faker instance through `self::faker()`. In a Symfony app, Foundry factories can be services (autoconfigured), so Quidam can be injected:

```php
use App\Entity\User;
use Quidam\Quidam;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

final class UserFactory extends PersistentObjectFactory
{
    public function __construct(
        private readonly Quidam $generator,
    ) {
        parent::__construct();
    }

    public static function class(): string
    {
        return User::class;
    }

    protected function defaults(): array
    {
        return [
            'name' => $this->generator->person()->name(),
            'email' => $this->generator->unique('users.email', fn (Quidam $d) => $d->internet()->email()),
        ];
    }
}

UserFactory::createMany(10);    // in KernelTestCase - the factory comes from the container
```

Limits of this approach:

* constructor injection needs the kernel - in plain unit tests (no kernel booted) Foundry creates factories with `new static()`, so a factory with a required constructor argument fails with `CannotCreateFactory`. For factories used there, give the argument a default (`?Quidam $generator = null`, then `$this->generator ?? Quidam::create()`) or keep `self::faker()`
* Foundry's own `faker.seed` config doesn't seed Quidam - set `QUIDAM_SEED` too

## Slim

Slim 4 takes any PSR-11 container - usually PHP-DI, as in the Slim skeleton. Add a definition for dev / test:

```php
// app/dependencies.php
use DI\ContainerBuilder;
use Psr\Clock\ClockInterface;
use Psr\Container\ContainerInterface;
use Quidam\Quidam;

return function (ContainerBuilder $containerBuilder): void {
    if (class_exists(Quidam::class)) {      // dev dependency - not installed in production
        $containerBuilder->addDefinitions([
            Quidam::class => fn (ContainerInterface $c) => Quidam::create(
                seed: getenv('QUIDAM_SEED') !== false ? (int) getenv('QUIDAM_SEED') : null,
                clock: $c->has(ClockInterface::class) ? $c->get(ClockInterface::class) : null,
            ),
        ]);
    }
};
```

* PHP-DI keeps one instance per container. The Slim skeleton's `TestCase::getAppInstance()` builds a new container for every call, so each test that creates its app starts with fresh unique memory and sequences
* Slim has no clock of its own - define a PSR-20 `ClockInterface` (`symfony/clock`, `lcobucci/clock`...) and Quidam follows it; without one Quidam uses system time
* under a long-running server (RoadRunner, Swoole, FrankenPHP worker mode) the container lives across requests, so does the instance and its unique memory - use `$c->make(Quidam::class)` where every call should get a fresh one

Get it from the container in tests, set a frozen clock first when the test depends on dates:

```php
use Psr\Clock\ClockInterface;
use Quidam\Clock\FrozenClock;
use Quidam\Quidam;
use Tests\TestCase;

final class ImportActionTest extends TestCase
{
    public function testImport(): void
    {
        $app = $this->getAppInstance();
        $container = $app->getContainer();
        $container->set(ClockInterface::class, new FrozenClock('2026-01-01 12:00 UTC'));   // before Quidam is resolved

        $generator = $container->get(Quidam::class);
        $request = $this->createRequest('POST', '/import')
            ->withParsedBody(['csv' => $generator->file()->csv(100)]);

        $response = $app->handle($request);
        // ...
    }
}
```

Slim has no seeders - in a seeding script (Phinx seed, Doctrine fixtures, plain CLI command) take Quidam from the same container and use `fork('users.' . $id)` as in [Laravel seeders](#seeders---stable-data-with-forks).

## Rules of thumb

* **one instance per test** - unique memory and sequences belong to the instance; scoped / per-kernel services give that for free, never keep a Quidam in a static property shared across tests
* **framework clock** - pass the framework's clock (Laravel adapter, Symfony `clock` service, a PSR-20 clock in Slim's container), so time travel in tests applies to generated dates too
* **seed from environment** - random by default, `QUIDAM_SEED` to reproduce a failure or get the same seeded database
* **forks for seeders and fixtures** - `fork('table.' . $id)` keeps records stable while the seeding code changes
* **unique keys per column** - `'users.email'` shared by every factory, fixture and seeder writing that column
