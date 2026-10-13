<?php

declare(strict_types = 1);

namespace Quidam\DeadLanguagePack\fro_FR;

use Quidam\Core\Person as CorePerson;
use Quidam\Definitions\Enum\Gender;

/**
 * Old French names - heroes of the chansons de geste and the Arthurian romances, and names of the Paris tax rolls;
 * a byname by place ("Chrestien de Troies"), trade ("Jehan le Fevre", "Aelis la Merciere") or looks ("Huon le Blont",
 * "Aude la Blonde"). Names are in the object case, the one modern French kept (Rollant, not Rollanz).
 */
class Person extends CorePerson
{
    /** @var list<string> */
    protected const array FIRST_NAME_MALE = [
        'Aimeri', 'Artus', 'Aubri', 'Bertran', 'Carles', 'Chrestien', 'Erec', 'Estienne', 'Gauvain', 'Gautier',
        'Girart', 'Godefroi', 'Guillaume', 'Huon', 'Jehan', 'Keu', 'Lancelot', 'Naimon', 'Ogier', 'Olivier',
        'Perceval', 'Pierre', 'Raoul', 'Renart', 'Robert', 'Rollant', 'Thibaut', 'Thierri', 'Tristran', 'Turpin',
        'Vivien', 'Yvain',
    ];

    /** @var list<string> */
    protected const array FIRST_NAME_FEMALE = [
        'Aelis', 'Agnes', 'Alienor', 'Ameline', 'Aude', 'Berte', 'Blanche', 'Blancheflor', 'Enide', 'Ermengart',
        'Fenice', 'Guenievre', 'Heloïs', 'Jehanne', 'Laudine', 'Lunete', 'Mahaut', 'Marguerite', 'Marie', 'Nicolete',
        'Perrette', 'Soredamors', 'Ysabel', 'Yseut',
    ];

    /** @var list<string> */
    protected const array FIRST_NAME_NEUTRAL = [];

    /**
     * Bynames by place - the same for women and men
     *
     * @var list<string>
     */
    protected const array LAST_NAME = [
        'de Troies', 'de Champaigne', 'de Paris', 'de Rains', 'd\'Orliens', 'de Chartres', 'de Biaucaire',
        'de Narbone', 'de Monpellier', 'de Loon', 'de Biauvais', 'du Pont', 'de la Fontaine', 'du Bois',
    ];

    /**
     * Bynames by trade and looks as [masculine, feminine] - "le Mercier" / "la Merciere"
     *
     * @var list<array{string, string}>
     */
    protected const array BYNAMES = [
        ['le Fevre', 'la Fevresse'], ['le Mercier', 'la Merciere'], ['le Barbier', 'la Barbiere'],
        ['le Pelletier', 'la Pelletiere'], ['le Tisserant', 'la Tisserande'], ['le Talemelier', 'la Talemeliere'],
        ['le Tavernier', 'la Taverniere'], ['le Charpentier', 'la Charpentiere'], ['le Blont', 'la Blonde'],
        ['le Petit', 'la Petite'], ['le Grant', 'la Grant'], ['le Roux', 'la Rousse'], ['le Sage', 'la Sage'],
    ];

    /** @var list<string> */
    protected const array TITLE_MALE = ['sire', 'messire', 'mestre', 'frere', 'dant', 'cuens', 'dus', 'rois'];

    /** @var list<string> */
    protected const array TITLE_FEMALE = ['dame', 'madame', 'damoisele', 'suer', 'abeesse', 'contesse', 'reïne'];

    /** @var list<string> */
    protected const array SUFFIX = ['le Viel', 'le Jone'];

    /**
     * Byname by place (both genders) or by trade or looks in the form of the gender, e.g. "de Troies", "le Mercier" /
     * "la Merciere" - random gender when null
     */
    public function lastName(?Gender $gender = null): string
    {
        $gender = $this->resolveGender($gender);

        if ($this->randomizer->getBool()) {
            return $this->randomizer->randomElement(static::LAST_NAME);
        }

        $byname = $this->randomizer->randomElement(static::BYNAMES);

        return $gender === Gender::Male ? $byname[0] : $byname[1];
    }
}
