<?php

/**
 * Runs benchmark scenarios of ONE library in this process and prints JSON. Started by run.php:
 *   php worker.php <faker|dummygenerator|quidam> <micro|startup|realtext-cold|unique> <default|seeded>
 *
 * "unique" prints progress lines - run.php runs it under `timeout`, a single call may never return
 */

declare(strict_types = 1);

$start = hrtime(true);

[, $library, $suite, $mode] = $argv + [null, null, null, null];
$seed = $mode === 'seeded' ? 2026 : null;

$paths = [
    'faker' => getenv('FAKER_PATH') ?: __DIR__ . '/../../fakerphp',
    'dummygenerator' => getenv('DUMMYGENERATOR_PATH') ?: __DIR__ . '/../../dummygenerator',
    'quidam' => getenv('QUIDAM_PATH') ?: __DIR__ . '/..',
];

if (!isset($paths[$library]) || !in_array($suite, ['micro', 'startup', 'realtext-cold', 'unique'], true) || !in_array($mode, ['default', 'seeded'], true)) {
    fwrite(STDERR, "Usage: php worker.php <faker|dummygenerator|quidam> <micro|startup|realtext-cold|unique> <default|seeded>\n");
    exit(2);
}

require $paths[$library] . '/vendor/autoload.php';

// Quidam extension interfaces - short names for the adapter below
use Quidam\Definitions\Extension\AddressExtensionInterface as Address;
use Quidam\Definitions\Extension\CompanyExtensionInterface as Company;
use Quidam\Definitions\Extension\DateTimeExtensionInterface as DateTime;
use Quidam\Definitions\Extension\InternetExtensionInterface as Internet;
use Quidam\Definitions\Extension\LoremExtensionInterface as Lorem;
use Quidam\Definitions\Extension\NumberExtensionInterface as Number;
use Quidam\Definitions\Extension\PaymentExtensionInterface as Payment;
use Quidam\Definitions\Extension\PersonExtensionInterface as Person;
use Quidam\Definitions\Extension\PhoneNumberExtensionInterface as PhoneNumber;
use Quidam\Definitions\Extension\ProfileExtensionInterface as Profile;
use Quidam\Definitions\Extension\UuidExtensionInterface as Uuid;

/**
 * Library adapter: create() returns a generator, scenarios() returns name => Closure (one operation)
 */
