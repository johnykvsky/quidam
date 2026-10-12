<?php

declare(strict_types = 1);

namespace Quidam\Addon;

use BackedEnum;
use Closure;
use DateTime as PhpDateTime;
use DateTimeImmutable;
use DateTimeInterface;
use Quidam\Definitions\Addon\FillerInterface;
use Quidam\Definitions\Addon\TemplateInterface;
use Quidam\Definitions\Extension\DateTimeExtensionInterface;
use Quidam\Definitions\Extension\EnumExtensionInterface;
use Quidam\Definitions\Extension\LoremExtensionInterface;
use Quidam\Definitions\Randomizer\RandomizerInterface;
use Quidam\Exception\InvalidArgumentException;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionProperty;
use ReflectionType;
use ReflectionUnionType;

/**
 * Creates objects and arrays filled with dummy data guessed from names and types:
 *   $filler->make(UserDto::class)                   // constructor parameters and public properties
 *   $filler->array(['id', 'email', 'createdAt'])   // keys guessed by name
 *
 * Value of every parameter / property, first match wins:
 * 1. override given to make() / array() - value, or Closure called for every object
 * 2. name rule - NAME_RULES (whole name), WORD_RULES (last word, i.e. "createdAt" → "at"), BOOLEAN_PREFIXES
 *    ("isActive") - rules are Template expressions, i.e. 'internet.email', or "@key" / "@address.city" of one
 *    Profile::person() shared by the whole object, so name, email, age, phone and address parts belong together
 * 3. default value of the parameter / property, when it has one
 * 4. type - int, float, bool, string, DateTime*, enum, other class (filled recursively), array (empty)
 *
 * Extend the class and override the rule constants to add or change rules.
 */
