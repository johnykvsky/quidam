<?php

declare(strict_types = 1);

namespace Quidam\Testing;

use Closure;
use DateTimeInterface;
use Quidam\Quidam;
use ReflectionClass;
use ReflectionMethod;
use UnitEnum;

/**
 * Seeded output snapshot - guards a seed stability promise in any test framework: the same seed, clock and calls give
 * the same values. Used by Quidam's own tests; language packs and extensions use it for theirs.
 *
 *   $snapshot = new SeedSnapshot(__DIR__ . '/snapshot.json', fn (int $seed) => MyGenerator::create($seed, $clock));
 *   $output = $snapshot->generate([[PersonExtensionInterface::class, 'name', []], ...]);
 *   getenv('UPDATE_SEED_SNAPSHOT') === '1' ? $snapshot->save($output) : assertSame([], $snapshot->changed($output));
 *
 * Every call runs on a fresh instance, so a changed value points to the method which changed.
 */
final readonly class SeedSnapshot
{
    /**
     * @param Closure(int): Quidam $factory instance for a seed - with a frozen clock, for repeatable dates
     * @param list<int> $seeds
     * @param int $calls calls per entry - the second one shows state kept between calls (i.e. uuid7() counter)
     */
    public function __construct(
        private string $file,
        private Closure $factory,
        private array $seeds = [1, 42, 2026],
        private int $calls = 2,
    ) {
    }

    /**
     * Values of each call and scenario for every seed, by label like '42 Person::name("female")'
     *
     * @param list<array{class-string, string, array<int|string, mixed>}> $calls [class, method, arguments]
     * @param array<string, Closure(Quidam): mixed> $scenarios named checks of generator level features
     * @return array<string, mixed>
     */
    public function generate(array $calls, array $scenarios = []): array
    {
        $output = [];

        foreach ($this->seeds as $seed) {
            foreach ($calls as [$class, $method, $arguments]) {
                $generator = ($this->factory)($seed)->ext($class);
                $label = sprintf('%d %s::%s(%s)', $seed, self::label($class), $method, self::describe($arguments));

                $values = [];
                for ($call = 0; $call < $this->calls; ++$call) {
                    $values[] = self::normalize($generator->{$method}(...$arguments));
                }

                $output[$label] = $values;
            }

            foreach ($scenarios as $name => $scenario) {
                $output[sprintf('%d Quidam %s', $seed, $name)] = self::normalize($scenario(($this->factory)($seed)));
            }
        }

        // the same shape as read back from the file (floats, unicode) - labels stay string keys
        /** @var array<string, mixed> $decoded */
        $decoded = json_decode(json_encode($output, JSON_THROW_ON_ERROR), true, flags: JSON_THROW_ON_ERROR);

        return $decoded;
    }

    /**
     * Labels whose values differ from the snapshot file, or which are new or gone - empty when nothing changed
     *
     * @param array<string, mixed> $output result of generate()
     * @return list<string>
     */
    public function changed(array $output): array
    {
        $expected = is_file($this->file) ? json_decode((string) file_get_contents($this->file), true, flags: JSON_THROW_ON_ERROR) : [];
        $expected = is_array($expected) ? $expected : [];

        $changed = [];
        foreach (array_keys($expected + $output) as $label) {
            if (($expected[$label] ?? null) !== ($output[$label] ?? null)) {
                $changed[] = (string) $label;
            }
        }

        return $changed;
    }

    /**
     * Writes the snapshot file - after an intended change, which belongs in the changelog
     *
     * @param array<string, mixed> $output result of generate()
     */
    public function save(array $output): void
    {
        file_put_contents($this->file, json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) . "\n");
    }

    /**
     * Public instance methods of the classes which aren't in the calls - "Class::method" - so new methods can't skip
     * the snapshot
     *
     * @param list<class-string> $classes
     * @param list<array{class-string, string, array<int|string, mixed>}> $calls
     * @param list<string> $ignored "Class::method" of methods which don't depend on randomness
     * @return list<string>
     */
    public static function uncovered(array $classes, array $calls, array $ignored = []): array
    {
        $covered = [...array_map(static fn (array $call): string => $call[0] . '::' . $call[1], $calls), ...$ignored];
        $missing = [];

        foreach ($classes as $class) {
            foreach (new ReflectionClass($class)->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                $name = $class . '::' . $method->getName();

                if (!$method->isConstructor() && !$method->isStatic() && !in_array($name, $covered, true)) {
                    $missing[] = $name;
                }
            }
        }

        return $missing;
    }

    /**
     * Value as stored in the snapshot: dates as ISO strings, enums as "Class::Case", objects as class and public
     * properties, binary and long strings as hash
     */
    public static function normalize(mixed $value): mixed
    {
        return match (true) {
            $value instanceof DateTimeInterface => $value->format('Y-m-d\TH:i:s.uP'),
            $value instanceof UnitEnum => $value::class . '::' . $value->name,
            is_array($value) => array_map(self::normalize(...), $value),
            is_object($value) => ['class' => $value::class, ...array_map(self::normalize(...), get_object_vars($value))],
            is_string($value) && (!mb_check_encoding($value, 'UTF-8') || strlen($value) > 400) => sprintf('sha256:%s (%d bytes)', hash('sha256', $value), strlen($value)),
            default => $value,
        };
    }

    /** @param array<int|string, mixed> $arguments */
    private static function describe(array $arguments): string
    {
        return implode(', ', array_map(static fn (mixed $argument): string => (string) json_encode($argument, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), $arguments));
    }

    /**
     * Short name of the class - extension interfaces without the suffix ("Person" for PersonExtensionInterface)
     *
     * @param class-string $class
     */
    private static function label(string $class): string
    {
        $short = new ReflectionClass($class)->getShortName();

        return str_ends_with($short, 'ExtensionInterface') ? substr($short, 0, -strlen('ExtensionInterface')) : $short;
    }
}
