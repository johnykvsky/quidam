<?php

declare(strict_types = 1);

namespace Quidam\LanguagePack\la_VA;

use Quidam\Core\Person as CorePerson;
use Quidam\Definitions\Enum\Gender;

/**
 * Roman names - men have the tria nomina, praenomen + nomen (the gens) + cognomen: "Gaius Iulius Caesar"; women the
 * feminine form of the nomen, with a personal name in front: "Tertia Cornelia"
 */
class Person extends CorePerson
{
    /**
     * Praenomina - the classical set, Gaius to Kaeso
     *
     * @var list<string>
     */
    protected const array FIRST_NAME_MALE = [
        'Appius', 'Aulus', 'Decimus', 'Faustus', 'Gaius', 'Gnaeus', 'Kaeso', 'Lucius', 'Mamercus', 'Manius', 'Marcus',
        'Numerius', 'Publius', 'Quintus', 'Servius', 'Sextus', 'Spurius', 'Tiberius', 'Titus', 'Vibius',
    ];

    /**
     * Personal names of women - order of birth (Tertia), feminine praenomina and diminutives (Drusilla)
     *
     * @var list<string>
     */
    protected const array FIRST_NAME_FEMALE = [
        'Prima', 'Secunda', 'Tertia', 'Quarta', 'Quinta', 'Maior', 'Minor', 'Paulla', 'Polla', 'Gaia', 'Lucia',
        'Marca', 'Publia', 'Postuma', 'Agrippina', 'Drusilla', 'Livilla', 'Faustina', 'Lucilla', 'Marcella',
        'Priscilla', 'Domitilla', 'Plotina', 'Sabina', 'Messalina',
    ];

    /** @var list<string> */
    protected const array FIRST_NAME_NEUTRAL = [];

    /**
     * Nomina gentilicia - all end in "-ius", Person and Company build the feminine and genitive forms from it
     *
     * @var list<string>
     */
    protected const array LAST_NAME = [
        'Aemilius', 'Antonius', 'Atilius', 'Aurelius', 'Caecilius', 'Calpurnius', 'Claudius', 'Cornelius', 'Domitius',
        'Fabius', 'Flavius', 'Furius', 'Horatius', 'Hortensius', 'Iulius', 'Iunius', 'Licinius', 'Livius', 'Lucretius',
        'Manlius', 'Octavius', 'Ovidius', 'Papirius', 'Plinius', 'Pompeius', 'Porcius', 'Postumius', 'Sempronius',
        'Sergius', 'Servilius', 'Sulpicius', 'Terentius', 'Tullius', 'Ulpius', 'Valerius', 'Vergilius', 'Vipsanius',
    ];

    /**
     * Cognomina - family branches and nicknames ("Rufus" - red-haired, "Naso" - big nose)
     *
     * @var list<string>
     */
    protected const array COGNOMEN = [
        'Agrippa', 'Albinus', 'Balbus', 'Brutus', 'Caesar', 'Calvus', 'Catulus', 'Cato', 'Celer', 'Cicero', 'Cotta',
        'Crassus', 'Dolabella', 'Drusus', 'Felix', 'Flaccus', 'Galba', 'Gracchus', 'Lentulus', 'Lepidus', 'Longus',
        'Magnus', 'Marcellus', 'Maximus', 'Metellus', 'Naso', 'Nasica', 'Nero', 'Niger', 'Paullus', 'Pius',
        'Regulus', 'Rufus', 'Scipio', 'Seneca', 'Severus', 'Strabo', 'Sulla', 'Varro',
    ];

    /** @var list<string> */
    protected const array TITLE_MALE = ['Consul', 'Praetor', 'Aedilis', 'Quaestor', 'Censor', 'Tribunus', 'Senator', 'Dominus'];

    /** @var list<string> */
    protected const array TITLE_FEMALE = ['Domina', 'Matrona', 'Virgo Vestalis', 'Flaminica'];

    /** @var list<string> */
    protected const array SUFFIX = ['Maior', 'Minor', 'Senior', 'Iunior'];

    /**
     * Nomen and cognomen for men ("Iulius Caesar"), the feminine nomen for women ("Iulia") - random gender when null
     */
    public function lastName(?Gender $gender = null): string
    {
        $nomen = $this->randomizer->randomElement(static::LAST_NAME);

        return match ($this->resolveGender($gender)) {
            Gender::Male => $nomen . ' ' . $this->cognomen(),
            Gender::Female => substr($nomen, 0, -2) . 'a',
        };
    }

    /**
     * e.g. "Cicero", "Rufus"
     */
    public function cognomen(): string
    {
        return $this->randomizer->randomElement(static::COGNOMEN);
    }
}
