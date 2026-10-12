<?php

declare(strict_types = 1);

namespace Quidam\Test\Addon;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Quidam\Addon\Template;
use Quidam\DefinitionPack\DefinitionPack;
use Quidam\Definitions\Addon\EdgeCasesInterface;
use Quidam\Definitions\Addon\RichTextInterface;
use Quidam\Definitions\Addon\TemplateInterface;
use Quidam\Definitions\Addon\TextInterface;
use Quidam\Definitions\Extension\PersonExtensionInterface;
use Quidam\Definitions\Randomizer\RandomizerInterface;
use Quidam\Exception\InvalidArgumentException;
use Quidam\Quidam;
use Quidam\Test\Fixtures\CarExtension;
use Quidam\Test\Fixtures\FixedPerson;
use Quidam\Test\Fixtures\GenderedSurnamePerson;
use Quidam\Test\Fixtures\TemplateValues;

final class TemplateTest extends TestCase
{
    private TemplateInterface $template;

    protected function setUp(): void
    {
        $this->template = Quidam::create()
            ->withDefinition(TemplateInterface::class, static fn (ContainerInterface $c): Template => new Template($c, [
                ...Template::GENERATORS,
                'values' => TemplateValues::class,
            ]))
            ->ext(TemplateInterface::class);
    }

    public function testReplacesTokens(): void
    {
        $result = Quidam::create()->ext(TemplateInterface::class)->parse('{{ person.firstName }} {{person.lastName}} <{{ internet.email }}>');

        self::assertMatchesRegularExpression('/^[A-Z][a-z]+ [A-Z][A-Za-z]+ <[a-z0-9._]+@example\.(com|org|net)>$/', $result);
    }

    public function testAliases(): void
    {
        self::assertSame(Template::GENERATORS, Quidam::create()->ext(TemplateInterface::class)->aliases());
        self::assertArrayHasKey('person', $this->template->aliases());
    }

    public function testCallsWithArguments(): void
    {
        self::assertMatchesRegularExpression(
            '/^INV-\d{4}-(5|6)$/',
            $this->template->parse('INV-{{ number.numberBetween(1000, 9999) }}-{{ number.numberBetween(max: 6, min: 5) }}'),
        );
        self::assertMatchesRegularExpression('/^[a-z]{2}-\d{3}$/', $this->template->parse("{{ strings.bothify('??-###') }}"));
        self::assertMatchesRegularExpression('/^\d{4}$/', $this->template->parse('{{ dateTime.date("Y") }}'));
    }

    /** @return iterable<string, array{string, string}> */
    public static function arguments(): iterable
    {
        yield 'no parentheses' => ['{{ values.echo }}', ''];
        yield 'empty parentheses' => ['{{ values.echo() }}', ''];
        yield 'int and negative int' => ['{{ values.echo(1, -2) }}', 'int:1|int:-2'];
        yield 'float' => ['{{ values.echo(1.5, -0.25) }}', 'float:1.5|float:-0.25'];
        yield 'bool and null' => ['{{ values.echo(true, false, null) }}', 'bool:true|bool:false|null:NULL'];
        yield 'single quoted' => ["{{ values.echo('a, (b)') }}", "string:'a, (b)'"];
        yield 'double quoted' => ['{{ values.echo("it\'s") }}', "string:'it\\'s'"];
        yield 'escaped quote' => ["{{ values.echo('it\\'s') }}", "string:'it\\'s'"];
        yield 'escaped backslash' => ["{{ values.echo('a\\\\b') }}", "string:'a\\\\b'"];
        yield 'named' => ['{{ values.echo(1, second: "x") }}', "int:1|second=string:'x'"];
        yield 'spaces' => ['{{values.echo( 1 ,  2 )}}', 'int:1|int:2'];
    }

    #[DataProvider('arguments')]
    public function testParsesArguments(string $template, string $expected): void
    {
        self::assertSame($expected, $this->template->parse($template));
    }

    /** @return iterable<string, array{string, string}> */
    public static function conversions(): iterable
    {
        yield 'null' => ['[{{ values.nothing }}]', '[]'];
        yield 'float' => ['{{ values.float }}', '1.5'];
        yield 'stringable' => ['{{ values.stringable }}', 'stringable'];
        yield 'date' => ['{{ values.date }}', '2026-01-02 03:04:05'];
        yield 'backed enum' => ['{{ values.suit }}', 'H'];
        yield 'unit enum' => ['{{ values.unitSuit }}', 'Spades'];
        yield 'array' => ['{{ values.list }}', '1, two, true, S'];
    }

    #[DataProvider('conversions')]
    public function testConvertsResultsToString(string $template, string $expected): void
    {
        self::assertSame($expected, $this->template->parse($template));
    }

    public function testLeavesNonTokensUntouched(): void
    {
        $text = 'plain {{ firstName }} {{ }} {{ person. }} {single} person.firstName';

        self::assertSame($text, $this->template->parse($text));
    }

    public function testConvertsStringsToEnumArguments(): void
    {
        $template = Quidam::create()->withDefinition(PersonExtensionInterface::class, GenderedSurnamePerson::class)->ext(TemplateInterface::class);

        for ($i = 0; $i < 20; ++$i) {
            self::assertStringEndsWith(' Kowalska', $template->parse('{{ person.name("female") }}'));
            self::assertStringEndsWith(' Kowalski', $template->parse('{{ person.name(gender: "male") }}'));
        }

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid value "other" for $gender of name(), expected: male, female');
        $template->parse('{{ person.name("other") }}');
    }

