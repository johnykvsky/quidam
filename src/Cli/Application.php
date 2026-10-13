<?php

declare(strict_types = 1);

namespace Quidam\Cli;

use BackedEnum;
use Closure;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Psr\Clock\ClockInterface;
use Quidam\Clock\FrozenClock;
use Quidam\Clock\SystemClock;
use Quidam\Definitions\Addon\TemplateInterface;
use Quidam\Exception\InvalidArgumentException;
use Quidam\Exception\QuidamExceptionInterface;
use Quidam\Exception\RuntimeException;
use Quidam\ProviderPack\ProviderPackInterface;
use Quidam\Quidam;
use ReflectionClass;
use ReflectionMethod;
use ReflectionParameter;
use UnitEnum;

/**
 * Command line interface - bin/quidam. All output goes to given streams, so it can be tested.
 */
final class Application
{
    private const int EXIT_OK = 0;
    private const int EXIT_ERROR = 1;
    private const int EXIT_USAGE = 2;

    private const array FORMATS = ['text', 'json', 'csv'];

    /** @var list<string> namespaces (and directories under src/) of language packs - living, then dead */
    private const array PACK_DIRECTORIES = ['LanguagePack', 'DeadLanguagePack'];

    private const string USAGE = <<<'TEXT'
        Usage:
          quidam <alias.method[(arguments)]> [options]
          quidam -t '<template>' [options]
          quidam --list [alias]

        Examples:
          quidam person.name
          quidam internet.email -n 5 --unique
          quidam 'number.numberBetween(1, 100)' --seed 42
          quidam -t '{{ person.name }} <{{ internet.email }}>' -n 3
          quidam profile.person -n 100 --format csv > people.csv
          quidam dateTime.dateTimePast --now '2026-01-01 12:00' --timezone Europe/Warsaw
          quidam -l pl_PL profile.citizen -f json

        Options:
          -n, --count <n>        number of values (default 1)
          -s, --seed <int>       seed - the same seed gives the same values
          -u, --unique           all values different from each other
          -t, --template <text>  template with {{ alias.method }} tokens instead of one expression
          -f, --format <format>  text (default), json or csv
          -l, --language <pack>  language pack: code (pl_PL) or ProviderPackInterface class name
          -c, --config <file>    PHP file returning fn (?int $seed, ClockInterface $clock): Quidam - own
                                 extensions, packs with arguments, withDefinition(); -l is applied on top
              --now <date>       frozen "now" for dates and time-based ids
              --timezone <tz>    timezone of dates (default: PHP default timezone)
              --list [alias]     list aliases, or methods of one alias
          -h, --help             this help

        Arguments in expressions and templates are PHP literals: 'text', "text", 12, 1.5, true, false, null,
        positional or named (min: 1).
        TEXT;

    /**
     * @param list<string> $argv arguments with script name, like $argv in PHP
     * @param resource $stdout
     * @param resource $stderr
     */
    public function run(array $argv, $stdout, $stderr): int
    {
        try {
            $options = $this->parse(array_slice($argv, 1));
        } catch (InvalidArgumentException $e) {
            fwrite($stderr, 'Error: ' . $e->getMessage() . "\n\nRun 'quidam --help' for usage.\n");

            return self::EXIT_USAGE;
        }

        if ($options['help']) {
            fwrite($stdout, self::USAGE . "\n");

            return self::EXIT_OK;
        }

        try {
            if ($options['list'] !== null) {
                fwrite($stdout, $this->list($options['list'], $options['config'], $options['language']));

                return self::EXIT_OK;
            }

            fwrite($stdout, $this->format($this->generate($options), $options['format'], $options['count']));

            return self::EXIT_OK;
        } catch (QuidamExceptionInterface $e) {
            fwrite($stderr, 'Error: ' . $e->getMessage() . "\n");

            return self::EXIT_ERROR;
        }
    }

