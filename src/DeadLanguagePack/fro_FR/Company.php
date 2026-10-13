<?php

declare(strict_types = 1);

namespace Quidam\DeadLanguagePack\fro_FR;

use Quidam\Core\Company as CoreCompany;
use Quidam\Definitions\Enum\Gender;

/**
 * Medieval businesses - "Jehan le Fevre et si fil" (and his sons), "Aelis la Merciere et Huon de Troies", the crafts
 * of Étienne Boileau's Livre des métiers ("le mestier des Talemeliers"); court offices and trades as job titles, lines
 * of the Chanson de Roland and other early French texts as catch phrases
 */
class Company extends CoreCompany
{
    /** @var list<string> */
    protected const array SUFFIX = ['et si fil', 'et si compaignon', 'et si frere'];

    /**
     * Crafts of Paris with statutes of their own (1268)
     *
     * @var list<string>
     */
    protected const array NAME_WORD = [
        'Talemeliers', 'Fevres', 'Orfevres', 'Drapiers', 'Pelletiers', 'Merciers', 'Tisserans', 'Cordoaniers',
        'Chandeliers', 'Tonneliers', 'Barbiers',
    ];

    /** @var list<string> */
    protected const array JOB_TITLE = [
        'chevalier', 'escuier', 'seneschal', 'conestable', 'chamberlenc', 'bouteillier', 'bailli', 'prevost', 'clerc',
        'jogleor', 'trovere', 'menestrel', 'mire', 'marcheant', 'fevre', 'charpentier', 'talemelier', 'tavernier',
        'moine', 'chanoine', 'vilain',
    ];

    /** @var list<string> */
    protected const array INDUSTRY = ['Draperie', 'Vins', 'Sel', 'Pelleterie', 'Orfaverie', 'Marcheandise', 'Chevalerie', 'Clergie'];

    /**
     * One list - catch phrases are lines of the Strasbourg Oaths (842), the Chanson de Roland, Marie de France and
     * Aucassin et Nicolette, whole
     *
     * @var list<list<string>>
     */
    protected const array CATCH_PHRASE_WORDS = [[
        'Pro Deo amur et pro christian poblo', 'Carles li reis, nostre emperere magnes',
        'Halt sunt li pui e li val tenebrus', 'Paien unt tort e chrestiens unt dreit', 'Rollant est proz e Oliver est sage',
        'Ne vus sanz mei, ne jo sanz vus', 'Qui vauroit bons vers oïr',
    ]];

    /**
     * "Jehan le Fevre et si fil", "Aelis la Merciere et Huon de Troies" or a craft: "le mestier des Talemeliers"
     */
    public function company(): string
    {
        return match ($this->randomizer->getInt(0, 2)) {
            0 => $this->person->name(Gender::Male) . ' ' . $this->companySuffix(),
            1 => $this->person->name() . ' et ' . $this->person->name(),
            default => 'le mestier des ' . $this->randomizer->randomElement(static::NAME_WORD),
        };
    }
}
