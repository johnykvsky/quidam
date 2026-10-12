<?php

declare(strict_types = 1);

namespace Quidam\LanguagePack\la_VA;

use Quidam\Core\Company as CoreCompany;
use Quidam\Definitions\Enum\Gender;

/**
 * Roman businesses - "Cornelius et Filii", "Iulius et Tullius", "Fullonica Pompeii" (the fullery of Pompeius); trades,
 * fields and mottos as job titles, industries and catch phrases
 */
class Company extends CoreCompany
{
    /** @var list<string> */
    protected const array SUFFIX = ['et Filii', 'et Socii', 'et Fratres', 'et Liberti'];

    /**
     * Shops and workshops
     *
     * @var list<string>
     */
    protected const array NAME_WORD = [
        'Caupona', 'Fabrica', 'Figlina', 'Fullonica', 'Horrea', 'Officina', 'Pistrinum', 'Popina', 'Taberna',
        'Thermopolium',
    ];

    /** @var list<string> */
    protected const array JOB_TITLE = [
        'Advocatus', 'Agricola', 'Architectus', 'Argentarius', 'Auriga', 'Caupo', 'Coquus', 'Faber', 'Figulus',
        'Fullo', 'Gladiator', 'Grammaticus', 'Lanius', 'Librarius', 'Lictor', 'Medicus', 'Mercator', 'Miles', 'Nauta',
        'Pictor', 'Pistor', 'Praeco', 'Rhetor', 'Scriba', 'Sutor', 'Tabellarius', 'Textor', 'Tonsor', 'Vilicus',
    ];

    /** @var list<string> */
    protected const array INDUSTRY = [
        'Agricultura', 'Architectura', 'Ars culinaria', 'Ars textoria', 'Argentaria', 'Litterae', 'Ludi', 'Medicina',
        'Mercatura', 'Navigatio', 'Res metallica', 'Res militaris',
    ];

    /**
     * One list - catch phrases are mottos, whole
     *
     * @var list<list<string>>
     */
    protected const array CATCH_PHRASE_WORDS = [[
        'Ars longa, vita brevis', 'Audentes fortuna iuvat', 'Aut viam inveniam aut faciam', 'Carpe diem',
        'Citius, altius, fortius', 'Dum spiro, spero', 'Ex nihilo nihil fit', 'Festina lente', 'Fiat lux',
        'Labor omnia vincit', 'Mens sana in corpore sano', 'Nil desperandum', 'Non ducor, duco', 'Nulla dies sine linea',
        'Per aspera ad astra', 'Plus ultra', 'Primus inter pares', 'Sapere aude', 'Semper fidelis', 'Tempus fugit',
        'Veni, vidi, vici',
    ]];

    /**
     * "Cornelius et Filii", "Iulius et Tullius" or a shop of its owner, with the nomen in genitive: "Taberna Iulii"
     */
    public function company(): string
    {
        $nomen = $this->companyLastName();

        return match ($this->randomizer->getInt(0, 2)) {
            0 => $nomen . ' ' . $this->companySuffix(),
            1 => $nomen . ' et ' . $this->otherNomen($nomen),
            default => $this->randomizer->randomElement(static::NAME_WORD) . ' ' . preg_replace('/us$/', 'i', $nomen),
        };
    }

    /**
     * Nomen alone - "Cornelius et Filii", not "Cornelius Scipio et Filii"
     */
    protected function companyLastName(): string
    {
        return explode(' ', $this->person->lastName(Gender::Male))[0];
    }

    /**
     * A nomen other than $nomen - limited attempts, a replaced Person could always return the same name
     */
    private function otherNomen(string $nomen): string
    {
        $attempts = 0;
        do {
            $other = $this->companyLastName();
        } while ($other === $nomen && ++$attempts < 10);

        return $other;
    }
}