$adapters = [
    'faker' => [
        'create' => static function (?int $seed): Faker\Generator {
            $faker = Faker\Factory::create();
            if ($seed !== null) {
                $faker->seed($seed);
            }

            return $faker;
        },
        'scenarios' => static fn (Faker\Generator $f): array => [
            'name' => static fn () => $f->name(),
            'firstName' => static fn () => $f->firstName(),
            'lastName' => static fn () => $f->lastName(),
            'email' => static fn () => $f->email(),
            'userName' => static fn () => $f->userName(),
            'url' => static fn () => $f->url(),
            'password' => static fn () => $f->password(),
            'address' => static fn () => $f->address(),
            'city' => static fn () => $f->city(),
            'postcode' => static fn () => $f->postcode(),
            'phoneNumber' => static fn () => $f->phoneNumber(),
            'company' => static fn () => $f->company(),
            'numberBetween' => static fn () => $f->numberBetween(1, 100),
            'randomElement' => static fn () => $f->randomElement(LIST10),
            'boolean' => static fn () => $f->boolean(),
            'dateTimeBetween' => static fn () => $f->dateTimeBetween('-1 year', 'now'),
            'uuid' => static fn () => $f->uuid(),
            'sentence' => static fn () => $f->sentence(),
            'paragraph' => static fn () => $f->paragraph(),
            'text200' => static fn () => $f->text(200),
            'iban' => static fn () => $f->iban(),
            'creditCardNumber' => static fn () => $f->creditCardNumber(),
            'optionalEmail' => static fn () => $f->optional(0.5)->email(),
            'template' => static fn () => $f->parse('{{firstName}} {{lastName}} <{{email}}>'),
            'userRecord' => static fn () => [
                'name' => $f->name(),
                'email' => $f->email(),
                'phone' => $f->phoneNumber(),
                'address' => $f->address(),
                'birthDate' => $f->dateTimeBetween('-80 years', '-18 years'),
                'uuid' => $f->uuid(),
                'company' => $f->company(),
                'createdAt' => $f->dateTimeBetween('-1 year', 'now'),
            ],
            'realText' => static fn () => $f->realText(200),
        ],
        // returns one-step closure: next unique email in one scope
        'unique' => static function (Faker\Generator $f, ?int $seed): Closure {
            $f->unique(true);

            return static fn () => $f->unique()->email();
        },
    ],
    'dummygenerator' => [
        'create' => static function (?int $seed): DummyGenerator\DummyGenerator {
            $generator = DummyGenerator\DummyGenerator::create();

            return $seed === null ? $generator : $generator->withDefinition(
                DummyGenerator\Definitions\Randomizer\RandomizerInterface::class,
                new DummyGenerator\Core\Randomizer\XoshiroRandomizer(seed: $seed),
            );
        },
        'scenarios' => static fn (DummyGenerator\DummyGenerator $g): array => [
            'name' => static fn () => $g->name(),
            'firstName' => static fn () => $g->firstName(),
            'lastName' => static fn () => $g->lastName(),
            'email' => static fn () => $g->email(),
            'userName' => static fn () => $g->userName(),
            'url' => static fn () => $g->url(),
            'password' => static fn () => $g->password(),
            'address' => static fn () => $g->address(),
            'city' => static fn () => $g->city(),
            'postcode' => static fn () => $g->postcode(),
            'phoneNumber' => static fn () => $g->phoneNumber(),
            'company' => static fn () => $g->company(),
            'numberBetween' => static fn () => $g->numberBetween(1, 100),
            'randomElement' => static fn () => $g->randomElement(LIST10),
            'boolean' => static fn () => $g->boolean(),
            'dateTimeBetween' => static fn () => $g->dateTimeBetween('-1 year', 'now'),
            'uuid' => static fn () => $g->uuid4(),
            'sentence' => static fn () => $g->sentence(),
            'paragraph' => static fn () => $g->paragraph(),
            'text200' => static fn () => $g->text(200),
            'iban' => static fn () => $g->iban(),
            'creditCardNumber' => static fn () => $g->creditCardNumber(),
            // optional() is a container-wide strategy in DummyGenerator - measured separately below
            'template' => static fn () => $g->parse('{{ firstName }} {{ lastName }} <{{ email }}>'),
            'userRecord' => static fn () => [
                'name' => $g->name(),
                'email' => $g->email(),
                'phone' => $g->phoneNumber(),
                'address' => $g->address(),
                'birthDate' => $g->dateTimeBetween('-80 years', '-18 years'),
                'uuid' => $g->uuid4(),
                'company' => $g->company(),
                'createdAt' => $g->dateTimeBetween('-1 year', 'now'),
            ],
        ],
        'unique' => static function (DummyGenerator\DummyGenerator $unused, ?int $seed): Closure {
            // unique is a container strategy - a generator with UniqueStrategy for every call (also nested ones)
            $container = DummyGenerator\Container\DiContainerFactory::all();
            $container->set(DummyGenerator\Strategy\StrategyInterface::class, new DummyGenerator\Strategy\UniqueStrategy(10000));
            if ($seed !== null) {
                $container->set(DummyGenerator\Definitions\Randomizer\RandomizerInterface::class, new DummyGenerator\Core\Randomizer\XoshiroRandomizer(seed: $seed));
            }

            $generator = new DummyGenerator\DummyGenerator($container);

            return static fn () => $generator->email();
        },
        'optional' => static function (?int $seed): Closure {
            $container = DummyGenerator\Container\DiContainerFactory::all();
            $container->set(DummyGenerator\Strategy\StrategyInterface::class, new DummyGenerator\Strategy\ChanceStrategy(0.5));
            if ($seed !== null) {
                $container->set(DummyGenerator\Definitions\Randomizer\RandomizerInterface::class, new DummyGenerator\Core\Randomizer\XoshiroRandomizer(seed: $seed));
            }

            $generator = new DummyGenerator\DummyGenerator($container);

            return static fn () => $generator->email();
        },
    ],
    // used the way people write it - ext() on every call, no handles kept
    'quidam' => [
        'create' => static fn (?int $seed): Quidam\Quidam => Quidam\Quidam::create($seed),
        'scenarios' => static fn (Quidam\Quidam $g): array => [
            'name' => static fn () => $g->ext(Person::class)->name(),
            'firstName' => static fn () => $g->ext(Person::class)->firstName(),
            'lastName' => static fn () => $g->ext(Person::class)->lastName(),
            'email' => static fn () => $g->ext(Internet::class)->email(),
            'userName' => static fn () => $g->ext(Internet::class)->userName(),
            'url' => static fn () => $g->ext(Internet::class)->url(),
            'password' => static fn () => $g->ext(Internet::class)->password(),
            'address' => static fn () => $g->ext(Address::class)->address(),
            'city' => static fn () => $g->ext(Address::class)->city(),
            'postcode' => static fn () => $g->ext(Address::class)->postcode(),
            'phoneNumber' => static fn () => $g->ext(PhoneNumber::class)->phoneNumber(),
            'company' => static fn () => $g->ext(Company::class)->company(),
            'numberBetween' => static fn () => $g->ext(Number::class)->numberBetween(1, 100),
            'randomElement' => static fn () => $g->random()->randomElement(LIST10),
            'boolean' => static fn () => $g->ext(Number::class)->boolean(),
            'dateTimeBetween' => static fn () => $g->ext(DateTime::class)->dateTimeBetween('-1 year', 'now'),
            'uuid' => static fn () => $g->ext(Uuid::class)->uuid4(),
            'sentence' => static fn () => $g->ext(Lorem::class)->sentence(),
            'paragraph' => static fn () => $g->ext(Lorem::class)->paragraph(),
            'text200' => static fn () => $g->ext(Lorem::class)->text(200),
            'iban' => static fn () => $g->ext(Payment::class)->iban(),
            'creditCardNumber' => static fn () => $g->ext(Payment::class)->creditCardNumber(),
            'optionalEmail' => static fn () => $g->optional(static fn (Quidam\Quidam $x) => $x->ext(Internet::class)->email()),
            'userRecord' => static fn () => [
                'name' => $g->ext(Person::class)->name(),
                'email' => $g->ext(Internet::class)->email(),
                'phone' => $g->ext(PhoneNumber::class)->phoneNumber(),
                'address' => $g->ext(Address::class)->address(),
                'birthDate' => $g->ext(DateTime::class)->dateTimeBetween('-80 years', '-18 years'),
                'uuid' => $g->ext(Uuid::class)->uuid4(),
                'company' => $g->ext(Company::class)->company(),
                'createdAt' => $g->ext(DateTime::class)->dateTimeBetween('-1 year', 'now'),
            ],
            'userRecordProfile' => static function () use ($g): array {
                $person = $g->ext(Profile::class)->person();

                return [
                    'name' => $person['name'],
                    'email' => $person['email'],
                    'phone' => $g->ext(PhoneNumber::class)->phoneNumber(),
                    'address' => $g->ext(Address::class)->address(),
                    'birthDate' => $person['dateOfBirth'],
                    'uuid' => $g->ext(Uuid::class)->uuid4(),
                    'company' => $g->ext(Company::class)->company(),
                    'createdAt' => $g->ext(DateTime::class)->dateTimeBetween('-1 year', 'now'),
                ];
            },
            'getExtension' => static fn () => $g->ext(Person::class),
            'template' => static fn () => $g->ext(Quidam\Definitions\Addon\TemplateInterface::class)->parse('{{ person.firstName }} {{ person.lastName }} <{{ internet.email }}>'),
            'realText' => static fn () => $g->ext(Quidam\Definitions\Addon\TextInterface::class)->realText(150, 200),
        ],
        'unique' => static function (Quidam\Quidam $g, ?int $seed): Closure {
            return static fn () => $g->unique('users.email', static fn (Quidam\Quidam $x) => $x->ext(Internet::class)->email());
        },
    ],
];

