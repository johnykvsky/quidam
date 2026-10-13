<?php

declare(strict_types = 1);

namespace Quidam\DeadLanguagePack\non_IS;

use Quidam\Core\Company as CoreCompany;
use Quidam\Definitions\Enum\Gender;

/**
 * Norse businesses - "Egill ok synir" (and sons), "Gunnarr ok Njáll", "Smiðja Egils" (Egill's smithy); trades, fields
 * and lines of the Hávamál as job titles, industries and catch phrases
 */
class Company extends CoreCompany
{
    /** @var list<string> */
    protected const array SUFFIX = ['ok synir', 'ok félagar', 'ok brœðr'];

    /**
     * Farms, workshops and ships - followed by the owner's name in genitive
     *
     * @var list<string>
     */
    protected const array NAME_WORD = ['Bú', 'Kaupskip', 'Naust', 'Skáli', 'Skipasmiðja', 'Smiðja'];

    /** @var list<string> */
    protected const array JOB_TITLE = [
        'berserkr', 'bóndi', 'fiskimaðr', 'goði', 'hersir', 'hirðmaðr', 'kaupmaðr', 'læknir', 'lǫgsǫgumaðr',
        'rúnameistari', 'skáld', 'skipasmiðr', 'smiðr', 'stýrimaðr', 'vefari', 'veiðimaðr', 'víkingr', 'vǫlva',
    ];

    /** @var list<string> */
    protected const array INDUSTRY = [
        'Fiskveiðar', 'Járnsmíð', 'Kaupskapr', 'Lǫg', 'Skáldskapr', 'Skipasmíð', 'Vefnaðr', 'Víking',
    ];

    /**
     * One list - catch phrases are lines of the Hávamál (and one of the law), whole
     *
     * @var list<list<string>>
     */
    protected const array CATCH_PHRASE_WORDS = [[
        'Bú er betra, þótt lítit sé', 'Deyr fé, deyja frændr', 'Gáttir allar áðr gangi fram',
        'Með lǫgum skal land byggja', 'Orðstírr deyr aldregi', 'Sá einn veit er víða ratar',
        'Ósnjallr maðr hyggsk munu ey lifa',
    ]];

    /**
     * "Egill ok synir", "Gunnarr ok Njáll" or a business of its owner, name in genitive: "Smiðja Egils"
     */
    public function company(): string
    {
        $name = $this->companyLastName();

        return match ($this->randomizer->getInt(0, 2)) {
            0 => $name . ' ' . $this->companySuffix(),
            1 => $name . ' ok ' . $this->otherName($name),
            default => $this->randomizer->randomElement(static::NAME_WORD) . ' '
                . ($this->person instanceof Person ? $this->person->genitive($name) : $name),
        };
    }

    /**
     * Patronymics change every generation - businesses carry the owner's own name
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