    /**
     * @param array{expression: ?string, template: ?string, count: int, seed: ?int, unique: bool, format: string, now: ?string, timezone: ?string, list: ?string, language: class-string<ProviderPackInterface>|null, config: ?string, help: bool} $options
     * @return list<mixed>
     */
    private function generate(array $options): array
    {
        $timezone = new DateTimeZone($options['timezone'] ?? date_default_timezone_get());
        $clock = $options['now'] !== null ? new FrozenClock($this->date($options['now'], $timezone)) : new SystemClock($timezone);

        $generator = $this->generator($options['config'], $options['language'], $options['seed'], $clock);
        $template = $generator->ext(TemplateInterface::class);
        $expression = $options['expression'];
        $text = $options['template'];

        $generate = static fn (): mixed => $text !== null ? $template->parse($text) : $template->evaluate((string) $expression);

        return $generator->many($options['count'], static fn (Quidam $g): mixed => $options['unique']
            ? $g->unique('cli', static fn (): mixed => $generate())
            : $generate());
    }

    /**
     * Default Quidam, or the one built by --config; --language on top
     *
     * @param class-string<ProviderPackInterface>|null $language
     */
    private function generator(?string $config, ?string $language, ?int $seed = null, ?ClockInterface $clock = null): Quidam
    {
        $clock ??= new SystemClock();
        $generator = $config !== null ? $this->configured($config, $seed, $clock) : Quidam::create($seed, $clock);

        return $language !== null ? $generator->withProvider(new $language()) : $generator;
    }

    /**
     * Generator of the --config file - the file returns a factory, so --seed and --now still apply
     */
    private function configured(string $config, ?int $seed, ClockInterface $clock): Quidam
    {
        // own scope - the file sees no CLI internals
        $factory = (static fn (string $file): mixed => require $file)($config);

        if (!$factory instanceof Closure) {
            throw new InvalidArgumentException(sprintf(
                'Config "%s" must return a Closure(?int $seed, ClockInterface $clock): Quidam, got %s',
                $config,
                get_debug_type($factory),
            ));
        }

        $generator = $factory($seed, $clock);

        if (!$generator instanceof Quidam) {
            throw new InvalidArgumentException(sprintf('Config "%s" factory must return Quidam, got %s', $config, get_debug_type($generator)));
        }

        return $generator;
    }

    /**
     * Provider pack class of --language: a code of a pack in Quidam\LanguagePack or Quidam\DeadLanguagePack
     * (pl_PL -> LanguagePack\pl_PL\PlPlProviderPack, la_VA -> DeadLanguagePack\la_VA\LaVaProviderPack) or a class name
     *
     * @return class-string<ProviderPackInterface>
     */
    private function providerPack(string $language): string
    {
        $class = ltrim($language, '\\');

        if (preg_match('/^([a-z]{2,3})_([A-Z]{2})$/', $language, $match) === 1) {
            foreach (self::PACK_DIRECTORIES as $directory) {
                $class = sprintf('Quidam\\%s\\%s\\%s%sProviderPack', $directory, $language, ucfirst($match[1]), ucfirst(strtolower($match[2])));

                if (class_exists($class)) {
                    break;
                }
            }
        }

        if (!class_exists($class) || !is_subclass_of($class, ProviderPackInterface::class)) {
            throw new InvalidArgumentException(sprintf(
                'Unknown language "%s" - use a pack code (%s) or a class implementing %s',
                $language,
                implode(', ', self::languages()),
                ProviderPackInterface::class,
            ));
        }

        return $class;
    }

    /**
     * Codes of packs in Quidam\LanguagePack and Quidam\DeadLanguagePack
     *
     * @return list<string>
     */
    private static function languages(): array
    {
        $directories = [];
        foreach (self::PACK_DIRECTORIES as $directory) {
            $directories = [...$directories, ...(glob(dirname(__DIR__) . '/' . $directory . '/*', GLOB_ONLYDIR) ?: [])];
        }

        $codes = array_filter(array_map(basename(...), $directories), static fn (string $code): bool => preg_match('/^[a-z]{2,3}_[A-Z]{2}$/', $code) === 1);
        sort($codes);

        return $codes;
    }

    private function date(string $date, DateTimeZone $timezone): DateTimeImmutable
    {
        try {
            return new DateTimeImmutable($date, $timezone)->setTimezone($timezone);
        } catch (\DateMalformedStringException $e) {
            throw new InvalidArgumentException(sprintf('Invalid --now date "%s"', $date), 0, $e);
        }
    }

