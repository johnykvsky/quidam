<?php

declare(strict_types = 1);

namespace Quidam\DeadLanguagePack\akk_IQ;

use Quidam\Core\Company as CoreCompany;
use Quidam\Definitions\Enum\Gender;

/**
 * Mesopotamian businesses - Old Assyrian merchant houses ("bīt Pūšu-kēn"), "Ea-nāṣir u mārūšu" (and his sons),
 * "Nanni u tappûšu" (and his partner); trades as job titles, Hammurabi, Gilgameš and the oldest customer complaint as
 * catch phrases
 */
class Company extends CoreCompany
{
    /** @var list<string> - and his sons, and his partner, and his brothers */
    protected const array SUFFIX = ['u mārūšu', 'u tappûšu', 'u aḫḫūšu'];

    /** @var list<string> */
    protected const array JOB_TITLE = [
        'tamkārum', 'ṭupšarrum', 'bārûm', 'āšipum', 'asûm', 'nuḫatimmum', 'išparum', 'nappāḫum', 'naggārum',
        'malāḫum', 'nāqidum', 'ikkarum', 'sābûm', 'šāpirum', 'rabiānum', 'šangûm', 'kalûm', 'nāgirum', 'ummiānum',
    ];

    /** @var list<string> - silver, tin, textiles, sesame oil, beer, fields, copper, tablets, dates */
    protected const array INDUSTRY = [
        'Kaspum', 'Annakum', 'Ṣubātū', 'Šamaššammū', 'Šikarum', 'Eqlum', 'Erûm', 'Ṭuppū', 'Suluppū',
    ];

    /**
     * One list - catch phrases are lines of Hammurabi's laws and the Gilgameš epic, and the opening of Nanni's letter
     * to Ea-nāṣir, the copper merchant who sold him bad copper (c. 1750 BC)
     *
     * @var list<list<string>>
     */
    protected const array CATCH_PHRASE_WORDS = [[
        'Dannum enšam ana lā ḫabālim', 'Ša naqba īmuru', 'Šūtur eli šarrī',
        'Šumma awīlum īn mār awīlim uḫtappid, īnšu uḫappadū', 'Ana Ea-nāṣir qibīma, umma Nanni-ma',
    ]];

    /**
     * "Ea-nāṣir u mārūšu", "Nanni u Ea-nāṣir" or a merchant house: "bīt Pūšu-kēn"
     */
    public function company(): string
    {
        $name = $this->companyLastName();

        return match ($this->randomizer->getInt(0, 2)) {
            0 => $name . ' ' . $this->companySuffix(),
            1 => $name . ' u ' . $this->otherName($name),
            default => 'bīt ' . $name,
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
