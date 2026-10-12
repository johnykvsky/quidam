<?php

declare(strict_types = 1);

namespace Quidam\Addon;

use BackedEnum;
use DateTimeInterface;
use Psr\Container\ContainerInterface;
use Quidam\Container\QuidamContainerInterface;
use Quidam\Definitions\Addon\EdgeCasesInterface;
use Quidam\Definitions\Addon\RichTextInterface;
use Quidam\Definitions\Addon\TemplateInterface;
use Quidam\Definitions\Addon\TextInterface;
use Quidam\Definitions\Extension\AddressExtensionInterface;
use Quidam\Definitions\Extension\BarcodeExtensionInterface;
use Quidam\Definitions\Extension\BloodExtensionInterface;
use Quidam\Definitions\Extension\ColorExtensionInterface;
use Quidam\Definitions\Extension\CommerceExtensionInterface;
use Quidam\Definitions\Extension\CompanyExtensionInterface;
use Quidam\Definitions\Extension\DateTimeExtensionInterface;
use Quidam\Definitions\Extension\EnumExtensionInterface;
use Quidam\Definitions\Extension\FileExtensionInterface;
use Quidam\Definitions\Extension\HashExtensionInterface;
use Quidam\Definitions\Extension\InternetExtensionInterface;
use Quidam\Definitions\Extension\LanguageExtensionInterface;
use Quidam\Definitions\Extension\LoremExtensionInterface;
use Quidam\Definitions\Extension\NumberExtensionInterface;
use Quidam\Definitions\Extension\PaymentExtensionInterface;
use Quidam\Definitions\Extension\PersonExtensionInterface;
use Quidam\Definitions\Extension\PhoneNumberExtensionInterface;
use Quidam\Definitions\Extension\ProfileExtensionInterface;
use Quidam\Definitions\Extension\StringsExtensionInterface;
use Quidam\Definitions\Extension\SystemExtensionInterface;
use Quidam\Definitions\Extension\UserAgentExtensionInterface;
use Quidam\Definitions\Extension\UuidExtensionInterface;
use Quidam\Definitions\Extension\VehicleExtensionInterface;
use Quidam\Definitions\Extension\VersionExtensionInterface;
use Quidam\Definitions\Randomizer\RandomizerInterface;
use Quidam\Exception\InvalidArgumentException;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use Stringable;
use UnitEnum;

/**
 * Replaces {{ alias.method }} / {{ alias.method(args) }} tokens with generated values, i.e.:
 *   {{ person.firstName }} <{{ internet.email }}> born {{ dateTime.date('d.m.Y') }}
 *
 * Alias is resolved to container entry by the aliases map - only generators from the map can be used. Default map is
 * GENERATORS plus aliases registered in the container under Template::alias() ids - packs and your code add their
 * own without replacing the Template:
 *   $generator->withDefinition(Template::alias('car'), Car::class)   // {{ car.brand }}
 * Arguments can be: quoted strings ('a' or "a", backslash escapes), integers, floats, true, false, null,
 * positional or named (min: 1).
 */
class Template implements TemplateInterface
{
    /** Default aliases - container ids of built-in extensions, the randomizer and text add-ons */
    public const array GENERATORS = [
        'address' => AddressExtensionInterface::class,
        'barcode' => BarcodeExtensionInterface::class,
        'blood' => BloodExtensionInterface::class,
        'color' => ColorExtensionInterface::class,
        'commerce' => CommerceExtensionInterface::class,
        'company' => CompanyExtensionInterface::class,
        'dateTime' => DateTimeExtensionInterface::class,
        'enum' => EnumExtensionInterface::class,
        'file' => FileExtensionInterface::class,
        'hash' => HashExtensionInterface::class,
        'internet' => InternetExtensionInterface::class,
        'language' => LanguageExtensionInterface::class,
        'lorem' => LoremExtensionInterface::class,
        'number' => NumberExtensionInterface::class,
        'payment' => PaymentExtensionInterface::class,
        'person' => PersonExtensionInterface::class,
        'phoneNumber' => PhoneNumberExtensionInterface::class,
        'profile' => ProfileExtensionInterface::class,
        'random' => RandomizerInterface::class,
        'strings' => StringsExtensionInterface::class,
        'system' => SystemExtensionInterface::class,
        'userAgent' => UserAgentExtensionInterface::class,
        'uuid' => UuidExtensionInterface::class,
        'vehicle' => VehicleExtensionInterface::class,
        'version' => VersionExtensionInterface::class,
        'text' => TextInterface::class,
        'richText' => RichTextInterface::class,
        'edgeCases' => EdgeCasesInterface::class,
    ];

