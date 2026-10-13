<?php

declare(strict_types = 1);

namespace Quidam\DeadLanguagePack\en_XS;

use Quidam\Core\Company as CoreCompany;

/**
 * Elizabethan businesses - players' companies named after their patron ("The Lord Burbage's Men"), partnerships
 * ("Heminges & Condell") and taverns ("The Mermaid Tavern"); trades of the plays, lines of Shakespeare as catch
 * phrases
 */
class Company extends CoreCompany
{
    /** @var list<string> */
    protected const array SUFFIX = ["'s Men", "'s Players", "'s Servants"];

    /**
     * Tavern signs - "The Mermaid Tavern"
     *
     * @var list<string>
     */
    protected const array NAME_WORD = ['Mermaid', "Boar's Head", 'Garter', 'Bell', 'Red Lion', 'Swan', 'Three Tuns', 'Angel'];

    /**
     * Trades - many of them of characters (Bottom the weaver, Quince the carpenter, Dogberry the constable)
     *
     * @var list<string>
     */
    protected const array JOB_TITLE = [
        'Player', 'Playwright', 'Groundling', 'Stationer', 'Printer', 'Glover', 'Apothecary', 'Tinker', 'Weaver',
        'Bellows-mender', 'Joiner', 'Tailor', 'Carpenter', 'Constable', 'Fool', 'Gravedigger', 'Nurse', 'Herald',
        'Page', 'Tapster',
    ];

    /** @var list<string> */
    protected const array INDUSTRY = [
        'The Theatre', 'Printing', 'Bear-baiting', 'Glove-making', 'Wool', 'Brewing', 'Navigation', 'Tavern-keeping',
    ];

    /**
     * One list - catch phrases are lines of Shakespeare, whole
     *
     * @var list<list<string>>
     */
    protected const array CATCH_PHRASE_WORDS = [[
        "All the world's a stage", 'Brevity is the soul of wit', 'To thine own self be true', "The play's the thing",
        "All's well that ends well", 'Neither a borrower nor a lender be', 'The course of true love never did run smooth',
        'We know what we are, but know not what we may be', "What's done is done", 'Once more unto the breach',
        'Lord, what fools these mortals be!', 'Parting is such sweet sorrow',
        "Though this be madness, yet there is method in't",
    ]];

    /**
     * "The Lord Burbage's Men", "Heminges & Condell" or "The Mermaid Tavern"
     */
    public function company(): string
    {
        $name = $this->companyLastName();

        return match ($this->randomizer->getInt(0, 2)) {
            0 => 'The Lord ' . $name . $this->companySuffix(),
            1 => $name . ' & ' . $this->otherName($name),
            default => 'The ' . $this->randomizer->randomElement(static::NAME_WORD) . ' Tavern',
        };
    }

    /**
     * A surname other than $name - limited attempts, a replaced Person could always return the same name
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
