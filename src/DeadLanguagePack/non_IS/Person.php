<?php

declare(strict_types = 1);

namespace Quidam\DeadLanguagePack\non_IS;

use Quidam\Core\Person as CorePerson;
use Quidam\Definitions\Enum\Gender;

/**
 * Norse names, normalized Old Norse spelling - a given name and the father's name in genitive with -son / -dóttir:
 * "Leifr Eiríksson", "Freydís Eiríksdóttir", "Hákon Hákonarson"; bynames ("inn rauði" - the Red) and runes apart
 */
class Person extends CorePerson
{
    /** @var list<string> */
    protected const array FIRST_NAME_MALE = [
        'Ari', 'Arnbjǫrn', 'Arnórr', 'Ásgeirr', 'Ásmundr', 'Bjǫrn', 'Bolli', 'Egill', 'Eiríkr', 'Flosi', 'Gizurr',
        'Glúmr', 'Grettir', 'Grímr', 'Guðmundr', 'Gunnarr', 'Hákon', 'Hálfdan', 'Hallfreðr', 'Hallr', 'Haraldr',
        'Hǫskuldr', 'Hrafn', 'Hrólfr', 'Ingólfr', 'Ívarr', 'Kári', 'Ketill', 'Kjartan', 'Knútr', 'Leifr', 'Njáll',
        'Óláfr', 'Ormr', 'Óspakr', 'Ragnarr', 'Sigurðr', 'Skarpheðinn', 'Snorri', 'Steinarr', 'Sveinn', 'Þórðr',
        'Þorgeirr', 'Þorkell', 'Þórólfr', 'Þorsteinn', 'Þorvaldr', 'Úlfr',
    ];

    /**
     * Genitive of every male name - the first part of patronymics: Eiríks-son, Sigurðar-son, Bjarnar-son, Snorra-son
     *
     * @var array<string, string>
     */
    protected const array GENITIVE = [
        'Ari' => 'Ara', 'Arnbjǫrn' => 'Arnbjarnar', 'Arnórr' => 'Arnórs', 'Ásgeirr' => 'Ásgeirs', 'Ásmundr' => 'Ásmundar',
        'Bjǫrn' => 'Bjarnar', 'Bolli' => 'Bolla', 'Egill' => 'Egils', 'Eiríkr' => 'Eiríks', 'Flosi' => 'Flosa',
        'Gizurr' => 'Gizurar', 'Glúmr' => 'Glúms', 'Grettir' => 'Grettis', 'Grímr' => 'Gríms', 'Guðmundr' => 'Guðmundar',
        'Gunnarr' => 'Gunnars', 'Hákon' => 'Hákonar', 'Hálfdan' => 'Hálfdanar', 'Hallfreðr' => 'Hallfreðar',
        'Hallr' => 'Halls', 'Haraldr' => 'Haralds', 'Hǫskuldr' => 'Hǫskulds', 'Hrafn' => 'Hrafns', 'Hrólfr' => 'Hrólfs',
        'Ingólfr' => 'Ingólfs', 'Ívarr' => 'Ívars', 'Kári' => 'Kára', 'Ketill' => 'Ketils', 'Kjartan' => 'Kjartans',
        'Knútr' => 'Knúts', 'Leifr' => 'Leifs', 'Njáll' => 'Njáls', 'Óláfr' => 'Óláfs', 'Ormr' => 'Orms',
        'Óspakr' => 'Óspaks', 'Ragnarr' => 'Ragnars', 'Sigurðr' => 'Sigurðar', 'Skarpheðinn' => 'Skarpheðins',
        'Snorri' => 'Snorra', 'Steinarr' => 'Steinars', 'Sveinn' => 'Sveins', 'Þórðr' => 'Þórðar', 'Þorgeirr' => 'Þorgeirs',
        'Þorkell' => 'Þorkels', 'Þórólfr' => 'Þórólfs', 'Þorsteinn' => 'Þorsteins', 'Þorvaldr' => 'Þorvalds',
        'Úlfr' => 'Úlfs',
    ];

    /** @var list<string> */
    protected const array FIRST_NAME_FEMALE = [
        'Álfhildr', 'Ása', 'Ástríðr', 'Auðr', 'Bergþóra', 'Freydís', 'Guðrún', 'Gunnhildr', 'Gyða', 'Hallgerðr', 'Helga',
        'Hervǫr', 'Hildr', 'Ingibjǫrg', 'Ingunn', 'Jórunn', 'Ólǫf', 'Ragnheiðr', 'Ragnhildr', 'Sigríðr', 'Steinunn',
        'Þóra', 'Þórdís', 'Þórunn', 'Unnr', 'Valgerðr', 'Vigdís',
    ];

