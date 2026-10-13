<?php

declare(strict_types = 1);

namespace Quidam\DeadLanguagePack\grc_GR;

use Quidam\Core\Person as CorePerson;
use Quidam\Definitions\Enum\Gender;

/**
 * Athenian names, in the usual Latin transliteration - a citizen is named with his father's name in genitive and his
 * deme: "Sokrates Sophroniskou Alopekethen" (Sokrates, son of Sophroniskos, of Alopeke); a woman with her father's:
 * "Hegeso Proxenou"
 */
class Person extends CorePerson
{
    /** @var list<string> */
    protected const array FIRST_NAME_MALE = [
        'Agathon', 'Aischines', 'Aischylos', 'Alkibiades', 'Andokides', 'Antiphon', 'Anytos', 'Aristeides', 'Ariston',
        'Aristophanes', 'Chabrias', 'Charmides', 'Demades', 'Demosthenes', 'Epikuros', 'Eukleides', 'Euphorion',
        'Euripides', 'Gryllos', 'Hipponikos', 'Hippokrates', 'Hypereides', 'Iphikrates', 'Isokrates', 'Kallias',
        'Kephalos', 'Kimon', 'Kleainetos', 'Kleisthenes', 'Kleon', 'Konon', 'Kritias', 'Laches', 'Lykon', 'Lykurgos',
        'Lysias', 'Lysimachos', 'Meletos', 'Menandros', 'Miltiades', 'Mnesarchos', 'Neokles', 'Nikeratos', 'Nikias',
        'Nikomachos', 'Perikles', 'Phaidros', 'Pheidias', 'Philippos', 'Phokion', 'Platon', 'Polemarchos', 'Proxenos',
        'Sokrates', 'Solon', 'Sophokles', 'Sophroniskos', 'Themistokles', 'Thrasybulos', 'Thukydides', 'Timotheos',
        'Xanthippos', 'Xenophon',
    ];

    /**
     * Genitive of every male name - the patronymic: "Sophroniskou" (of Sophroniskos), "Perikleous", "Xenophontos"
     *
     * @var array<string, string>
     */
    protected const array GENITIVE = [
        'Agathon' => 'Agathonos', 'Aischines' => 'Aischinou', 'Aischylos' => 'Aischylou', 'Alkibiades' => 'Alkibiadou',
        'Andokides' => 'Andokidou', 'Antiphon' => 'Antiphontos', 'Anytos' => 'Anytou', 'Aristeides' => 'Aristeidou',
        'Ariston' => 'Aristonos', 'Aristophanes' => 'Aristophanous', 'Chabrias' => 'Chabriou',
        'Charmides' => 'Charmidou', 'Demades' => 'Demadou', 'Demosthenes' => 'Demosthenous', 'Epikuros' => 'Epikurou',
        'Eukleides' => 'Eukleidou', 'Euphorion' => 'Euphorionos', 'Euripides' => 'Euripidou', 'Gryllos' => 'Gryllou',
        'Hipponikos' => 'Hipponikou', 'Hippokrates' => 'Hippokratous', 'Hypereides' => 'Hypereidou',
        'Iphikrates' => 'Iphikratous', 'Isokrates' => 'Isokratous', 'Kallias' => 'Kalliou', 'Kephalos' => 'Kephalou',
        'Kimon' => 'Kimonos', 'Kleainetos' => 'Kleainetou', 'Kleisthenes' => 'Kleisthenous', 'Kleon' => 'Kleonos',
        'Konon' => 'Kononos', 'Kritias' => 'Kritiou', 'Laches' => 'Lachetos', 'Lykon' => 'Lykonos',
        'Lykurgos' => 'Lykurgou', 'Lysias' => 'Lysiou', 'Lysimachos' => 'Lysimachou', 'Meletos' => 'Meletou',
        'Menandros' => 'Menandrou', 'Miltiades' => 'Miltiadou', 'Mnesarchos' => 'Mnesarchou', 'Neokles' => 'Neokleous',
        'Nikeratos' => 'Nikeratou', 'Nikias' => 'Nikiou', 'Nikomachos' => 'Nikomachou', 'Perikles' => 'Perikleous',
        'Phaidros' => 'Phaidrou', 'Pheidias' => 'Pheidiou', 'Philippos' => 'Philippou', 'Phokion' => 'Phokionos',
        'Platon' => 'Platonos', 'Polemarchos' => 'Polemarchou', 'Proxenos' => 'Proxenou', 'Sokrates' => 'Sokratous',
        'Solon' => 'Solonos', 'Sophokles' => 'Sophokleous', 'Sophroniskos' => 'Sophroniskou',
        'Themistokles' => 'Themistokleous', 'Thrasybulos' => 'Thrasybulou', 'Thukydides' => 'Thukydidou',
        'Timotheos' => 'Timotheou', 'Xanthippos' => 'Xanthippou', 'Xenophon' => 'Xenophontos',
    ];