class Filler implements FillerInterface
{
    /**
     * Normalized name (lowercase, without "_" and "-") => Template expression, or "@key" of the object's profile
     *
     * @var array<string, string>
     */
    protected const array NAME_RULES = [
        // person
        'name' => '@name', 'fullname' => '@name', 'firstname' => '@firstName', 'givenname' => '@firstName',
        'lastname' => '@lastName', 'surname' => '@lastName', 'familyname' => '@lastName', 'middlename' => 'person.middleName',
        'gender' => '@gender', 'sex' => '@gender', 'salutation' => '@title', 'honorific' => '@title',
        'birthdate' => '@dateOfBirth', 'dateofbirth' => '@dateOfBirth', 'dob' => '@dateOfBirth', 'birthday' => '@dateOfBirth',
        'age' => '@age',
        // contact
        'email' => '@email', 'emailaddress' => '@email', 'mail' => '@email',
        'username' => '@userName', 'login' => '@userName', 'nickname' => '@userName',
        'password' => 'internet.password', 'phone' => '@phone', 'phonenumber' => '@phone', 'telephone' => '@phone',
        'mobile' => '@phone', 'tel' => '@phone', 'landline' => 'phoneNumber.phoneNumber',
        // address - parts of the profile's address belong together (city, region, postcode, coordinates)
        'address' => '@address.address', 'street' => 'address.streetName', 'streetname' => 'address.streetName',
        'streetaddress' => '@address.street', 'address1' => '@address.street', 'addressline1' => '@address.street',
        'address2' => 'address.secondaryAddress', 'addressline2' => 'address.secondaryAddress',
        'buildingnumber' => 'address.buildingNumber', 'housenumber' => 'address.buildingNumber',
        'city' => '@address.city', 'town' => '@address.city', 'postcode' => '@address.postcode',
        'postalcode' => '@address.postcode', 'zip' => '@address.postcode', 'zipcode' => '@address.postcode',
        'state' => '@address.state', 'province' => '@address.state', 'region' => '@address.state',
        'statecode' => '@address.stateCode', 'county' => '@address.county', 'district' => '@address.county',
        'country' => 'address.country', 'countrycode' => 'address.countryCode',
        'latitude' => '@address.latitude', 'lat' => '@address.latitude', 'longitude' => '@address.longitude',
        'lng' => '@address.longitude', 'lon' => '@address.longitude',
        // company
        'company' => 'company.company', 'companyname' => 'company.company', 'organization' => 'company.company',
        'jobtitle' => 'company.jobTitle', 'position' => 'company.jobTitle', 'industry' => 'company.industry',
        // internet
        'url' => 'internet.url', 'website' => 'internet.url', 'homepage' => 'internet.url', 'link' => 'internet.url',
        'domain' => 'internet.domainName', 'hostname' => 'internet.hostname', 'host' => 'internet.hostname',
        'ip' => 'internet.ipv4', 'ipaddress' => 'internet.ipv4', 'ipv4' => 'internet.ipv4', 'ipv6' => 'internet.ipv6',
        'macaddress' => 'internet.macAddress', 'port' => 'internet.port', 'slug' => 'internet.slug',
        'useragent' => 'userAgent.userAgent',
        // text
        'title' => 'lorem.sentence(4)', 'subject' => 'lorem.sentence(4)', 'headline' => 'lorem.sentence(4)',
        'subtitle' => 'lorem.sentence', 'excerpt' => 'lorem.sentence', 'caption' => 'lorem.sentence',
        'summary' => 'lorem.paragraph(2)', 'description' => 'lorem.paragraph', 'body' => 'lorem.paragraph(5)',
        'content' => 'lorem.paragraph(5)', 'text' => 'lorem.paragraph', 'bio' => 'lorem.paragraph(2)',
        'about' => 'lorem.paragraph(2)', 'comment' => 'lorem.sentence(10)', 'message' => 'lorem.sentence(10)',
        'note' => 'lorem.sentence(8)', 'notes' => 'lorem.paragraph(2)', 'tag' => 'lorem.word', 'keyword' => 'lorem.word',
        'label' => 'lorem.word',
        // money and commerce
        'price' => 'payment.price(1, 1000)', 'amount' => 'payment.price(1, 1000)', 'total' => 'payment.price(1, 5000)',
        'subtotal' => 'payment.price(1, 5000)', 'cost' => 'payment.price(1, 1000)', 'currency' => 'payment.currencyCode',
        'currencycode' => 'payment.currencyCode', 'iban' => 'payment.iban', 'bic' => 'payment.swiftBicNumber',
        'swift' => 'payment.swiftBicNumber', 'cardnumber' => 'payment.creditCardNumber',
        'creditcardnumber' => 'payment.creditCardNumber', 'cvv' => 'payment.creditCardCvv',
        'sku' => 'commerce.sku', 'product' => 'commerce.productName', 'productname' => 'commerce.productName',
        'category' => 'commerce.department', 'department' => 'commerce.department', 'quantity' => 'number.numberBetween(1, 10)',
        'qty' => 'number.numberBetween(1, 10)', 'stock' => 'number.numberBetween(0, 500)', 'rating' => 'number.numberBetween(1, 5)',
        'discount' => 'number.numberBetween(0, 50)', 'percentage' => 'number.randomFloat(1, 0, 100)',
        // identifiers and codes
        'id' => 'uuid.uuid4', 'uuid' => 'uuid.uuid4', 'guid' => 'uuid.uuid4', 'ulid' => 'uuid.ulid', 'ean' => 'barcode.ean13',
        'barcode' => 'barcode.ean13', 'isbn' => 'barcode.isbn13', 'vin' => 'vehicle.vin', 'imei' => 'phoneNumber.imei',
        'token' => 'hash.base64Url', 'apikey' => 'hash.apiKey', 'secret' => 'hash.base64Url', 'hash' => 'hash.sha256',
        'checksum' => 'hash.sha256', 'version' => 'version.semver',
        // other
        'color' => 'color.hexColor', 'colour' => 'color.hexColor', 'timezone' => 'dateTime.timezone',
        'locale' => 'language.locale', 'language' => 'language.languageCode', 'year' => 'number.numberBetween(1990, 2030)',
        'filename' => 'file.fileName', 'filepath' => 'file.filePath', 'path' => 'file.filePath', 'extension' => 'file.extension',
        'mimetype' => 'file.mimeType', 'filesize' => 'file.fileSize',
        'loglevel' => 'system.logLevel',
        // booleans
        'active' => 'number.boolean', 'enabled' => 'number.boolean', 'verified' => 'number.boolean',
        'visible' => 'number.boolean', 'published' => 'number.boolean', 'deleted' => 'number.boolean',
        'archived' => 'number.boolean', 'admin' => 'number.boolean',
    ];

