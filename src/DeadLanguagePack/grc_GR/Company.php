<?php

declare(strict_types = 1);

namespace Quidam\DeadLanguagePack\grc_GR;

use Quidam\Core\Company as CoreCompany;
use Quidam\Definitions\Enum\Gender;

/**
 * Greek businesses - "Kimon kai Hyioi", "Kimon kai Lykon", "Trapeza Kephalou" (the bank of Kephalos); trades, fields
 * and maxims as job titles, industries and catch phrases
 */
class Company extends CoreCompany
{
    /** @var list<string> */
    protected const array SUFFIX = ['kai Hyioi', 'kai Koinonoi', 'kai Adelphoi'];

    /**
     * Banks, shops and workshops
     *
     * @var list<string>
     */
    protected const array NAME_WORD = [
        'Bibliopoleion', 'Chalkeion', 'Ergasterion', 'Kapeleion', 'Kerameion', 'Myropoleion', 'Palaistra', 'Trapeza',
    ];

    /** @var list<string> */
    protected const array JOB_TITLE = [
        'Agalmatopoios', 'Architekton', 'Auletes', 'Chalkeus', 'Emporos', 'Georgos', 'Gnapheus', 'Grammateus', 'Halieus',
        'Hoplites', 'Iatros', 'Kapelos', 'Kerameus', 'Kitharodos', 'Mageiros', 'Naukleros', 'Nautes', 'Paidagogos',
        'Rhetor', 'Skytotomos', 'Sophistes', 'Tekton', 'Toxotes', 'Trapezites', 'Zographos',
    ];

    /** @var list<string> */
    protected const array INDUSTRY = [
        'Architektonike', 'Emporia', 'Georgia', 'Iatrike', 'Kerameike', 'Metalleia', 'Mousike', 'Nautilia',
        'Philosophia', 'Polemike', 'Rhetorike', 'Theatron',
    ];

    /**
     * One list - catch phrases are maxims, whole
     *
     * @var list<list<string>>
     */
    protected const array CATCH_PHRASE_WORDS = [[
        'Aien aristeuein', 'Arche hemisy pantos', 'Chalepa ta kala', 'E tan e epi tas', 'Gnothi seauton',
        'Ho bios brachys, he de techne makre', 'Ktema es aiei', 'Mega biblion mega kakon', 'Meden agan',
        'Metron ariston', 'Molon labe', 'Panta rhei', 'Speude bradeos', 'Eureka',
    ]];

    /**
     * "Kimon kai Hyioi", "Kimon kai Lykon" or a business of its owner, name in genitive: "Trapeza Kimonos"
     */
    public function company(): string
    {
        $name = $this->companyLastName();

        return match ($this->randomizer->getInt(0, 2)) {
            0 => $name . ' ' . $this->companySuffix(),
            1 => $name . ' kai ' . $this->otherName($name),
            default => $this->randomizer->randomElement(static::NAME_WORD) . ' '
                . ($this->person instanceof Person ? $this->person->genitive($name) : $name),
        };
    }

    /**
     * Greeks had no family names - businesses carry the owner's own name
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