const LIST10 = ['new', 'paid', 'shipped', 'delivered', 'returned', 'cancelled', 'refunded', 'pending', 'failed', 'on-hold'];

/**
 * Median / min / max nanoseconds per operation over $rounds rounds - each round ~$roundMs long, so slow and fast
 * operations take similar total time
 *
 * @return array{median: float, min: float, max: float, iterations: int}
 */
function measure(Closure $operation, int $rounds = 5, float $roundMs = 100): array
{
    // warm-up and calibration
    $calibration = 0;
    $time = hrtime(true);
    do {
        $operation();
        ++$calibration;
    } while ((hrtime(true) - $time) < 20e6 && $calibration < 100000);

    $perCall = (hrtime(true) - $time) / $calibration;
    $iterations = (int) max(3, min(2000000, $roundMs * 1e6 / max($perCall, 1)));

    $times = [];
    for ($round = 0; $round < $rounds; ++$round) {
        $time = hrtime(true);
        for ($i = 0; $i < $iterations; ++$i) {
            $operation();
        }

        $times[] = (hrtime(true) - $time) / $iterations;
    }

    sort($times);

    return ['median' => $times[intdiv($rounds, 2)], 'min' => $times[0], 'max' => $times[$rounds - 1], 'iterations' => $iterations];
}