    /**
     * Last word of the name (lowercase) => Template expression, i.e. "createdAt", "published_at" → "at"
     *
     * @var array<string, string>
     */
    protected const array WORD_RULES = [
        'at' => "dateTime.dateTimeBetween('-2 years', 'now')", 'date' => "dateTime.dateTimeBetween('-2 years', 'now')",
        'time' => "dateTime.dateTimeBetween('-2 years', 'now')", 'on' => "dateTime.dateTimeBetween('-2 years', 'now')",
        'id' => 'uuid.uuid4', 'uuid' => 'uuid.uuid4', 'email' => 'internet.email', 'url' => 'internet.url',
        'phone' => 'phoneNumber.phoneNumber', 'city' => 'address.city', 'country' => 'address.country',
        'price' => 'payment.price(1, 1000)', 'amount' => 'payment.price(1, 1000)', 'count' => 'number.numberBetween(0, 100)',
        'description' => 'lorem.paragraph', 'title' => 'lorem.sentence(4)', 'ip' => 'internet.ipv4', 'color' => 'color.hexColor',
    ];

    /** @var list<string> first word of the name meaning a boolean, i.e. "isActive", "has_children" */
    protected const array BOOLEAN_PREFIXES = ['is', 'has', 'can', 'should', 'was', 'allow', 'enable', 'enabled', 'show'];

    /** Nested objects are filled up to this depth - deeper ones use default / null, or throw */
    protected const int MAX_DEPTH = 3;

    private int|float $nullChance = 0;

    /** @var array<mixed>|null profile()->person() shared by the object being filled, created on first use */
    private ?array $profile = null;

    public function __construct(
        protected readonly TemplateInterface $template,
        protected readonly RandomizerInterface $randomizer,
        protected readonly LoremExtensionInterface $lorem,
        protected readonly DateTimeExtensionInterface $dateTime,
        protected readonly EnumExtensionInterface $enum,
    ) {
    }

    /**
     * Copy which leaves nullable values null with given chance (int as percent, float as probability) - by default
     * nullable values are always filled
     */
    public function withNullChance(int|float $chance): static
    {
        // validated here, not by the randomizer - a validation call would consume a random value
        if (is_int($chance) ? $chance < 0 || $chance > 100 : $chance < 0.0 || $chance > 1.0) {
            throw new InvalidArgumentException('Null chance must be 0-100 (int, percent) or 0.0-1.0 (float, probability)');
        }

        $filler = clone $this;
        $filler->nullChance = $chance;

        return $filler;
    }

    /**
     * Object of given class - constructor parameters and uninitialized public properties filled
     *
     * @param class-string<T> $class
     * @param array<string, mixed> $overrides name => value, or Closure returning the value
     * @return T
     *
     * @template T of object
     */
    public function make(string $class, array $overrides = []): object
    {
        return $this->build($class, $overrides, 0);
    }

    /**
     * Array with given keys, values guessed by key name (lorem word when no rule matches), i.e. for API payloads:
     *   array(['id', 'email', 'firstName', 'createdAt'])
     *
     * @param list<string> $keys
     * @param array<string, mixed> $overrides key => value, or Closure returning the value
     * @return array<string, mixed>
     */
    public function array(array $keys, array $overrides = []): array
    {
        $this->validateOverrides($overrides, $keys, 'array');
        $previousProfile = $this->profile;
        $this->profile = null;

        try {
            $values = [];
            foreach ($keys as $key) {
                $values[$key] = array_key_exists($key, $overrides)
                    ? $this->override($overrides[$key])
                    : $this->byName($key, null) ?? $this->lorem->word();
            }

            return $values;
        } finally {
            $this->profile = $previousProfile;
        }
    }

