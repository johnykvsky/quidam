<?php

declare(strict_types = 1);

namespace Quidam\DeadLanguagePack\got_IT;

use Quidam\Core\Person as CorePerson;
use Quidam\Definitions\Enum\Gender;

/**
 * Gothic names - a given name and the father's name in genitive, as Wulfila's Bible names "Iakobus sa Zaibaidaiaus"
 * (James, the one of Zebedee): "Þiudareiks sa Þiudameris", "Amalaswinþa so Þiudareikis"; Gothic alphabet apart
 */
class Person extends CorePerson
{
    use GothicAlphabetTrait;

    /**
     * Kings, bishops and scribes - strong names in -s, weak ones in -a
     *
     * @var list<string>
     */
    protected const array FIRST_NAME_MALE = [
        'Airmanareiks', 'Aiwareiks', 'Alareiks', 'Amalareiks', 'Ataulfs', 'Aþanareiks', 'Fritigairns', 'Gaina',
        'Hunimunds', 'Liubagilds', 'Merila', 'Sigisreiks', 'Swinþila', 'Teja', 'Totila', 'Tulga', 'Walamers', 'Wamba',
        'Widimers', 'Witiza', 'Wulfila', 'Þiudamers', 'Þiudareiks',
    ];

    /** @var list<string> */
    protected const array FIRST_NAME_FEMALE = [
        'Amalaberga', 'Amalafriþa', 'Amalaswinþa', 'Baddo', 'Gaiswinþa', 'Galswinþa', 'Þiudagutþa',
    ];

    /** @var list<string> */
    protected const array FIRST_NAME_NEUTRAL = [];

    /** @var list<string> */
    protected const array TITLE_MALE = ['þiudans', 'reiks', 'kindins', 'aipiskaupus', 'gudja', 'diakon', 'bokareis'];

    /** @var list<string> */
    protected const array TITLE_FEMALE = ['qens', 'magaþs', 'widuwo'];

    /**
     * Royal houses - "of the Amals", "of the Balths"
     *
     * @var list<string>
     */
    protected const array SUFFIX = ['us Amalam', 'us Balþam'];

    /**
     * Father's name in genitive after the article of the gender - "sa Þiudameris" / "so Þiudameris" (the one of
     * Þiudamers); random gender when null
     */
    public function lastName(?Gender $gender = null): string
    {
        return match ($this->resolveGender($gender)) {
            Gender::Male => 'sa ',
            Gender::Female => 'so ',
        } . $this->genitive($this->firstName(Gender::Male));
    }

    /**
     * Genitive of a male name - strong names in -s take -is ("Þiudareiks" -> "Þiudareikis"), weak ones in -a take
     * -ins ("Wulfila" -> "Wulfilins"); other names get "is"
     */
    public function genitive(string $name): string
    {
        return match (true) {
            str_ends_with($name, 's') => substr($name, 0, -1) . 'is',
            str_ends_with($name, 'a') => substr($name, 0, -1) . 'ins',
            default => $name . 'is',
        };
    }

    /**
     * Text in Wulfila's Gothic alphabet - of given text or of a random name: "Wulfila" -> "𐍅𐌿𐌻𐍆𐌹𐌻𐌰". Letters without
     * a Gothic one are left out.
     */
    public function gothic(?string $text = null): string
    {
        return $this->gothicLetters($text ?? $this->name());
    }
}