    /** {{ alias.method }} with optional (args), quoted args may contain parentheses */
    private const string TOKEN_PATTERN = <<<'REGEX'
        /\{\{\s*([A-Za-z_]\w*)\.([A-Za-z_]\w*)\s*(?:\(((?:[^()'"]|'(?:[^'\\]|\\.)*'|"(?:[^"\\]|\\.)*")*)\))?\s*\}\}/
        REGEX;

    /** Single alias.method / alias.method(args) expression, without braces */
    private const string EXPRESSION_PATTERN = <<<'REGEX'
        /^\s*([A-Za-z_]\w*)\.([A-Za-z_]\w*)\s*(?:\(((?:[^()'"]|'(?:[^'\\]|\\.)*'|"(?:[^"\\]|\\.)*")*)\))?\s*$/s
        REGEX;

    /** Prefix of container ids of registered aliases - see alias() */
    public const string ALIAS_PREFIX = 'template.';

    /** @var array<string, string> alias => container id */
    protected readonly array $generators;

    /**
     * @param array<string, string>|null $generators alias => container id (class or interface name); null - GENERATORS
     *                                               and aliases registered in the container, a map - exactly these
     *                                               (i.e. a whitelist for untrusted templates)
     */
    public function __construct(
        protected readonly ContainerInterface $container,
        ?array $generators = null,
    ) {
        $this->generators = $generators ?? [...self::GENERATORS, ...$this->registeredAliases()];
    }

    /**
     * Container id of an alias - register a generator under it to use it in templates:
     *   withDefinition(Template::alias('car'), Car::class) -> {{ car.brand }}
     */
    public static function alias(string $alias): string
    {
        if (preg_match('/^[a-zA-Z_]\w*$/', $alias) !== 1) {
            throw new InvalidArgumentException(sprintf('Invalid template alias "%s" - use letters, digits and _', $alias));
        }

        return self::ALIAS_PREFIX . $alias;
    }

    public function parse(string $template): string
    {
        return (string) preg_replace_callback(
            self::TOKEN_PATTERN,
            fn (array $match): string => $this->toString(
                $this->call($match[1], $match[2], $this->parseArguments($match[3] ?? '')),
                $match[0],
            ),
            $template,
        );
    }

    /**
     * Result of one expression as returned by the generator (not converted to string), i.e.
     *   evaluate('number.numberBetween(1, 10)') → 7, evaluate('profile.person') → array
     */
    public function evaluate(string $expression): mixed
    {
        if (preg_match(self::EXPRESSION_PATTERN, $expression, $match) !== 1) {
            throw new InvalidArgumentException(sprintf('Invalid expression "%s", expected alias.method or alias.method(arguments)', $expression));
        }

        return $this->call($match[1], $match[2], $this->parseArguments($match[3] ?? ''));
    }

    public function aliases(): array
    {
        return $this->generators;
    }

    /**
     * Aliases registered under alias() ids - alias => their target id (or the alias id for factories and objects)
     *
     * @return array<string, string>
     */
    protected function registeredAliases(): array
    {
        if (!$this->container instanceof QuidamContainerInterface) {
            return [];
        }

        $aliases = [];
        foreach ($this->container->definitions() as $id => $definition) {
            if (str_starts_with($id, self::ALIAS_PREFIX)) {
                $aliases[substr($id, strlen(self::ALIAS_PREFIX))] = is_string($definition) ? $definition : $id;
            }
        }

        return $aliases;
    }

    /** @param array<int|string, mixed> $arguments */
    protected function call(string $alias, string $method, array $arguments): mixed
    {
        if (!isset($this->generators[$alias])) {
            throw new InvalidArgumentException(sprintf(
                'Unknown template generator "%s". Available: %s',
                $alias,
                implode(', ', array_keys($this->generators)),
            ));
        }

        $generator = $this->container->get($this->generators[$alias]);

        if (!is_object($generator) || !$this->isCallable($generator, $method)) {
            throw new InvalidArgumentException(sprintf('Unknown template method "%s.%s"', $alias, $method));
        }

        $reflection = new ReflectionMethod($generator, $method);
        $this->validateArgumentNames($reflection, $arguments, $alias);

        try {
            return $generator->{$method}(...$this->enumArguments($reflection, $arguments));
        } catch (\TypeError $e) {
            // wrong type or count of the template's arguments - PHP reports this file as the caller; errors from
            // inside the generator are bugs, not template mistakes, and are passed on
            if (!str_contains($e->getMessage(), ' in ' . __FILE__ . ' on line ')) {
                throw $e;
            }

            throw new InvalidArgumentException(sprintf('Cannot call "%s.%s": %s', $alias, $method, $e->getMessage()), 0, $e);
        }
    }

    /**
     * Named arguments must be parameters of the method (unless it's variadic)
     *
     * @param array<int|string, mixed> $arguments
     */
    protected function validateArgumentNames(ReflectionMethod $method, array $arguments, string $alias): void
    {
        $parameters = $method->getParameters();

        foreach ($parameters as $parameter) {
            if ($parameter->isVariadic()) {
                return;
            }
        }

        $names = array_map(static fn (ReflectionParameter $parameter): string => $parameter->getName(), $parameters);

        foreach (array_keys($arguments) as $name) {
            if (is_string($name) && !in_array($name, $names, true)) {
                throw new InvalidArgumentException(sprintf(
                    'Cannot call "%s.%s": unknown argument "%s", expected: %s',
                    $alias,
                    $method->getName(),
                    $name,
                    $names === [] ? 'none' : implode(', ', $names),
                ));
            }
        }
    }

    /**
     * Strings and ints given for backed enum parameters become cases: person.name("female") -> Gender::Female
     *
     * @param array<int|string, mixed> $arguments
     * @return array<int|string, mixed>
     */
    protected function enumArguments(ReflectionMethod $method, array $arguments): array
    {
        foreach ($method->getParameters() as $position => $parameter) {
            $key = array_key_exists($parameter->getName(), $arguments) ? $parameter->getName() : $position;
            $type = $parameter->getType();

            if (!array_key_exists($key, $arguments) || !$type instanceof ReflectionNamedType || !is_subclass_of($type->getName(), BackedEnum::class)) {
                continue;
            }

            $value = $arguments[$key];
            if (is_string($value) || is_int($value)) {
                $enum = $type->getName();
                $arguments[$key] = $enum::tryFrom($value) ?? throw new InvalidArgumentException(sprintf(
                    'Invalid value "%s" for $%s of %s(), expected: %s',
                    $value,
                    $parameter->getName(),
                    $method->getName(),
                    implode(', ', array_map(static fn (BackedEnum $case): string => (string) $case->value, $enum::cases())),
                ));
            }
        }

        return $arguments;
    }

    protected function isCallable(object $generator, string $method): bool
    {
        if (!method_exists($generator, $method) || str_starts_with($method, '__')) {
            return false;
        }

        $reflection = new ReflectionMethod($generator, $method);

        return $reflection->isPublic() && !$reflection->isStatic();
    }

    /** @return array<int|string, mixed> */
    protected function parseArguments(string $arguments): array
    {
        $parsed = [];

        foreach ($this->splitArguments($arguments) as $argument) {
            if (preg_match('/^([A-Za-z_]\w*)\s*:\s*(.+)$/s', $argument, $named) === 1) {
                $parsed[$named[1]] = $this->parseValue($named[2]);
                continue;
            }

            if (array_filter(array_keys($parsed), is_string(...)) !== []) {
                throw new InvalidArgumentException(sprintf('Positional argument "%s" cannot follow named argument', $argument));
            }

            $parsed[] = $this->parseValue($argument);
        }

        return $parsed;
    }

    /**
     * Splits by commas outside of quotes
     *
     * @return list<string>
     */
    protected function splitArguments(string $arguments): array
    {
        if (trim($arguments) === '') {
            return [];
        }

        $result = [];
        $current = '';
        $quote = null;
        $length = strlen($arguments);

        for ($i = 0; $i < $length; ++$i) {
            $char = $arguments[$i];

            if ($quote !== null && $char === '\\' && $i + 1 < $length) {
                $current .= $char . $arguments[++$i];
                continue;
            }

            if ($char === '"' || $char === "'") {
                $quote = match ($quote) {
                    null => $char,
                    $char => null,
                    default => $quote,
                };
            }

            if ($char === ',' && $quote === null) {
                $result[] = trim($current);
                $current = '';
                continue;
            }

            $current .= $char;
        }

        $result[] = trim($current);

        if (in_array('', $result, true)) {
            throw new InvalidArgumentException(sprintf('Empty argument in "(%s)"', $arguments));
        }

        return $result;
    }

    protected function parseValue(string $value): mixed
    {
        $value = trim($value);

        if (preg_match('/^([\'"])(.*)\1$/s', $value, $quoted) === 1) {
            return preg_replace('/\\\\(.)/s', '$1', $quoted[2]);
        }

        return match (true) {
            $value === 'true' => true,
            $value === 'false' => false,
            $value === 'null' => null,
            preg_match('/^-?\d+$/', $value) === 1 => (int) $value,
            is_numeric($value) => (float) $value,
            default => throw new InvalidArgumentException(sprintf(
                'Unsupported template argument "%s" - use quoted string, number, true, false or null',
                $value,
            )),
        };
    }

    protected function toString(mixed $result, string $token): string
    {
        return match (true) {
            $result === null => '',
            is_bool($result) => $result ? 'true' : 'false',
            is_scalar($result), $result instanceof Stringable => (string) $result,
            $result instanceof DateTimeInterface => $result->format('Y-m-d H:i:s'),
            $result instanceof BackedEnum => (string) $result->value,
            $result instanceof UnitEnum => $result->name,
            is_array($result) => implode(', ', array_map(fn (mixed $item): string => $this->toString($item, $token), $result)),
            default => throw new InvalidArgumentException(sprintf('Result of %s cannot be converted to string', $token)),
        };
    }
}