    /**
     * @param class-string<T> $class
     * @param array<string, mixed> $overrides
     * @return T
     *
     * @template T of object
     */
    protected function build(string $class, array $overrides, int $depth): object
    {
        $reflection = new ReflectionClass($class);

        if (!$reflection->isInstantiable()) {
            throw new InvalidArgumentException(sprintf('Cannot fill "%s" - it is not an instantiable class', $class));
        }

        $constructor = $reflection->getConstructor();
        $parameters = $constructor?->getParameters() ?? [];
        $properties = array_filter(
            $reflection->getProperties(ReflectionProperty::IS_PUBLIC),
            static fn (ReflectionProperty $property): bool => !$property->isStatic() && !$property->isReadOnly() && !$property->isPromoted() && $property->hasType() && !$property->hasDefaultValue(),
        );

        $this->validateOverrides($overrides, [
            ...array_map(static fn (ReflectionParameter $parameter): string => $parameter->getName(), $parameters),
            ...array_map(static fn (ReflectionProperty $property): string => $property->getName(), $properties),
        ], $class);

        // every object gets own profile - nested objects too, restored after them
        $previousProfile = $this->profile;
        $this->profile = null;

        try {
            return $this->fill($reflection, $parameters, $properties, $overrides, $depth);
        } finally {
            $this->profile = $previousProfile;
        }
    }

    /**
     * @param ReflectionClass<T> $reflection
     * @param list<ReflectionParameter> $parameters
     * @param array<ReflectionProperty> $properties
     * @param array<string, mixed> $overrides
     * @return T
     *
     * @template T of object
     */
    protected function fill(ReflectionClass $reflection, array $parameters, array $properties, array $overrides, int $depth): object
    {
        $class = $reflection->getName();
        $arguments = [];
        foreach ($parameters as $parameter) {
            if ($parameter->isVariadic()) {
                break;
            }

            $arguments[] = array_key_exists($parameter->getName(), $overrides)
                ? $this->override($overrides[$parameter->getName()])
                : $this->value(
                    $parameter->getName(),
                    $parameter->getType(),
                    $parameter->isDefaultValueAvailable(),
                    $parameter->isDefaultValueAvailable() ? $parameter->getDefaultValue() : null,
                    $depth,
                    sprintf('%s::__construct() parameter $%s', $class, $parameter->getName()),
                );
        }

        $object = $reflection->newInstanceArgs($arguments);

        foreach ($properties as $property) {
            if ($property->isInitialized($object)) {
                continue;
            }

            $property->setValue($object, array_key_exists($property->getName(), $overrides)
                ? $this->override($overrides[$property->getName()])
                : $this->value($property->getName(), $property->getType(), false, null, $depth, sprintf('%s::$%s', $class, $property->getName())));
        }

        return $object;
    }

    protected function value(string $name, ?ReflectionType $type, bool $hasDefault, mixed $default, int $depth, string $context): mixed
    {
        if ($type?->allowsNull() === true && $this->nullChance !== 0 && $this->randomizer->getBool($this->nullChance)) {
            return null;
        }

        $byName = $this->byName($name, $type);
        if ($byName !== null) {
            return $byName;
        }

        if ($hasDefault) {
            return $default;
        }

        foreach ($this->namedTypes($type) as $namedType) {
            $value = $this->byType($namedType, $depth);
            if ($value !== null) {
                return $value;
            }
        }

        if ($type === null || $type->allowsNull()) {
            return null;
        }

        throw new InvalidArgumentException(sprintf('Cannot fill %s of type %s - pass it in $overrides', $context, $type));
    }

    /**
     * Value from name rules converted to the type, null when no rule matches or the value doesn't fit the type
     */
    protected function byName(string $name, ?ReflectionType $type): mixed
    {
        $words = $this->words($name);
        $expression = static::NAME_RULES[implode('', $words)] ?? null;

        if ($expression === null && count($words) > 1 && in_array($words[0], static::BOOLEAN_PREFIXES, true)) {
            $expression = 'number.boolean';
        }

        if ($expression === null && count($words) > 1) {
            $expression = static::WORD_RULES[$words[count($words) - 1]] ?? null;
        }

        if ($expression === null) {
            return null;
        }

        $value = str_starts_with($expression, '@') ? $this->profileValue(substr($expression, 1)) : $this->template->evaluate($expression);

        if ($type === null) {
            return $value;
        }

        foreach ($this->namedTypes($type) as $namedType) {
            $converted = $this->convert($value, $namedType->getName());
            if ($converted !== null) {
                return $converted;
            }
        }

        return null;
    }

