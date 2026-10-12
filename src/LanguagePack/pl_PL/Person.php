<?php

declare(strict_types = 1);

namespace Quidam\LanguagePack\pl_PL;

use Quidam\Core\Person as CorePerson;
use Quidam\Definitions\Enum\Gender;

/**
 * Polish names - surnames follow gender (Kowalski / Kowalska)
 */
class Person extends CorePerson
{
    /** @var list<string> */
    protected const array FIRST_NAME_MALE = [
        'Adam', 'Adrian', 'Alan', 'Albert', 'Aleks', 'Aleksander', 'Aleksy', 'Alex', 'Andrzej', 'Antoni', 'Arkadiusz',
        'Artur', 'Bartek', 'Bartłomiej', 'Błażej', 'Bogdan', 'Bogusław', 'Bolesław', 'Borys', 'Bronisław', 'Bruno',
        'Cezary', 'Cyprian', 'Czesław', 'Damian', 'Daniel', 'Dariusz', 'Dawid', 'Dominik', 'Edward', 'Emil', 'Ernest',
        'Eryk', 'Eugeniusz', 'Fabian', 'Feliks', 'Filip', 'Franciszek', 'Fryderyk', 'Gabriel', 'Grzegorz', 'Gustaw',
        'Henryk', 'Hubert', 'Ignacy', 'Igor', 'Ireneusz', 'Iwo', 'Jacek', 'Jakub', 'Jan', 'Janusz', 'Jarosław',
        'Jeremi', 'Jerzy', 'Jędrzej', 'Józef', 'Julian', 'Juliusz', 'Kacper', 'Kajetan', 'Kamil', 'Karol', 'Kazimierz',
        'Konrad', 'Konstanty', 'Kornel', 'Krystian', 'Krzysztof', 'Ksawery', 'Lech', 'Leon', 'Leonard', 'Leszek',
        'Ludwik', 'Łukasz', 'Maciej', 'Maks', 'Maksymilian', 'Marcel', 'Marcin', 'Marek', 'Marian', 'Mariusz',
        'Mateusz', 'Maurycy', 'Michał', 'Mieszko', 'Mikołaj', 'Miłosz', 'Mirosław', 'Natan', 'Nataniel', 'Nikodem',
        'Norbert', 'Olaf', 'Olgierd', 'Oliwier', 'Oskar', 'Patryk', 'Paweł', 'Piotr', 'Przemysław', 'Radosław', 'Rafał',
        'Robert', 'Roman', 'Ryszard', 'Sebastian', 'Sławomir', 'Stanisław', 'Stefan', 'Szymon', 'Tadeusz', 'Teodor',
        'Tomasz', 'Tymon', 'Tymoteusz', 'Tytus', 'Wacław', 'Waldemar', 'Wiesław', 'Wiktor', 'Witold', 'Władysław',
        'Wojciech', 'Zbigniew', 'Zdzisław', 'Zenobiusz', 'Zenon', 'Zygmunt',
    ];

    /** @var list<string> */
    protected const array FIRST_NAME_FEMALE = [
        'Ada', 'Adrianna', 'Agata', 'Agnieszka', 'Aleksandra', 'Alicja', 'Amelia', 'Anastazja', 'Aneta', 'Angelika',
        'Aniela', 'Anita', 'Anna', 'Antonina', 'Apolonia', 'Aurelia', 'Barbara', 'Beata', 'Bianka', 'Blanka',
        'Bogumiła', 'Bożena', 'Czesława', 'Dagmara', 'Danuta', 'Daria', 'Dominika', 'Dorota', 'Edyta', 'Eliza',
        'Elżbieta', 'Emilia', 'Ewa', 'Ewelina', 'Gabriela', 'Genowefa', 'Grażyna', 'Halina', 'Hanna', 'Helena', 'Ida',
        'Iga', 'Ilona', 'Inga', 'Irena', 'Iwona', 'Izabela', 'Jadwiga', 'Jagoda', 'Janina', 'Joanna', 'Jolanta',
        'Julia', 'Julianna', 'Julita', 'Justyna', 'Kaja', 'Kalina', 'Kamila', 'Karina', 'Karolina', 'Katarzyna',
        'Kazimiera', 'Kinga', 'Klara', 'Klaudia', 'Kornelia', 'Krystyna', 'Laura', 'Lena', 'Leokadia', 'Lidia',
        'Liliana', 'Liwia', 'Lucyna', 'Łucja', 'Magdalena', 'Maja', 'Malwina', 'Małgorzata', 'Marcelina', 'Maria',
        'Marianna', 'Marika', 'Marta', 'Martyna', 'Marzena', 'Matylda', 'Melania', 'Michalina', 'Milena', 'Mirosława',
        'Monika', 'Nadia', 'Natalia', 'Natasza', 'Nela', 'Nicole', 'Nikola', 'Nina', 'Olga', 'Oliwia', 'Patrycja',
        'Paulina', 'Pola', 'Renata', 'Roksana', 'Rozalia', 'Róża', 'Sabina', 'Sandra', 'Sara', 'Sonia', 'Stanisława',
        'Sylwia', 'Teresa', 'Tola', 'Urszula', 'Wanda', 'Weronika', 'Wiktoria', 'Zofia', 'Zuzanna',
    ];

