<?php

declare(strict_types = 1);

namespace Quidam\Test\LanguagePack\pl_PL;

use PHPUnit\Framework\TestCase;
use Quidam\Cli\Application;

/**
 * The pack in the command line - quidam -l pl_PL
 */
final class CliTest extends TestCase
{
    public function testPolishData(): void
    {
        [$code, $out] = $this->cli('person.name', '-l', 'pl_PL', '-n', '3', '-s', '2026');
        [, $again] = $this->cli('person.name', '--language=pl_PL', '-n', '3', '-s', '2026');

        self::assertSame(0, $code);
        self::assertSame("Kazimiera Wójtowicz\nAntonina Markiewicz\nTeodor Włodarczyk\n", $out);
        self::assertSame($out, $again);
    }

    public function testPackMethodsAndAliases(): void
    {
        [, $toll] = $this->cli('phoneNumber.tollFreePhoneNumber', '-l', 'pl_PL');
        [, $template] = $this->cli('-l', 'pl_PL', '-t', '{{ nationalId.pesel }} {{ nationalId.nip(true) }}');
        [, $citizen] = $this->cli('profile.citizen', '-l', 'pl_PL', '-f', 'json', '--now', '2026-01-01 12:00', '--timezone', 'UTC');

        self::assertMatchesRegularExpression('/^800[ -]\d{3}[ -]\d{3}\n$/', $toll);
        self::assertMatchesRegularExpression('/^\d{11} \d{3}-\d{3}-\d{2}-\d{2}\n$/', $template);
        $data = json_decode($citizen, true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($data);
        self::assertIsString($data['pesel']);
        self::assertTrue(Validators::pesel($data['pesel']));
    }

    public function testConfigAndPackTogether(): void
    {
        $config = tempnam(sys_get_temp_dir(), 'quidam');
        self::assertIsString($config);
        file_put_contents($config, <<<'PHP'
            <?php
            use Quidam\Addon\Template;
            use Quidam\Definitions\Extension\PaymentExtensionInterface;
            use Quidam\Quidam;
            use Psr\Clock\ClockInterface;

            return static fn (?int $seed, ClockInterface $clock): Quidam => Quidam::create($seed, $clock)
                ->withDefinition(Template::alias('bank'), PaymentExtensionInterface::class);
            PHP);

        try {
            [$code, $out] = $this->cli('-c', $config, '-l', 'pl_PL', '-t', '{{ bank.bank }}, {{ nationalId.pesel }}', '-s', '1');
        } finally {
            unlink($config);
        }

        self::assertSame(0, $code);
        self::assertMatchesRegularExpression('/^\S.+, \d{11}\n$/u', $out, 'aliases of the config and of the pack');
    }

    public function testList(): void
    {
        [, $aliases] = $this->cli('--list', '-l', 'pl_PL');
        [, $phone] = $this->cli('--list', 'phoneNumber', '-l', 'pl_PL');
        [, $core] = $this->cli('--list');

        self::assertStringContainsString('nationalId   Quidam\LanguagePack\pl_PL\NationalIdInterface', $aliases);
        self::assertMatchesRegularExpression('/Languages \(-l <code>\): .*\bpl_PL\b/', $core);
        self::assertStringNotContainsString('nationalId', $core, 'the alias comes with the pack');
        self::assertStringContainsString('phoneNumber.tollFreePhoneNumber()', $phone, 'methods of the pack class');
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
