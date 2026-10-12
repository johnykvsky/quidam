<?php

declare(strict_types = 1);

namespace Quidam\Test\Cli;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Quidam\Cli\Application;

final class ApplicationTest extends TestCase
{
    public function testExpression(): void
    {
        [$code, $out] = $this->cli('person.name');

        self::assertSame(0, $code);
        self::assertMatchesRegularExpression("/^[A-Z][A-Za-z']+ [A-Z][A-Za-z']+\n$/", $out);
    }

    public function testCountSeedAndArguments(): void
    {
        [$code, $out] = $this->cli('number.numberBetween(1, 100)', '-n', '5', '--seed=42');
        [, $again] = $this->cli('number.numberBetween(1, 100)', '-n5', '-s', '42');

        self::assertSame(0, $code);
        self::assertCount(5, explode("\n", trim($out)));
        self::assertMatchesRegularExpression('/^(\d{1,3}\n){5}$/', $out);
        self::assertSame($out, $again, 'same seed - same values');
    }

    public function testUnique(): void
    {
        [, $out] = $this->cli('number.randomDigit', '-n', '10', '--unique');
        $digits = explode("\n", trim($out));
        sort($digits);

        self::assertSame(array_map(strval(...), range(0, 9)), $digits);

        [$code, , $err] = $this->cli('number.randomDigit', '-n', '11', '-u');
        self::assertSame(1, $code);
        self::assertStringContainsString('Unique value for "cli" not found', $err);
    }

    public function testTemplate(): void
    {
        [$code, $out] = $this->cli('-t', '{{ person.firstName }} <{{ internet.email }}>', '-n', '2');

        self::assertSame(0, $code);
        self::assertMatchesRegularExpression('/^([A-Z][a-z]+ <[a-z0-9._]+@example\.(com|org|net)>\n){2}$/', $out);
    }

    public function testJsonFormat(): void
    {
        [, $single] = $this->cli('profile.person', '--format', 'json', '--now', '2026-01-01 12:00', '--timezone', 'UTC');
        [, $many] = $this->cli('uuid.uuid4', '-f', 'json', '-n', '3');

        $person = json_decode($single, true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($person);
        self::assertIsString($person['dateOfBirth']);
        self::assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T00:00:00\+00:00$/', $person['dateOfBirth']);

        $uuids = json_decode($many, true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($uuids);
        self::assertCount(3, $uuids);
    }

    public function testCsvFormat(): void
    {
        [, $people] = $this->cli('profile.person', '-n', '3', '-f', 'csv');
        $rows = array_map(static fn (string $line): array => str_getcsv($line, ',', '"', ''), explode("\n", trim($people)));

        self::assertCount(4, $rows);
        self::assertSame(['gender', 'title', 'firstName', 'lastName', 'name', 'userName', 'email', 'dateOfBirth', 'age', 'phone', 'address'], $rows[0]);
        $address = $rows[1][10] ?? null;
        self::assertIsString($address);
        self::assertJson($address, 'nested address as JSON cell');

        [, $scalars] = $this->cli('number.randomDigit', '-n', '2', '-f', 'csv');
        self::assertMatchesRegularExpression('/^value\n\d\n\d\n$/', $scalars);
    }

    public function testFrozenNow(): void
    {
        [, $out] = $this->cli("dateTime.date('Y-m-d H:i', 'now')", '--now', '2026-03-04 05:06', '--timezone', 'Europe/Warsaw');
        [, $uuid] = $this->cli('uuid.uuid7', '--now', '2026-01-01 00:00:00 UTC');

        self::assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}\n$/', $out);
        self::assertLessThanOrEqual('2026-03-04 05:06', trim($out));
        self::assertStringStartsWith('019b7', $uuid, 'uuid7 timestamp from --now');
    }

    public function testTextFormatOfValues(): void
    {
        [, $bool] = $this->cli('number.boolean(100)');
        [, $array] = $this->cli('color.rgbColorAsArray');
        [, $date] = $this->cli("dateTime.dateTimeBetween('2020-01-01 10:00:00 UTC', '2020-01-01 10:00:00 UTC')", '--timezone', 'UTC');

        self::assertSame("true\n", $bool);
        self::assertMatchesRegularExpression('/^\[\d+,\d+,\d+\]\n$/', $array);
        self::assertSame("2020-01-01T10:00:00+00:00\n", $date);
    }

    public function testList(): void
    {
        [$code, $aliases] = $this->cli('--list');
        [, $methods] = $this->cli('--list', 'payment');
        [, $named] = $this->cli('--list=uuid');

        self::assertSame(0, $code);
        self::assertStringContainsString('vehicle      Quidam\Definitions\Extension\VehicleExtensionInterface', $aliases);
        self::assertStringContainsString('text         Quidam\Definitions\Addon\TextInterface', $aliases);
        self::assertStringContainsString("payment.iban(?string \$countryCode = null, string \$prefix = '', bool \$formatted = false)", $methods);
        self::assertStringContainsString('uuid.uuid5(string $namespace, string $name)', $named);
    }

    public function testLanguagePackByClassName(): void
    {
        $pack = \Quidam\Test\Fixtures\PlProviderPack::class;
        [$code, $out] = $this->cli('payment.localPrice(1000, 2000)', '--language', $pack);
        [, $short] = $this->cli('phoneNumber.e164PhoneNumber', '-l' . $pack);
        [, $list] = $this->cli('--list', '-l', $pack);

        self::assertSame(0, $code);
        self::assertMatchesRegularExpression("/^1\u{00A0}\d{3},\d{2}\u{00A0}zł\n$/u", $out);
        self::assertMatchesRegularExpression('/^\+48\d{9}\n$/', $short);
        self::assertStringContainsString('Languages (-l <code>):', $list);
    }

    public function testConfig(): void
    {
        $config = __DIR__ . '/../Fixtures/cli-config.php';
        [$code, $out] = $this->cli('car.brand', '--config', $config, '-n', '3', '-s', '5');
        [, $again] = $this->cli('car.brand', '-c' . $config, '-n', '3', '-s', '5');
        [, $template] = $this->cli('-c', $config, '-t', '{{ car.brand }} of {{ car.owner }}', '--language', \Quidam\Test\Fixtures\PlProviderPack::class);
        [, $list] = $this->cli('--list', '-c', $config);
        [, $plain] = $this->cli('--list');

        self::assertSame(0, $code);
        self::assertMatchesRegularExpression('/^(Auto (Toyota|Ford|Fiat)\n){3}$/', $out, 'built with a constructor argument');
        self::assertSame($out, $again, '--seed applies to the configured generator');
        self::assertMatchesRegularExpression('/^Auto \w+ of \S+ \S+\n$/u', $template, '--language on top of the config');
        self::assertStringContainsString('car          Quidam\Test\Fixtures\CarExtension', $list);
        self::assertStringNotContainsString('car ', $plain, 'without --config - default Quidam');
    }

    public function testInvalidConfig(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'quidam');
        self::assertIsString($file);

        try {
            file_put_contents($file, '<?php return 42;');
            [$code, , $err] = $this->cli('person.name', '-c', $file);
            self::assertSame(1, $code);
            self::assertStringContainsString('must return a Closure(?int $seed, ClockInterface $clock): Quidam, got int', $err);

            file_put_contents($file, '<?php return static fn (): string => "nope";');
            [$code, , $err] = $this->cli('person.name', '-c', $file);
            self::assertSame(1, $code);
            self::assertStringContainsString('factory must return Quidam, got string', $err);
        } finally {
            unlink($file);
        }
    }

