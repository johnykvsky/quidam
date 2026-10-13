<?php

declare(strict_types = 1);

namespace Quidam\DeadLanguagePack\tlh_QO;

use Quidam\Core\Company as CoreCompany;
use Quidam\Definitions\Enum\Gender;

/**
 * Businesses of the Empire - "Torvek's Bloodwine Cellar", "House of Kargh Shipyards", "Duvok & Varok"; warrior trades,
 * industries and battle cries as catch phrases
 */
class Company extends CoreCompany
{
    /** @var list<string> */
    protected const array SUFFIX = ['Shipyards', 'Armaments', 'Mining', 'Trading', 'Holdings'];

    /**
     * Shops - "Torvek's Gagh Bar"
     *
     * @var list<string>
     */
    protected const array NAME_WORD = [
        'Armory', "Bat'leth Forge", 'Bloodwine Cellar', 'Gagh Bar', 'Opera House', 'Raktajino Stand', 'Targ Farm',
        'Trophy Hall', 'Weapons Shop',
    ];

    /** @var list<string> */
    protected const array JOB_TITLE = [
        'Arbiter of Succession', 'Armorer', "Bat'leth Smith", 'Battle Poet', 'Bloodwine Brewer', 'Captain',
        'Chief Engineer', 'Council Advocate', 'Dahar Master', 'Dilithium Miner', 'First Officer', 'Gagh Chef',
        'Helmsman', 'Opera Singer', 'Ship Surgeon', 'Targ Handler', 'Warrior', 'Weapons Officer',
    ];

    /** @var list<string> */
    protected const array INDUSTRY = [
        'Battle Poetry', 'Bloodwine Brewing', 'Dilithium Mining', 'Opera', 'Shipbuilding', 'Targ Breeding',
        'Trophy Taxidermy', 'Warfare', 'Weapons Forging',
    ];

    /**
     * One list - catch phrases are battle cries, whole
     *
     * @var list<list<string>>
     */
    protected const array CATCH_PHRASE_WORDS = [[
        "Qapla'!", 'Today is a good day to die', 'Victory or a glorious death', 'Honor before all',
        'Strike first, strike hard', 'Glory to the House', 'No retreat, no surrender', 'Our blades never rust',
        'Death before dishonor', 'Only the strong survive', 'A song for every battle',
        'Revenge is a dish best served cold',
    ]];

    /**
     * "Torvek's Bloodwine Cellar", "House of Kargh Shipyards" or "Duvok & Varok"
     */
    public function company(): string
    {
        $name = $this->companyLastName();

        return match ($this->randomizer->getInt(0, 2)) {
            0 => $name . "'s " . $this->randomizer->randomElement(static::NAME_WORD),
            1 => 'House of ' . $name . ' ' . $this->companySuffix(),
            default => $name . ' & ' . $this->otherName($name),
        };
    }

    /**
     * Klingons carry no family names - businesses carry the founder's own
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
