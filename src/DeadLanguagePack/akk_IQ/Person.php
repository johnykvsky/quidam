<?php

declare(strict_types = 1);

namespace Quidam\DeadLanguagePack\akk_IQ;

use Quidam\Core\Person as CorePerson;
use Quidam\Definitions\Enum\Gender;

/**
 * Akkadian names, in the transcription of the handbooks - a given name and the father's name, as contracts write
 * them: "Ilī-iddinam mār Sîn-muballiṭ" (son of), "Lamassī mārat Pūšu-kēn" (daughter of); Sumerian names of kings and
 * heroes among them; cuneiform apart
 */
class Person extends CorePerson
{
    /**
     * Kings, merchants and heroes - many names are sentences: Ilī-iddinam "my god has given me", Sîn-muballiṭ "Sîn
     * keeps alive"
     *
     * @var list<string>
     */
    protected const array FIRST_NAME_MALE = [
        'Apil-Sîn', 'Aššur-bāni-apli', 'Awīl-Marduk', 'Ea-nāṣir', 'Enkidu', 'Gilgameš', 'Gudea', 'Ḫammurabi',
        'Iddin-Marduk', 'Ilī-iddinam', 'Ilšu-ibni', 'Marduk-nāṣir', 'Nabû-kudurri-uṣur', 'Nanni', 'Narām-Sîn',
        'Pūšu-kēn', 'Puzur-Aššur', 'Rīm-Sîn', 'Sîn-iddinam', 'Sîn-leqi-unninni', 'Sîn-muballiṭ', 'Šamaš-nāṣir',
        'Šamšī-Adad', 'Šarru-kīn', 'Šulgi', 'Šū-ilīšu', 'Ur-Nammu', 'Warad-Sîn', 'Yasmaḫ-Addu', 'Zimri-Līm',
    ];

    /** @var list<string> */
    protected const array FIRST_NAME_FEMALE = [
        'Aḫāssunu', 'Amat-Šamaš', 'Bēlessunu', 'Bēltani', 'Enḫeduanna', 'Erišti-Aya', 'Iltani', 'Ištar-lamassī',
        'Lamassī', 'Šammu-ramat', 'Šibtu', 'Tarām-Kūbi', 'Ummī-ṭābat',
    ];

    /** @var list<string> */
    protected const array FIRST_NAME_NEUTRAL = [];

    /** @var list<string> - king, governor, temple administrator, scribe, merchant, mayor, diviner, exorcist */
    protected const array TITLE_MALE = ['šarrum', 'šakkanakkum', 'šangûm', 'ṭupšarrum', 'tamkārum', 'rabiānum', 'bārûm', 'āšipum'];

    /** @var list<string> - queen, high priestess, cloistered woman, ale-wife */
    protected const array TITLE_FEMALE = ['šarratum', 'ēntum', 'nadītum', 'sābītum'];

    /** @var list<string> - the elder, the younger */
    protected const array SUFFIX = ['rabûm', 'ṣeḫrum'];

    /**
     * Syllable signs as consonant => [a, e, i, u] - one sign per syllable, of the many the scribes had
     *
     * @var array<string, array{string, string, string, string}>
     */
    private const array CV = [
        '' => ['𒀀', '𒂊', '𒄿', '𒌑'],
        'b' => ['𒁀', '𒁁', '𒁉', '𒁍'], 'p' => ['𒉺', '𒉿', '𒉿', '𒁍'],
        'd' => ['𒁕', '𒁲', '𒁲', '𒁺'], 't' => ['𒋫', '𒋼', '𒋾', '𒌅'], 'ṭ' => ['𒁕', '𒁲', '𒁲', '𒁺'],
        'g' => ['𒂵', '𒄀', '𒄀', '𒄖'], 'k' => ['𒅗', '𒆠', '𒆠', '𒆪'], 'q' => ['𒋡', '𒆠', '𒆠', '𒆪'],
        'h' => ['𒄩', '𒄭', '𒄭', '𒄷'], 'ḫ' => ['𒄩', '𒄭', '𒄭', '𒄷'], 'l' => ['𒆷', '𒇷', '𒇷', '𒇻'], 'm' => ['𒈠', '𒈨', '𒈪', '𒈬'],
        'n' => ['𒈾', '𒉈', '𒉌', '𒉡'], 'r' => ['𒊏', '𒊑', '𒊑', '𒊒'], 's' => ['𒊓', '𒋛', '𒋛', '𒋢'],
        'ṣ' => ['𒍝', '𒍣', '𒍣', '𒍪'], 'z' => ['𒍝', '𒍣', '𒍣', '𒍪'], 'š' => ['𒊭', '𒊺', '𒅆', '𒋗'],
        'w' => ['𒉿', '𒉿', '𒉿', '𒉿'], 'y' => ['𒅀', '𒅀', '𒅀', '𒅀'],
    ];

