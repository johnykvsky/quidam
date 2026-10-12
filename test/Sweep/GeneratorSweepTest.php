<?php

declare(strict_types = 1);

namespace Quidam\Test\Sweep;

use ErrorException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Quidam\Addon\EdgeCases;
use Quidam\Core\Internet;
use Quidam\Core\Payment;
use Quidam\Core\PhoneNumber;
use Quidam\Definitions\Addon\TemplateInterface;
use Quidam\LanguagePack\pl_PL\PlPlGenerator;
use Quidam\ProviderPack\ProviderPackInterface;
use Quidam\Quidam;
use ReflectionClass;
use ReflectionClassConstant;
use ReflectionMethod;
use ReflectionNamedType;

/**
 * Sweeps all generators instead of hand-picked ones: every public method of every extension, add-on and language pack
 * class, many samples per method - so a new method gets the generic checks too, and rare values (0.1% of results)
 * are hit. Format checks use independent data (official IBAN lengths, E.164 limits), not the generator's own tables.
 */
final class GeneratorSweepTest extends TestCase
{
    private const int SAMPLES = 120;

    private const array SEEDS = [1, 42, 2026];

    /**
     * Methods returning edge-case or random-character strings on purpose - whitespace and braces are expected there.
     * Class (and its subclasses) => method, null for all methods
     */
    private const array RAW_STRINGS = [[EdgeCases::class, null], [Internet::class, 'password']];

    /**
     * IBAN lengths from the SWIFT IBAN registry - independent of Payment::IBAN_FORMATS
     */
    private const array IBAN_LENGTHS = [
        'AD' => 24, 'AE' => 23, 'AL' => 28, 'AT' => 20, 'AZ' => 28, 'BA' => 20, 'BE' => 16, 'BG' => 22, 'BH' => 22,
        'BI' => 27, 'BR' => 29, 'BY' => 28, 'CH' => 21, 'CR' => 22, 'CY' => 28, 'CZ' => 24, 'DE' => 22, 'DJ' => 27,
        'DK' => 18, 'DO' => 28, 'EE' => 20, 'EG' => 29, 'ES' => 24, 'FI' => 18, 'FK' => 18, 'FO' => 18, 'FR' => 27,
        'GB' => 22, 'GE' => 22, 'GI' => 23, 'GL' => 18, 'GR' => 27, 'GT' => 28, 'HN' => 28, 'HR' => 21, 'HU' => 28,
        'IE' => 22, 'IL' => 23, 'IQ' => 23, 'IS' => 26, 'IT' => 27, 'JO' => 30, 'KW' => 30, 'KZ' => 20, 'LB' => 28,
        'LC' => 32, 'LI' => 21, 'LT' => 20, 'LU' => 20, 'LV' => 21, 'LY' => 25, 'MC' => 27, 'MD' => 24, 'ME' => 22,
        'MK' => 19, 'MN' => 20, 'MR' => 27, 'MT' => 31, 'MU' => 30, 'NI' => 28, 'NL' => 18, 'NO' => 15, 'OM' => 23,
        'PK' => 24, 'PL' => 28, 'PS' => 29, 'PT' => 25, 'QA' => 29, 'RO' => 24, 'RS' => 22, 'RU' => 33, 'SA' => 24,
        'SC' => 31, 'SD' => 18, 'SE' => 24, 'SI' => 19, 'SK' => 24, 'SM' => 27, 'SO' => 23, 'ST' => 25, 'SV' => 28,
        'TL' => 23, 'TN' => 24, 'TR' => 26, 'UA' => 29, 'VA' => 22, 'VG' => 24, 'XK' => 20, 'YE' => 30,
    ];

    protected function setUp(): void
    {
        // a warning, notice or deprecation inside a generator fails the sweep
        set_error_handler(static function (int $level, string $message, string $file, int $line): never {
            throw new ErrorException($message, 0, $level, $file, $line);
        });
    }

    protected function tearDown(): void
    {
        restore_error_handler();
    }