$adapter = $adapters[$library];
$result = ['library' => $library, 'suite' => $suite, 'mode' => $mode];

switch ($suite) {
    // first instance in a fresh process: autoload + create + first value
    case 'startup':
        $generator = $adapter['create']($seed);
        $adapter['scenarios']($generator)['name']();
        $result['startupMs'] = (hrtime(true) - $start) / 1e6;
        $result['peakMemoryKb'] = memory_get_peak_usage() / 1024;
        break;

    // first realText() in a fresh process - includes building the model
    case 'realtext-cold':
        $scenarios = $adapter['scenarios']($adapter['create']($seed));
        if (!isset($scenarios['realText'])) {
            $result['realTextColdMs'] = null;
            break;
        }

        $before = memory_get_usage();
        $time = hrtime(true);
        $scenarios['realText']();
        $result['realTextColdMs'] = (hrtime(true) - $time) / 1e6;
        $result['realTextMemoryKb'] = (memory_get_usage() - $before) / 1024;
        break;

    case 'micro':
        // empty closure call - loop overhead included in every number below
        $result['scenarios']['(loop overhead)'] = measure(static fn () => null);

        // instance creation (warm - classes already loaded), with and without the first value
        $result['scenarios']['create'] = measure(static fn () => $adapter['create']($seed));
        $result['scenarios']['create+name'] = measure(static fn () => $adapter['scenarios']($adapter['create']($seed))['name']());

        $generator = $adapter['create']($seed);
        foreach ($adapter['scenarios']($generator) as $name => $operation) {
            $result['scenarios'][$name] = measure($operation);
        }

        if (isset($adapter['optional'])) {
            $result['scenarios']['optionalEmail'] = measure($adapter['optional']($seed));
        }

        $result['peakMemoryKb'] = memory_get_peak_usage() / 1024;
        break;

    // up to 10 000 unique emails in one scope - progress every 10, time at 1000, memory at 10 000
    case 'unique':
        $generator = $adapter['create']($seed);
        $before = memory_get_usage();
        $next = $adapter['unique']($generator, $seed);
        $time = hrtime(true);

        for ($i = 1; $i <= 10000; ++$i) {
            $next();

            if ($i % 10 === 0) {
                echo 'progress ', $i, ' ', (hrtime(true) - $time) / 1e6, "\n";
            }

            if ($i === 1000) {
                $result['ms1000'] = (hrtime(true) - $time) / 1e6;
            }
        }

        $result['memory10000Kb'] = (memory_get_usage() - $before) / 1024;
        echo 'result ', json_encode($result), "\n";
        exit(0);
}

echo json_encode($result, JSON_PRETTY_PRINT), "\n";