    /**
     * Syllable signs as consonant => [a, e, i, u] for vowel + consonant - "ar", "in", "uš"
     *
     * @var array<string, array{string, string, string, string}>
     */
    private const array VC = [
        'b' => ['𒀊', '𒅁', '𒅁', '𒌒'], 'p' => ['𒀊', '𒅁', '𒅁', '𒌒'],
        'd' => ['𒀜', '𒀉', '𒀉', '𒌓'], 't' => ['𒀜', '𒀉', '𒀉', '𒌓'], 'ṭ' => ['𒀜', '𒀉', '𒀉', '𒌓'],
        'g' => ['𒀝', '𒅅', '𒅅', '𒊌'], 'k' => ['𒀝', '𒅅', '𒅅', '𒊌'], 'q' => ['𒀝', '𒅅', '𒅅', '𒊌'],
        'h' => ['𒄴', '𒄴', '𒄴', '𒄴'], 'ḫ' => ['𒄴', '𒄴', '𒄴', '𒄴'], 'l' => ['𒀠', '𒂖', '𒅋', '𒌌'], 'm' => ['𒄠', '𒅎', '𒅎', '𒌝'],
        'n' => ['𒀭', '𒂗', '𒅔', '𒌦'], 'r' => ['𒅈', '𒅕', '𒅕', '𒌨'], 's' => ['𒊍', '𒄑', '𒄑', '𒊻'],
        'ṣ' => ['𒊍', '𒄑', '𒄑', '𒊻'], 'z' => ['𒊍', '𒄑', '𒄑', '𒊻'], 'š' => ['𒀸', '𒅖', '𒅖', '𒍑'],
    ];

    /** @var array<string, int> vowels - long ones are written as short */
    private const array VOWELS = [
        'a' => 0, 'ā' => 0, 'â' => 0, 'e' => 1, 'ē' => 1, 'ê' => 1, 'i' => 2, 'ī' => 2, 'î' => 2, 'u' => 3, 'ū' => 3,
        'û' => 3,
    ];

    /**
     * Father's name after "mār" (son of) or "mārat" (daughter of), e.g. "mār Sîn-muballiṭ" - random gender when null
     */
    public function lastName(?Gender $gender = null): string
    {
        return match ($this->resolveGender($gender)) {
            Gender::Male => 'mār ',
            Gender::Female => 'mārat ',
        } . $this->firstName(Gender::Male);
    }

    /**
     * Text in cuneiform, words separated by spaces - of given text or of a random name: "Šarru-kīn" -> "𒊭𒅈𒊒𒆠𒅔"
     * (ša-ar-ru-ki-in). Words are split into syllables as scribes did - consonant + vowel, and vowel + consonant
     * before another consonant or at the end; letters without a sign are left out.
     */
    public function cuneiform(?string $text = null): string
    {
        $words = preg_split('/[^\p{L}\-ʾ\']+/u', mb_strtolower($text ?? $this->name()), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return implode(' ', array_filter(array_map($this->signs(...), $words)));
    }

    /**
     * Signs of one word - hyphens, alephs and letters without a sign are dropped first
     */
    private function signs(string $word): string
    {
        $sounds = array_values(array_filter(
            mb_str_split($word),
            static fn (string $sound): bool => isset(self::VOWELS[$sound]) || isset(self::CV[$sound]),
        ));
        $signs = '';
        $count = count($sounds);

        for ($i = 0; $i < $count;) {
            $consonant = isset(self::VOWELS[$sounds[$i]]) ? '' : $sounds[$i];
            $vowelAt = $consonant === '' ? $i : $i + 1;

            if (!isset($sounds[$vowelAt], self::VOWELS[$sounds[$vowelAt]])) {
                // a consonant without a vowel - only in clusters scribes would break with a vowel; skipped
                ++$i;
                continue;
            }

            $vowel = self::VOWELS[$sounds[$vowelAt]];
            $coda = $sounds[$vowelAt + 1] ?? null;
            $next = $sounds[$vowelAt + 2] ?? null;
            // a closed syllable - the consonant ends the word or another consonant follows
            $closed = $coda !== null && isset(self::VC[$coda]) && ($next === null || !isset(self::VOWELS[$next]));

            // "ur" is one sign, "šar" is two: ša-ar
            $signs .= $consonant === '' && $closed ? '' : self::CV[$consonant][$vowel] ?? '';
            $signs .= $closed ? self::VC[$coda][$vowel] : '';
            $i = $vowelAt + ($closed ? 2 : 1);
        }

        return $signs;
    }
}