    /** @var list<string> */
    protected const array FIRST_NAME_FEMALE = [
        'Agariste', 'Archippe', 'Arete', 'Aspasia', 'Diotima', 'Eirene', 'Elpinike', 'Glykera', 'Hegeso', 'Hipparete',
        'Hipparchia', 'Kallisto', 'Kleobuline', 'Korinna', 'Lysimache', 'Lysistrata', 'Melissa', 'Mnesarete',
        'Myrrhine', 'Nikarete', 'Philinna', 'Phryne', 'Praxagora', 'Sappho', 'Telesilla', 'Thais', 'Theano',
        'Theodote', 'Timarete', 'Xanthippe',
    ];

    /** @var list<string> */
    protected const array FIRST_NAME_NEUTRAL = [];

    /**
     * Demotics - "of the deme": Alopekethen (Sokrates), Cholargeus (Perikles), Paianieus (Demosthenes)
     *
     * @var list<string>
     */
    protected const array DEMOTIC = [
        'Acharneus', 'Aixoneus', 'Alopekethen', 'Anaphlystios', 'Cholargeus', 'Eleusinios', 'Erchieus', 'Euonymeus',
        'Hagnousios', 'Halaieus', 'Kephisieus', 'Kerameus', 'Kolonethen', 'Kydathenaieus', 'Lakiades', 'Lamptreus',
        'Marathonios', 'Myrrhinousios', 'Paianieus', 'Pallenieus', 'Peiraieus', 'Phalereus', 'Phrearrhios',
        'Rhamnousios', 'Sounieus', 'Sphettios', 'Thorikios',
    ];

    /** @var list<string> */
    protected const array TITLE_MALE = ['Strategos', 'Archon', 'Prytanis', 'Bouleutes', 'Choregos', 'Trierarchos', 'Hoplites'];

    /** @var list<string> */
    protected const array TITLE_FEMALE = ['Hiereia', 'Kanephoros', 'Pythia'];

    /** @var list<string> */
    protected const array SUFFIX = ['ho neoteros', 'ho presbyteros'];

    /**
     * Patronymic and deme for men ("Sophroniskou Alopekethen"), patronymic for women ("Proxenou") - random gender when
     * null
     */
    public function lastName(?Gender $gender = null): string
    {
        $patronymic = $this->patronymic();

        return match ($this->resolveGender($gender)) {
            Gender::Male => $patronymic . ' ' . $this->demotic(),
            Gender::Female => $patronymic,
        };
    }

    /**
     * Father's name in genitive, e.g. "Perikleous" (son / daughter of Perikles)
     */
    public function patronymic(): string
    {
        return $this->genitive($this->firstName(Gender::Male));
    }

    /**
     * e.g. "Paianieus" (of Paiania)
     */
    public function demotic(): string
    {
        return $this->randomizer->randomElement(static::DEMOTIC);
    }

    /**
     * Genitive of a male name of the pack, e.g. "Kimon" -> "Kimonos"; other names unchanged
     */
    public function genitive(string $name): string
    {
        return static::GENITIVE[$name] ?? $name;
    }
}