    /**
     * Value fitting given type, null when the type can't be generated
     */
    protected function byType(ReflectionNamedType $type, int $depth): mixed
    {
        $name = $type->getName();

        return match (true) {
            $name === 'int' => $this->randomizer->getInt(1, 1000),
            $name === 'float' => round($this->randomizer->getFloat(0, 1000), 2),
            $name === 'bool' => $this->randomizer->getBool(),
            $name === 'string', $name === 'mixed' => $this->lorem->word(),
            $name === 'array', $name === 'iterable' => [],
            $name === DateTimeImmutable::class, $name === DateTimeInterface::class => $this->dateTime->dateTime(),
            $name === PhpDateTime::class => PhpDateTime::createFromImmutable($this->dateTime->dateTime()),
            enum_exists($name) => $this->enum->enumCase($name),
            class_exists($name) && $depth < static::MAX_DEPTH && new ReflectionClass($name)->isInstantiable() => $this->build($name, [], $depth + 1),
            default => null,
        };
    }

    /**
     * Value converted to given type name, null when it can't be
     */
    protected function convert(mixed $value, string $type): mixed
    {
        return match (true) {
            $type === 'mixed' => $value,
            $type === 'string' && (is_string($value) || is_int($value) || is_float($value)) => (string) $value,
            $type === 'string' && $value instanceof BackedEnum => (string) $value->value,
            $type === 'string' && $value instanceof DateTimeInterface => $value->format('Y-m-d H:i:s'),
            $type === 'int' && (is_int($value) || (is_string($value) && preg_match('/^-?\d+$/', $value) === 1)) => (int) $value,
            $type === 'float' && (is_float($value) || is_int($value) || (is_string($value) && is_numeric($value))) => (float) $value,
            $type === 'bool' && is_bool($value) => $value,
            $value instanceof DateTimeImmutable && ($type === DateTimeImmutable::class || $type === DateTimeInterface::class) => $value,
            $value instanceof DateTimeImmutable && $type === PhpDateTime::class => PhpDateTime::createFromImmutable($value),
            enum_exists($type) && (is_string($value) || is_int($value)) && is_subclass_of($type, BackedEnum::class) => $type::tryFrom($value),
            // own enum with the same values, i.e. App\Gender for Gender::Female
            $value instanceof BackedEnum && !$value instanceof $type && enum_exists($type) && is_subclass_of($type, BackedEnum::class) => $type::tryFrom($value->value),
            is_object($value) && $value instanceof $type => $value,
            default => null,
        };
    }

    /** @return list<ReflectionNamedType> */
    protected function namedTypes(?ReflectionType $type): array
    {
        return match (true) {
            $type instanceof ReflectionNamedType => [$type],
            $type instanceof ReflectionUnionType => array_values(array_filter($type->getTypes(), static fn (ReflectionType $t): bool => $t instanceof ReflectionNamedType && $t->getName() !== 'null')),
            default => [],
        };
    }

    /**
     * "createdAt", "created_at", "Created-At" → ['created', 'at']
     *
     * @return list<string>
     */
    protected function words(string $name): array
    {
        $spaced = (string) preg_replace(['/([a-z0-9])([A-Z])/', '/([A-Z]+)([A-Z][a-z])/', '/[_\-\s]+/'], ['$1 $2', '$1 $2', ' '], $name);

        return array_values(array_filter(explode(' ', strtolower(trim($spaced))), static fn (string $word): bool => $word !== ''));
    }

    /**
     * Value of profile()->person() shared by the object being filled
     */
    protected function profileValue(string $key): mixed
    {
        if ($this->profile === null) {
            $person = $this->template->evaluate('profile.person');
            $this->profile = is_array($person) ? $person : [];
        }

        // "address.city" - a key of a nested record
        $value = $this->profile;
        foreach (explode('.', $key) as $part) {
            $value = is_array($value) && array_key_exists($part, $value)
                ? $value[$part]
                : throw new InvalidArgumentException(sprintf('Unknown profile key "@%s" in name rules', $key));
        }

        return $value;
    }

    protected function override(mixed $value): mixed
    {
        return $value instanceof Closure ? $value() : $value;
    }

    /**
     * @param array<string, mixed> $overrides
     * @param list<string> $names
     */
    protected function validateOverrides(array $overrides, array $names, string $target): void
    {
        $unknown = array_diff(array_keys($overrides), $names);

        if ($unknown !== []) {
            throw new InvalidArgumentException(sprintf(
                'Unknown override "%s" for %s. Available: %s',
                implode('", "', $unknown),
                $target,
                implode(', ', $names),
            ));
        }
    }
}
