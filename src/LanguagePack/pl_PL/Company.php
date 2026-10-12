<?php

declare(strict_types = 1);

namespace Quidam\LanguagePack\pl_PL;

use Quidam\Core\Company as CoreCompany;
use Quidam\Definitions\Enum\Gender;

/**
 * Polish company names, legal forms, job titles and industries - e.g. "Malinowski sp. z o.o.", "Bud-Pol S.A."
 */
class Company extends CoreCompany
{
    /** @var list<string> */
    protected const array SUFFIX = [
        'S.A.', 'sp. z o.o.', 'sp. z o.o.', 'sp. z o.o.', 'sp. j.', 'sp. k.', 'sp. p.', 'S.K.A.', 's.c.', 'i Syn',
        'i Wspólnicy',
    ];

    /** @var list<string> */
    protected const array PREFIX = [
        'Grupa', 'Fundacja', 'Stowarzyszenie', 'Spółdzielnia', 'P.H.U.', 'F.H.U.', 'Zakład', 'Pracownia', 'Studio',
    ];

    /**
     * Core placeholders plus "{prefix}" (PREFIX) and "{nameWords}" (two different NAME_WORD parts, "Bud-Pol")
     *
     * @var list<string>
     */
    protected const array COMPANY_FORMATS = [
        '{lastName} {suffix}', '{lastName} i {otherLastName}', '{lastName}-{otherLastName} {suffix}', '{prefix} {lastName}',
        '{nameWords} {suffix}',
    ];

    /**
     * Parts of made-up names like "Bud-Pol", "Trans-Mar"
     *
     * @var list<string>
     */
    protected const array NAME_WORD = [
        'Agro', 'Auto', 'Bud', 'Drew', 'Eko', 'Elektro', 'Hand', 'Inter', 'Inwest', 'Mar', 'Med', 'Pol', 'Rol',
        'Serwis', 'Stal', 'Tech', 'Trans', 'Wod', 'Kom', 'Met',
    ];

    /** @var list<string> */
    protected const array JOB_TITLE = [
        'Administrator systemów', 'Agent ubezpieczeniowy', 'Analityk biznesowy', 'Analityk danych', 'Architekt',
        'Asystent zarządu', 'Doradca klienta', 'Doradca podatkowy', 'Dyrektor finansowy', 'Dyrektor handlowy',
        'Elektryk', 'Farmaceuta', 'Fizjoterapeuta', 'Fryzjer', 'Grafik', 'Inspektor BHP', 'Inżynier budowy', 'Kelner',
        'Kierowca', 'Kierownik projektu', 'Kierownik sklepu', 'Kierownik zmiany', 'Konsultant', 'Kontroler jakości',
        'Koordynator projektów', 'Księgowa', 'Księgowy', 'Kucharz', 'Lekarz', 'Logistyk', 'Magazynier',
        'Mechanik samochodowy', 'Murarz', 'Nauczyciel', 'Operator wózka widłowego', 'Opiekun klienta', 'Pielęgniarka',
        'Prezes zarządu', 'Product owner', 'Programista', 'Projektant UX', 'Przedstawiciel handlowy', 'Radca prawny',
        'Recepcjonista', 'Rekruter', 'Spawacz', 'Specjalista ds. kadr i płac', 'Specjalista ds. marketingu',
        'Specjalista ds. zakupów', 'Spedytor', 'Sprzedawca', 'Stolarz', 'Ślusarz', 'Technik informatyk',
        'Tester oprogramowania',
    ];

    /** @var list<string> */
    protected const array INDUSTRY = [
        'Administracja publiczna', 'Bankowość', 'Budownictwo', 'Doradztwo', 'E-commerce', 'Edukacja', 'Energetyka',
        'Farmacja', 'Gastronomia', 'Górnictwo', 'Handel detaliczny', 'Handel hurtowy', 'Hotelarstwo', 'Informatyka',
        'Kosmetyki', 'Media', 'Moda i odzież', 'Nieruchomości', 'Ochrona zdrowia', 'Produkcja mebli',
        'Przemysł chemiczny', 'Przemysł drzewny', 'Przemysł motoryzacyjny', 'Przemysł spożywczy', 'Reklama i marketing',
        'Rolnictwo', 'Sport i rekreacja', 'Telekomunikacja', 'Transport i logistyka', 'Turystyka', 'Ubezpieczenia',
        'Usługi prawne',
    ];

    /**
     * Plural adjective + plural noun + complement: in plural, adjectives of non-personal nouns have one form for all
     * genders, so every combination is grammatical - "Nowoczesne rozwiązania dla biznesu"
     *
     * @var list<list<string>>
     */
    protected const array CATCH_PHRASE_WORDS = [
        [
            'Innowacyjne', 'Nowoczesne', 'Kompleksowe', 'Elastyczne', 'Skalowalne', 'Zintegrowane', 'Bezpieczne',
            'Inteligentne', 'Wydajne', 'Niezawodne', 'Zautomatyzowane', 'Profesjonalne', 'Ekologiczne', 'Cyfrowe',
            'Mobilne', 'Sprawdzone', 'Intuicyjne', 'Zaawansowane', 'Dedykowane', 'Przyjazne', 'Optymalne', 'Lokalne',
            'Globalne', 'Ekonomiczne', 'Spersonalizowane', 'Uniwersalne', 'Kreatywne', 'Strategiczne',
        ],
        [
            'rozwiązania', 'systemy', 'usługi', 'narzędzia', 'platformy', 'technologie', 'aplikacje', 'procesy',
            'produkty', 'strategie', 'projekty', 'standardy', 'moduły', 'algorytmy', 'serwisy', 'sieci', 'modele',
            'interfejsy', 'pakiety', 'programy', 'metody', 'koncepcje', 'inwestycje', 'partnerstwa',
        ],
        [
            'dla biznesu', 'dla każdego', 'dla firm', 'dla domu', 'w chmurze', 'nowej generacji', 'na miarę XXI wieku',
            'bez kompromisów', 'od podstaw', 'na lata', 'w czasie rzeczywistym', 'dla wymagających', 'w każdej skali',
            'krok po kroku', 'bez granic', 'z gwarancją', 'dla przyszłości', 'na każdą kieszeń', 'dla profesjonalistów',
            'w twoim mieście', 'na wyciągnięcie ręki', 'dla rozwoju', 'w jednym miejscu', 'z pasją',
        ],
    ];

    /**
     * e.g. "Malinowski sp. z o.o.", "Kowalski i Nowak", "Grupa Wiśniewski", "Bud-Pol S.A."
     */
    public function company(): string
    {
        $company = parent::company();

        if (str_contains($company, '{prefix}')) {
            $company = str_replace('{prefix}', $this->companyPrefix(), $company);
        }

        if (str_contains($company, '{nameWords}')) {
            [$first, $second] = $this->randomizer->randomElements(static::NAME_WORD, 2, true);
            $company = str_replace('{nameWords}', $first . '-' . $second, $company);
        }

        return $company;
    }

    /**
     * e.g. "Grupa", "Fundacja", "P.H.U."
     */
    public function companyPrefix(): string
    {
        return $this->randomizer->randomElement(static::PREFIX);
    }

    /**
     * Company names use the base (male) form of surnames: "Kowalski i Syn"
     */
    protected function companyLastName(): string
    {
        return $this->person->lastName(Gender::Male);
    }
}