    /**
     * Male forms - female forms are derived by feminine()
     *
     * @var list<string>
     */
    protected const array LAST_NAME = [
        'Adamczyk', 'Adamski', 'Andrzejewski', 'Baran', 'Baranowski', 'Bąk', 'Bednarek', 'Bielecki', 'Błaszczyk',
        'Borkowski', 'Borowski', 'Brzeziński', 'Brzozowski', 'Chmielewski', 'Chojnacki', 'Ciesielski', 'Cieślak',
        'Czajkowski', 'Czarnecki', 'Czerwiński', 'Dąbrowski', 'Dobrowolski', 'Domagała', 'Domański', 'Duda', 'Dudek',
        'Dziedzic', 'Gajda', 'Gajewski', 'Głowacki', 'Górecki', 'Górski', 'Grabowski', 'Grzelak', 'Jabłoński',
        'Jakubowski', 'Janicki', 'Janik', 'Jankowski', 'Jarosz', 'Jasiński', 'Jastrzębski', 'Jaworski', 'Jóźwiak',
        'Kaczmarczyk', 'Kaczmarek', 'Kalinowski', 'Kamiński', 'Kania', 'Karpiński', 'Kaźmierczak', 'Klimek',
        'Kołodziej', 'Kołodziejczyk', 'Konieczny', 'Kopeć', 'Kot', 'Kowalczyk', 'Kowalewski', 'Kowalik', 'Kowalski',
        'Kozak', 'Kozieł', 'Kozłowski', 'Krajewski', 'Krawczyk', 'Król', 'Kruk', 'Krupa', 'Kubiak', 'Kucharski',
        'Kurek', 'Kwiatkowski', 'Laskowski', 'Leszczyński', 'Lewandowski', 'Lipiński', 'Lis', 'Łuczak', 'Maciejewski',
        'Maj', 'Majchrzak', 'Majewski', 'Makowski', 'Malinowski', 'Małecki', 'Marciniak', 'Marczak', 'Markiewicz',
        'Markowski', 'Mazur', 'Mazurek', 'Michalak', 'Michalski', 'Mikołajczyk', 'Milewski', 'Mróz', 'Mucha', 'Musiał',
        'Nawrocki', 'Nowacki', 'Nowak', 'Nowakowski', 'Nowicki', 'Olejnik', 'Olszewski', 'Orłowski', 'Ostrowski',
        'Pawlak', 'Pawlik', 'Pawłowski', 'Piasecki', 'Piątek', 'Pietrzak', 'Piotrowski', 'Polak', 'Przybylski',
        'Rutkowski', 'Ryba', 'Sadowski', 'Sawicki', 'Sikora', 'Sikorski', 'Siwiec', 'Sobczak', 'Sokołowski',
        'Sosnowski', 'Sowa', 'Stankiewicz', 'Stasiak', 'Stefański', 'Stępień', 'Szczepaniak', 'Szczepański', 'Szewczyk',
        'Szulc', 'Szymański', 'Szymczak', 'Tomaszewski', 'Tomczak', 'Tomczyk', 'Urban', 'Urbaniak', 'Urbański',
        'Walczak', 'Wasilewski', 'Wawrzyniak', 'Wesołowski', 'Wieczorek', 'Wierzbicki', 'Wilk', 'Wiśniewski',
        'Witkowski', 'Włodarczyk', 'Wojciechowski', 'Wolski', 'Woźniak', 'Wójcik', 'Wójtowicz', 'Wróbel', 'Wróblewski',
        'Wysocki', 'Zając', 'Zakrzewski', 'Zalewski', 'Zawadzki', 'Zieliński', 'Zięba', 'Ziółkowski', 'Żak',
    ];

    /** @var list<string> */
    protected const array TITLE_MALE = ['mgr', 'mgr inż.', 'inż.', 'dr', 'dr inż.', 'dr hab.', 'prof.'];

    /** @var list<string> */
    protected const array TITLE_FEMALE = ['mgr', 'mgr inż.', 'inż.', 'dr', 'dr inż.', 'dr hab.', 'prof.'];

    /**
     * Polish has no established unisex first names - the English ones of the core don't apply
     *
     * @var list<string>
     */
    protected const array FIRST_NAME_NEUTRAL = [];

    /** @var list<string> */
    protected const array SUFFIX = ['senior', 'junior'];

    /**
     * Surname in the form of given gender (random when null), e.g. "Kowalski" / "Kowalska", "Konieczny" / "Konieczna", "Nowak" / "Nowak"
     */
    public function lastName(?Gender $gender = null): string
    {
        $gender = $this->resolveGender($gender);
        $lastName = $this->randomizer->randomElement(static::LAST_NAME);

        return $gender === Gender::Female ? $this->feminine($lastName) : $lastName;
    }

    /**
     * Female form of a surname: adjective surnames change the ending - -ski / -cki / -dzki become -ska / -cka / -dzka,
     * -y becomes -a (Konieczny / Konieczna); noun surnames (Nowak) don't change
     */
    protected function feminine(string $lastName): string
    {
        return preg_match('/(?:(?:s|c|dz)ki|y)$/u', $lastName) === 1 ? mb_substr($lastName, 0, -1) . 'a' : $lastName;
    }
}
