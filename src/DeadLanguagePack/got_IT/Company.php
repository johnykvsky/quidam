<?php

declare(strict_types = 1);

namespace Quidam\DeadLanguagePack\got_IT;

use Quidam\Core\Company as CoreCompany;
use Quidam\Definitions\Enum\Gender;

/**
 * Gothic businesses - "Totila jah sunjus is" (and his sons), "Teja jah Wamba", "Weinagards Ataulfis" (Ataulf's
 * vineyard); trades of Wulfila's Bible and of the Gothic deeds of Naples and Arezzo as job titles, the Lord's Prayer
 * as catch phrases
 */
class Company extends CoreCompany
{
    /** @var list<string> */
    protected const array SUFFIX = ['jah sunjus is', 'jah broþrjus is', 'jah gasinþans is'];

    /**
     * House, vineyard, garden, village and ship - followed by the owner's name in genitive
     *
     * @var list<string>
     */
    protected const array NAME_WORD = ['Gards', 'Weinagards', 'Aurtigards', 'Haims', 'Skip'];

    /** @var list<string> */
    protected const array JOB_TITLE = [
        'aipiskaupus', 'bokareis', 'diakon', 'fiskja', 'gudja', 'hairdeis', 'kasja', 'kindins', 'laisareis', 'lekeis',
        'motareis', 'sinista', 'skalks', 'smiþa', 'timrja', 'waurstwja', 'weinagardja', 'wullareis',
    ];

    /** @var list<string> - work, books, wine, fish, grain, bread, ships, gold, silver, iron */
    protected const array INDUSTRY = [
        'Waurstw', 'Bokos', 'Wein', 'Fiskos', 'Kaurn', 'Hlaifs', 'Skipa', 'Gulþ', 'Silubr', 'Eisarn',
    ];

    /**
     * One list - catch phrases are lines of the Lord's Prayer (Matthew 6:9-13), and the Gothic toast a Latin poem
     * quoted: "let us make to eat and drink"
     *
     * @var list<list<string>>
     */
    protected const array CATCH_PHRASE_WORDS = [[
        'Atta unsar þu in himinam', 'Weihnai namo þein', 'Qimai þiudinassus þeins', 'Wairþai wilja þeins',
        'Hlaif unsarana þana sinteinan gif uns himma daga', 'Jah ni briggais uns in fraistubnjai',
        'Unte þeina ist þiudangardi jah mahts jah wulþus in aiwins', 'Skapjam matjan jah drigkan',
    ]];

    /**
     * "Totila jah sunjus is", "Teja jah Wamba" or a business of its owner, name in genitive: "Weinagards Ataulfis"
     */
    public function company(): string
    {
        $name = $this->companyLastName();

        return match ($this->randomizer->getInt(0, 2)) {
            0 => $name . ' ' . $this->companySuffix(),
            1 => $name . ' jah ' . $this->otherName($name),
            default => $this->randomizer->randomElement(static::NAME_WORD) . ' '
                . ($this->person instanceof Person ? $this->person->genitive($name) : $name),
        };
    }

    /**
     * Second names are the father's - businesses carry the owner's own name
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