    /** @param list<mixed> $values */
    private function format(array $values, string $format, int $count): string
    {
        $values = array_map($this->plain(...), $values);

        return match ($format) {
            'json' => json_encode($count === 1 ? $values[0] : $values, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) . "\n",
            'csv' => $this->csv($values),
            default => implode('', array_map(
                static fn (mixed $value): string => (is_array($value) ? json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) : self::scalar($value)) . "\n",
                $values,
            )),
        };
    }

    /**
     * Value as JSON-friendly data - dates as ISO 8601, enums as value / name, objects as public properties
     */
    private function plain(mixed $value): mixed
    {
        return match (true) {
            $value instanceof DateTimeInterface => $value->format(DateTimeInterface::ATOM),
            $value instanceof BackedEnum => $value->value,
            $value instanceof UnitEnum => $value->name,
            is_array($value) => array_map($this->plain(...), $value),
            is_object($value) => array_map($this->plain(...), get_object_vars($value)),
            default => $value,
        };
    }

    /** @param list<mixed> $values */
    private function csv(array $values): string
    {
        $stream = fopen('php://memory', 'w+');

        if ($stream === false) {
            throw new RuntimeException('Cannot open memory stream');
        }

        $first = $values[0] ?? null;
        $header = is_array($first) ? array_map(strval(...), array_keys($first)) : ['value'];
        fputcsv($stream, $header, ',', '"', '');

        foreach ($values as $value) {
            $row = is_array($value) ? $value : [$value];

            if (count($row) !== count($header) || (is_array($value) && array_keys($value) !== array_keys((array) $first))) {
                throw new InvalidArgumentException('CSV needs values of the same shape - use --format json');
            }

            fputcsv($stream, array_map(
                static fn (mixed $cell): string => is_array($cell) ? (string) json_encode($cell, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : self::scalar($cell),
                array_values($row),
            ), ',', '"', '');
        }

        rewind($stream);
        $csv = (string) stream_get_contents($stream);
        fclose($stream);

        return $csv;
    }

    private static function scalar(mixed $value): string
    {
        return match (true) {
            $value === null => '',
            is_bool($value) => $value ? 'true' : 'false',
            is_scalar($value) => (string) $value,
            default => get_debug_type($value),
        };
    }

    /** @param class-string<ProviderPackInterface>|null $language */
    private function list(string $alias, ?string $config, ?string $language): string
    {
        $generator = $this->generator($config, $language);
        $aliases = $generator->ext(TemplateInterface::class)->aliases();

        if ($alias === '') {
            $lines = array_map(
                static fn (string $name, string $id): string => sprintf("  %-12s %s\n", $name, $id),
                array_keys($aliases),
                $aliases,
            );

            return "Aliases (quidam --list <alias> for methods):\n" . implode('', $lines)
                . sprintf("\nLanguages (-l <code>): %s\n", implode(', ', self::languages()));
        }

        $id = $aliases[$alias] ?? throw new InvalidArgumentException(sprintf(
            'Unknown alias "%s". Available: %s',
            $alias,
            implode(', ', array_keys($aliases)),
        ));

        // methods of the class actually used - a language pack can add its own
        $lines = [];
        $entry = $generator->container()->get($id);

        if (!is_object($entry)) {
            throw new InvalidArgumentException(sprintf('Alias "%s" doesn\'t resolve to an object', $alias));
        }

        foreach (new ReflectionClass($entry)->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if (!$method->isConstructor() && !$method->isStatic()) {
                $lines[] = sprintf("  %s.%s(%s)\n", $alias, $method->getName(), implode(', ', array_map($this->describe(...), $method->getParameters())));
            }
        }

        return implode('', $lines);
    }

    private function describe(ReflectionParameter $parameter): string
    {
        $description = ($parameter->hasType() ? $parameter->getType() . ' ' : '') . '$' . $parameter->getName();

        if ($parameter->isDefaultValueAvailable()) {
            $default = $parameter->getDefaultValue();
            $description .= ' = ' . match (true) {
                $default === null => 'null',
                is_array($default) => (string) json_encode($default),
                default => var_export($default, true),
            };
        }

        return $description;
    }

    /**
     * @param list<string> $arguments
     * @return array{expression: ?string, template: ?string, count: int, seed: ?int, unique: bool, format: string, now: ?string, timezone: ?string, list: ?string, language: class-string<ProviderPackInterface>|null, config: ?string, help: bool}
     */
    private function parse(array $arguments): array
    {
        $options = [
            'expression' => null, 'template' => null, 'count' => 1, 'seed' => null, 'unique' => false, 'format' => 'text',
            'now' => null, 'timezone' => null, 'list' => null, 'language' => null, 'config' => null, 'help' => false,
        ];

        while ($arguments !== []) {
            $argument = array_shift($arguments);

            // --name=value and -n5 forms
            $value = null;
            if (preg_match('/^(--[a-z]+)=(.*)$/s', $argument, $match) === 1 || preg_match('/^(-[nsftlc])(.+)$/s', $argument, $match) === 1) {
                [, $argument, $value] = $match;
            }

            $next = static function () use (&$arguments, &$value, $argument): string {
                $result = $value ?? array_shift($arguments) ?? throw new InvalidArgumentException(sprintf('Option %s needs a value', $argument));
                $value = null;

                return $result;
            };

            switch ($argument) {
                case '-h':
                case '--help':
                    $options['help'] = true;
                    break;

                case '-n':
                case '--count':
                    $options['count'] = $this->integer($next(), $argument, 1);
                    break;

                case '-s':
                case '--seed':
                    $options['seed'] = $this->integer($next(), $argument);
                    break;

                case '-u':
                case '--unique':
                    $options['unique'] = true;
                    break;

                case '-t':
                case '--template':
                    $options['template'] = $next();
                    break;

                case '-f':
                case '--format':
                    $options['format'] = in_array($format = $next(), self::FORMATS, true)
                        ? $format
                        :
                    throw new InvalidArgumentException(sprintf('Unknown format "%s", use: %s', $format, implode(', ', self::FORMATS)));
                    break;

                case '-l':
                case '--language':
                    $options['language'] = $this->providerPack($next());
                    break;

                case '-c':
                case '--config':
                    $file = $next();
                    $options['config'] = is_file($file) && is_readable($file)
                        ? (string) realpath($file)
                        :
                    throw new InvalidArgumentException(sprintf('Config file "%s" not found or not readable', $file));
                    break;

                case '--now':
                    $options['now'] = $next();
                    break;

                case '--timezone':
                    $options['timezone'] = $next();
                    break;

                case '--list':
                    $options['list'] = $value ?? (isset($arguments[0]) && !str_starts_with($arguments[0], '-') ? array_shift($arguments) : '');
                    $value = null;
                    break;

                default:
                    if (str_starts_with($argument, '-') && !is_numeric($argument)) {
                        throw new InvalidArgumentException(sprintf('Unknown option "%s"', $argument));
                    }

                    if ($options['expression'] !== null) {
                        throw new InvalidArgumentException(sprintf('Only one expression allowed, got "%s" and "%s" - quote expressions with spaces', $options['expression'], $argument));
                    }

                    $options['expression'] = $argument;
            }

            if ($value !== null) {
                throw new InvalidArgumentException(sprintf('Option %s doesn\'t take a value', $argument));
            }
        }

        if (!$options['help'] && $options['list'] === null && ($options['expression'] === null) === ($options['template'] === null)) {
            throw new InvalidArgumentException('Give one expression (i.e. person.name) or a template (-t)');
        }

        if ($options['timezone'] !== null) {
            try {
                new DateTimeZone($options['timezone']);
            } catch (\Throwable $e) {
                throw new InvalidArgumentException(sprintf('Unknown timezone "%s"', $options['timezone']), 0, $e);
            }
        }

        return $options;
    }

    private function integer(string $value, string $option, int $min = PHP_INT_MIN): int
    {
        if (preg_match('/^-?\d+$/', $value) !== 1 || (int) $value < $min) {
            throw new InvalidArgumentException(sprintf('Option %s needs an integer%s, "%s" given', $option, $min > PHP_INT_MIN ? ' >= ' . $min : '', $value));
        }

        return (int) $value;
    }
}