    /** @var list<string> */
    protected const array FIRST_NAME_NEUTRAL = [];

    /**
     * Bynames as [masculine, feminine] - after the name: "Eiríkr inn rauði", "Auðr in djúpúðga"
     *
     * @var list<array{string, string}>
     */
    protected const array BYNAMES = [
        ['inn rauði', 'in rauða'], ['inn hvíti', 'in hvíta'], ['inn svarti', 'in svarta'], ['inn sterki', 'in sterka'],
        ['inn gamli', 'in gamla'], ['inn ungi', 'in unga'], ['inn spaki', 'in spaka'], ['inn heppni', 'in heppna'],
        ['inn fagri', 'in fagra'], ['inn djúpúðgi', 'in djúpúðga'], ['hárfagri', 'hárfagra'], ['blátǫnn', 'blátǫnn'],
        ['loðbrók', 'loðbrók'], ['skalli', 'skalla'],
    ];

    /** @var list<string> */
    protected const array TITLE_MALE = ['jarl', 'konungr', 'goði', 'hersir', 'lǫgsǫgumaðr', 'skáld', 'bóndi'];

    /** @var list<string> */
    protected const array TITLE_FEMALE = ['dróttning', 'húsfreyja', 'vǫlva', 'gyðja'];

    /** @var list<string> */
    protected const array SUFFIX = ['inn gamli', 'inn ungi'];

    /**
     * Younger Futhark (long-branch) for letters - one rune serves several sounds, double letters are written once
     *
     * @var array<string, string>
     */
    private const array RUNES = [
        'a' => 'ᛅ', 'á' => 'ᛅ', 'æ' => 'ᛅ', 'b' => 'ᛒ', 'p' => 'ᛒ', 'd' => 'ᛏ', 't' => 'ᛏ', 'ð' => 'ᚦ', 'þ' => 'ᚦ',
        'e' => 'ᛁ', 'é' => 'ᛁ', 'i' => 'ᛁ', 'í' => 'ᛁ', 'j' => 'ᛁ', 'f' => 'ᚠ', 'v' => 'ᚠ', 'g' => 'ᚴ', 'k' => 'ᚴ',
        'c' => 'ᚴ', 'q' => 'ᚴ', 'h' => 'ᚼ', 'l' => 'ᛚ', 'm' => 'ᛘ', 'n' => 'ᚾ', 'o' => 'ᚢ', 'ó' => 'ᚢ', 'ǫ' => 'ᚢ',
        'ø' => 'ᚢ', 'œ' => 'ᚢ', 'u' => 'ᚢ', 'ú' => 'ᚢ', 'w' => 'ᚢ', 'y' => 'ᚢ', 'ý' => 'ᚢ', 'r' => 'ᚱ', 's' => 'ᛋ',
        'z' => 'ᛋ', 'x' => 'ᚴᛋ',
    ];

    /**
     * Father's name in genitive with -son / -dóttir, e.g. "Eiríksson" / "Eiríksdóttir" - random gender when null
     */
    public function lastName(?Gender $gender = null): string
    {
        return $this->genitive($this->firstName(Gender::Male)) . match ($this->resolveGender($gender)) {
            Gender::Male => 'son',
            Gender::Female => 'dóttir',
        };
    }

    /**
     * Byname of given gender (random when null), e.g. "inn rauði" (the Red), "hárfagri" (Fair-hair), "blátǫnn"
     * (Bluetooth)
     */
    public function byname(?Gender $gender = null): string
    {
        $byname = $this->randomizer->randomElement(static::BYNAMES);

        return $this->resolveGender($gender) === Gender::Male ? $byname[0] : $byname[1];
    }

    /**
     * Genitive of a male name of the pack, e.g. "Bjǫrn" -> "Bjarnar"; other names get "s"
     */
    public function genitive(string $name): string
    {
        return static::GENITIVE[$name] ?? $name . 's';
    }

    /**
     * Text in Younger Futhark runes, words separated by "᛫" - of given text or of a random name: "Leifr Eiríksson" ->
     * "ᛚᛁᚠᚱ᛫ᛁᚱᛁᚴᛋᚢᚾ". Letters without a rune are left out.
     */
    public function runes(?string $text = null): string
    {
        $words = preg_split('/[^\p{L}]+/u', mb_strtolower($text ?? $this->name()), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return implode('᛫', array_map(static function (string $word): string {
            $runes = implode('', array_map(static fn (string $letter): string => self::RUNES[$letter] ?? '', mb_str_split($word)));

            // runes don't write double sounds - "Eiríksson" has one ᛋ, "ei" one ᛁ
            return (string) preg_replace('/(\X)\1+/u', '$1', $runes);
        }, $words));
    }
}