    public function testUsesReplacedGenerators(): void
    {
        $template = Quidam::create()->withDefinition(PersonExtensionInterface::class, FixedPerson::class)->ext(TemplateInterface::class);

        self::assertSame('Johny Fixed', $template->parse('{{ person.firstName }} {{ person.lastName }}'));
    }

    public function testCustomAliasAndRestrictedMap(): void
    {
        $template = Quidam::create()
            ->withDefinition(TemplateInterface::class, static fn (ContainerInterface $c): Template => new Template($c, ['car' => CarExtension::class]))
            ->ext(TemplateInterface::class);

        self::assertMatchesRegularExpression('/^Car (Toyota|Ford|Fiat)$/', $template->parse('{{ car.brand }}'));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Available: car');
        $template->parse('{{ person.name }}');
    }

    public function testRegisteredAliases(): void
    {
        $generator = Quidam::create()
            ->withDefinition(Template::alias('car'), CarExtension::class)
            ->withDefinition(Template::alias('auto'), static fn (RandomizerInterface $r, PersonExtensionInterface $p): CarExtension => new CarExtension($r, $p, 'Auto'))
            ->withDefinition(Template::alias('person'), FixedPerson::class);
        $template = $generator->ext(TemplateInterface::class);

        self::assertMatchesRegularExpression('/^Car \w+ \/ Auto \w+$/', $template->parse('{{ car.brand }} / {{ auto.brand }}'));
        self::assertSame('Johny Fixed', $template->parse('{{ person.name }}'), 'registered alias replaces a default one');
        self::assertSame($generator->ext(CarExtension::class), $generator->container()->get($template->aliases()['car']), 'the shared instance');
        self::assertSame(CarExtension::class, $template->aliases()['car']);
        self::assertSame('template.auto', $template->aliases()['auto'], 'factory - the alias id');
        self::assertSame('template.car', Template::alias('car'));
    }

    public function testExplicitMapIgnoresRegisteredAliases(): void
    {
        $template = Quidam::create()
            ->withDefinition(Template::alias('car'), CarExtension::class)
            ->withDefinition(TemplateInterface::class, static fn (ContainerInterface $c): Template => new Template($c, ['person' => PersonExtensionInterface::class]))
            ->ext(TemplateInterface::class);

        self::assertSame(['person' => PersonExtensionInterface::class], $template->aliases(), 'a whitelist stays exact');
        $this->expectException(InvalidArgumentException::class);
        $template->parse('{{ car.brand }}');
    }

    public function testInvalidAliasName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Template::alias('car.brand');
    }

    public function testSeedGivesSameResult(): void
    {
        $text = '{{ person.name }} {{ internet.email }} {{ number.randomFloat }} {{ uuid.uuid4 }}';

        self::assertSame(
            Quidam::create(seed: 7)->ext(TemplateInterface::class)->parse($text),
            Quidam::create(seed: 7)->ext(TemplateInterface::class)->parse($text),
        );
    }

    /** @return iterable<string, array{string, string}> */
    public static function invalidTemplates(): iterable
    {
        yield 'unknown alias' => ['{{ foo.bar }}', 'Unknown template generator "foo"'];
        yield 'unknown method' => ['{{ person.nope }}', 'Unknown template method "person.nope"'];
        yield 'magic method' => ['{{ person.__construct }}', 'Unknown template method'];
        yield 'protected method' => ['{{ values.hidden }}', 'Unknown template method'];
        yield 'static method' => ['{{ values.staticMethod }}', 'Unknown template method'];
        yield 'bare word argument' => ['{{ number.numberBetween(1, max) }}', 'Unsupported template argument "max"'];
        yield 'positional after named' => ['{{ number.numberBetween(min: 1, 5) }}', 'cannot follow named argument'];
        yield 'empty argument' => ['{{ number.numberBetween(1, ) }}', 'Empty argument'];
        yield 'wrong argument type' => ["{{ number.numberBetween('a') }}", 'Cannot call "number.numberBetween"'];
        yield 'unknown named argument' => ['{{ number.numberBetween(foo: 1) }}', 'unknown argument "foo", expected: min, max'];
        yield 'too few arguments' => ['{{ values.broken }}', 'Cannot call "values.broken": Too few arguments'];
        yield 'object result' => ['{{ values.object }}', 'cannot be converted to string'];
    }

    #[DataProvider('invalidTemplates')]
    public function testInvalidTemplates(string $template, string $message): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($message);
        $this->template->parse($template);
    }

    public function testErrorsInsideGeneratorArePassedOn(): void
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('internal bug with 5');
        $this->template->parse('{{ values.broken(5) }}');
    }

    public function testDefaultMapHasEveryExtension(): void
    {
        $pack = new DefinitionPack();
        $expected = [...array_keys($pack->extensions()), RandomizerInterface::class, TextInterface::class, RichTextInterface::class, EdgeCasesInterface::class];
        $map = array_values(Template::GENERATORS);
        sort($expected);
        sort($map);

        self::assertSame($expected, $map);
        self::assertSame(17, strlen($this->template->parse('{{ vehicle.vin }}')));
    }
}