    public function testHelp(): void
    {
        [$code, $out] = $this->cli('--help');

        self::assertSame(0, $code);
        self::assertStringStartsWith('Usage:', $out);
    }

    /** @return iterable<string, array{list<string>, int, string}> */
    public static function errors(): iterable
    {
        yield 'nothing' => [[], 2, 'Give one expression'];
        yield 'expression and template' => [['person.name', '-t', 'x'], 2, 'Give one expression'];
        yield 'two expressions' => [['person.name', 'person.lastName'], 2, 'Only one expression'];
        yield 'unknown option' => [['person.name', '--colour'], 2, 'Unknown option "--colour"'];
        yield 'missing value' => [['person.name', '-n'], 2, 'Option -n needs a value'];
        yield 'bad count' => [['person.name', '-n', 'many'], 2, 'needs an integer >= 1'];
        yield 'bad format' => [['person.name', '-f', 'xml'], 2, 'Unknown format "xml"'];
        yield 'bad timezone' => [['person.name', '--timezone', 'Mars/Base'], 2, 'Unknown timezone'];
        yield 'flag with value' => [['person.name', '--unique=yes'], 2, "doesn't take a value"];
        yield 'unknown alias' => [['nope.name'], 1, 'Unknown template generator "nope"'];
        yield 'unknown method' => [['person.nope'], 1, 'Unknown template method "person.nope"'];
        yield 'bad arguments' => [['number.numberBetween(10, 1)'], 1, '$min cannot be higher than $max'];
        yield 'bad now' => [['person.name', '--now', 'not a date'], 1, 'Invalid --now date'];
        yield 'unsupported argument' => [['random.randomElement([1])'], 1, 'Unsupported template argument'];
        yield 'unknown list alias' => [['--list', 'nope'], 1, 'Unknown alias "nope"'];
        yield 'missing config' => [['person.name', '--config', '/nope/quidam.php'], 2, 'Config file "/nope/quidam.php" not found'];
        yield 'unknown language' => [['person.name', '-l', 'xx_XX'], 2, 'Unknown language "xx_XX"'];
        yield 'not a provider pack' => [['person.name', '--language', \stdClass::class], 2, 'Unknown language "stdClass"'];
    }

    /** @param list<string> $arguments */
    #[DataProvider('errors')]
    public function testErrors(array $arguments, int $expectedCode, string $message): void
    {
        [$code, $out, $err] = $this->cli(...$arguments);

        self::assertSame($expectedCode, $code);
        self::assertSame('', $out);
        self::assertStringContainsString($message, $err);
    }

    public function testBinScript(): void
    {
        exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__DIR__ . '/../../bin/quidam') . ' person.name --seed 1', $output, $code);

        self::assertSame(0, $code);
        self::assertCount(1, $output);
        self::assertMatchesRegularExpression("/^[A-Z][A-Za-z']+ [A-Z][A-Za-z']+$/", $output[0]);
    }

    /** @return array{int, string, string} */
    private function cli(string ...$arguments): array
    {
        $stdout = fopen('php://memory', 'w+');
        $stderr = fopen('php://memory', 'w+');
        self::assertIsResource($stdout);
        self::assertIsResource($stderr);

        $code = new Application()->run(['quidam', ...array_values($arguments)], $stdout, $stderr);
        rewind($stdout);
        rewind($stderr);

        return [$code, (string) stream_get_contents($stdout), (string) stream_get_contents($stderr)];
    }
}
