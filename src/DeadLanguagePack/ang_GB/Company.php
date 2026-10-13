<?php

declare(strict_types = 1);

namespace Quidam\DeadLanguagePack\ang_GB;

use Quidam\Core\Company as CoreCompany;
use Quidam\Definitions\Enum\Gender;

/**
 * Anglo-Saxon businesses - "Ælfrēd and his suna" (and his sons), "Wulfstān and Ēadgār", "Medoheall Ælfrēdes"
 * (Ælfrēd's mead hall); trades, crafts and lines of Old English poetry as job titles, industries and catch phrases
 */
class Company extends CoreCompany
{
    /** @var list<string> */
    protected const array SUFFIX = ['and his suna', 'and his gefēran', 'and his brōþru'];

    /**
     * Workplaces - followed by the owner's name in genitive
     *
     * @var list<string>
     */
    protected const array NAME_WORD = ['Cēapstōw', 'Ealahūs', 'Medoheall', 'Mylen', 'Smiþþe'];

    /** @var list<string> */
    protected const array JOB_TITLE = [
        'bæcere', 'bōcere', 'cēapmann', 'cyrcweard', 'fiscere', 'gerēfa', 'hunta', 'ierþling', 'lǣce', 'mynetere',
        'sǣmann', 'scēaphyrde', 'scop', 'smiþ', 'þegn', 'wealhstōd',
    ];

    /** @var list<string> */
    protected const array INDUSTRY = ['Bōccræft', 'Cēapung', 'Eorþtilþ', 'Fiscoþ', 'Lǣcecræft', 'Smiþcræft', 'Wīgcræft'];

    /**
     * One list - catch phrases are lines of Old English poetry, whole
     *
     * @var list<list<string>>
     */
    protected const array CATCH_PHRASE_WORDS = [[
        'Hwæt!', 'Wyrd bið ful ārǣd', 'Gǣð ā wyrd swā hīo scel', 'Swā sceal man dōn',
        'Þæs ofereode, þisses swā mæg', 'Hige sceal þē heardra, heorte þē cēnre',
        'Wyrd oft nereð unfǣgne eorl, þonne his ellen dēah',
    ]];

    /**
     * "Ælfrēd and his suna", "Wulfstān and Ēadgār" or a workplace of its owner: "Smiþþe Ælfrēdes"
     */
    public function company(): string
    {
        $name = $this->companyLastName();

        return match ($this->randomizer->getInt(0, 2)) {
            0 => $name . ' ' . $this->companySuffix(),
            1 => $name . ' and ' . $this->otherName($name),
            default => $this->randomizer->randomElement(static::NAME_WORD) . ' '
                . ($this->person instanceof Person ? $this->person->genitive($name) : $name),
        };
    }

    /**
     * No family names - businesses carry the owner's own name
     */
    protected function companyLastName(): string
    {
        return $this->person->firstName(Gender::Male);
    }

    /**
     * A name other than $name - limited attempts, a replaced Person could always return the same name
     */
    private function otherName(string $name): string
    {
        $attempts = 0;
        do {
            $other = $this->companyLastName();
        } while ($other === $name && ++$attempts < 10);

        return $other;
    }
}