    /** @return iterable<string, array{class-string, string, ?string}> */
    public static function generatorMethods(): iterable
    {
        $src = dirname(__DIR__, 2) . '/src/';
        $files = [...(array) glob($src . 'Core/*.php'), ...(array) glob($src . 'Addon/*.php'), ...(array) glob($src . 'LanguagePack/*/*.php')];

        foreach ($files as $file) {
            $class = 'Quidam\\' . str_replace(['/', '.php'], ['\\', ''], substr((string) $file, strlen($src)));

            if (!class_exists($class)) {
                continue;
            }

            $reflection = new ReflectionClass($class);
            $pack = preg_match('/\\\\LanguagePack\\\\(\w+)\\\\/', $class, $match) === 1 ? $match[1] : null;

            if (!$reflection->isInstantiable() || str_ends_with($class, 'Generator') || str_ends_with($class, 'ProviderPack')) {
                continue;
            }

            foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                if ($method->isConstructor() || $method->isStatic() || $method->getNumberOfRequiredParameters() > 0 || str_starts_with($method->getName(), 'with')) {
                    continue;
                }

                $name = $reflection->getShortName() . '::' . $method->getName();
                yield ($pack !== null ? $pack . ' ' : '') . $name . (str_contains($class, '\\Addon\\') ? ' (add-on)' : '') => [$class, $method->getName(), $pack];

                // core classes also run inside the Polish pack - its locale and data reach them
                if ($pack === null && !str_contains($class, '\\Addon\\')) {
                    yield 'in pl_PL ' . $name => [$class, $method->getName(), 'pl_PL'];
                }
            }
        }
    }

    /** @param class-string $class */
    #[DataProvider('generatorMethods')]
    public function testEveryMethodGivesCleanValues(string $class, string $method, ?string $pack): void
    {
        $raw = false;

        foreach (self::RAW_STRINGS as [$rawClass, $rawMethod]) {
            $raw = $raw || (is_a($class, $rawClass, true) && ($rawMethod === null || $rawMethod === $method));
        }

        foreach (self::SEEDS as $seed) {
            $generator = match ($pack) {
                null => Quidam::create(seed: $seed),
                'pl_PL' => PlPlGenerator::create(seed: $seed),
                default => Quidam::create(seed: $seed)->withProvider(self::providerPack($pack)),
            };
            $extension = $generator->ext($class);

            for ($i = 0; $i < self::SAMPLES; ++$i) {
                $this->assertClean($extension->$method(), $raw, sprintf('%s::%s() with seed %d', $class, $method, $seed));
                // no warning, notice or exception is a check too - for values without string checks (numbers, dates)
                $this->addToAssertionCount(1);
            }
        }
    }

    /** Provider pack of a pack code - the CLI's naming convention, la_VA -> LanguagePack\la_VA\LaVaProviderPack */
    private static function providerPack(string $pack): ProviderPackInterface
    {
        [$language, $country] = explode('_', $pack);
        $class = sprintf('Quidam\\LanguagePack\\%s\\%s%sProviderPack', $pack, ucfirst($language), ucfirst(strtolower($country)));
        $provider = new $class();
        self::assertInstanceOf(ProviderPackInterface::class, $provider);

        return $provider;
    }

    public function testIbanHasRegistryLengthForEveryCountry(): void
    {
        $payment = Quidam::create(seed: 7)->payment();
        $formats = new ReflectionClassConstant(Payment::class, 'IBAN_FORMATS')->getValue();
        self::assertIsArray($formats);

        // both ways - a country without registry length, or a registry entry the generator lost
        self::assertEqualsCanonicalizing(array_keys(self::IBAN_LENGTHS), array_keys($formats), 'IBAN countries differ from IBAN_LENGTHS');

        foreach (array_keys(self::IBAN_LENGTHS) as $country) {
            for ($i = 0; $i < 20; ++$i) {
                $iban = $payment->iban($country);
                self::assertSame(self::IBAN_LENGTHS[$country], strlen($iban), $iban);
                self::assertMatchesRegularExpression('/^' . $country . '\d{2}[A-Z0-9]+$/', $iban);
                self::assertSame(1, self::mod97(substr($iban, 4) . substr($iban, 0, 4)), $iban);
            }
        }
    }

    public function testE164NumberForEveryCountry(): void
    {
        $phone = Quidam::create(seed: 7)->phoneNumber();
        $callingCodes = new ReflectionClassConstant(PhoneNumber::class, 'COUNTRY_CALLING_CODES')->getValue();
        self::assertIsArray($callingCodes);

        foreach ($callingCodes as $country => $callingCode) {
            self::assertIsString($country);
            self::assertIsString($callingCode);

            for ($i = 0; $i < 20; ++$i) {
                $number = $phone->e164PhoneNumber($country);
                // E.164: "+", calling code, at most 15 digits in total
                self::assertMatchesRegularExpression('/^\+' . $callingCode . '\d+$/', $number);
                self::assertLessThanOrEqual(15, strlen($number) - 1, $number);
                self::assertGreaterThanOrEqual(7, strlen($number) - 1, $number);
            }
        }
    }

    public function testNetworkValuesAreAlwaysPublicAndValid(): void
    {
        $internet = Quidam::create(seed: 7)->internet();

        // public ranges miss ~0.1% of addresses (i.e. 100.64.0.0/10) - only many samples find such leaks
        for ($i = 0; $i < 20000; ++$i) {
            $ipv4 = $internet->ipv4();
            $ipv6 = $internet->ipv6();
            $mac = $internet->macAddress();

            self::assertNotFalse(filter_var($ipv4, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_GLOBAL_RANGE), $ipv4);
            self::assertNotFalse(filter_var($ipv6, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 | FILTER_FLAG_GLOBAL_RANGE), $ipv6);
            // unicast (bit 0 clear), locally administered (bit 1 set)
            self::assertSame(2, hexdec(substr($mac, 0, 2)) & 3, $mac);
        }
    }

    /** @return iterable<string, array{bool}> */
    public static function generators(): iterable
    {
        yield 'default' => [false];
        yield 'pl_PL' => [true];
    }

    #[DataProvider('generators')]
    public function testEveryTemplateTokenRenders(bool $polish): void
    {
        $generator = $polish ? PlPlGenerator::create(seed: 7) : Quidam::create(seed: 7);
        $template = $generator->ext(TemplateInterface::class);
        $tokens = 0;

        foreach ($template->aliases() as $alias => $id) {
            if (!class_exists($id) && !interface_exists($id)) {
                self::fail(sprintf('Alias "%s" points to unknown class "%s"', $alias, $id));
            }

            foreach (new ReflectionClass($generator->ext($id))->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                $type = $method->getReturnType();

                if (
                    $method->isConstructor() || $method->isStatic() || $method->getNumberOfRequiredParameters() > 0
                    || !$type instanceof ReflectionNamedType || !in_array($type->getName(), ['string', 'int', 'float'], true)
                ) {
                    continue;
                }

                $token = sprintf('{{ %s.%s }}', $alias, $method->getName());
                $output = $template->parse($token);

                self::assertNotSame('', $output, $token);
                self::assertStringNotContainsString('{{', $output, $token);
                ++$tokens;
            }
        }

        self::assertGreaterThan(150, $tokens);
    }

    private function assertClean(mixed $value, bool $raw, string $context): void
    {
        if (is_array($value)) {
            foreach ($value as $key => $item) {
                $this->assertClean($item, $raw, $context . '[' . $key . ']');
            }

            return;
        }

        if (is_float($value)) {
            self::assertTrue(is_finite($value), $context);

            return;
        }

        if (!is_string($value) || $raw) {
            return;
        }

        self::assertTrue(mb_check_encoding($value, 'UTF-8'), $context . ': invalid UTF-8');

        if (str_contains($value, "\n")) {
            return; // multi-line text, documents, CSV
        }

        self::assertSame(trim($value), $value, $context . ': leading or trailing whitespace');
        self::assertDoesNotMatchRegularExpression('/ {2}/', $value, $context . ': double space');
        self::assertDoesNotMatchRegularExpression('/\{\{|\}\}|\{[a-zA-Z]+\}/', $value, $context . ': unfilled placeholder');
    }

    private static function mod97(string $rearranged): int
    {
        $remainder = 0;

        foreach (str_split((string) preg_replace_callback('/[A-Z]/', static fn (array $m): string => (string) (ord($m[0]) - 55), $rearranged)) as $digit) {
            $remainder = ($remainder * 10 + (int) $digit) % 97;
        }

        return $remainder;
    }
}
