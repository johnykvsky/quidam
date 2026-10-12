<?php

declare(strict_types = 1);

namespace Quidam\LanguagePack\pl_PL;

use Quidam\Core\Commerce as CoreCommerce;

/**
 * Polish products and departments - adjectives agree with the gender of the product: "Elegancka skórzana torba",
 * "Elegancki dębowy stół", "Eleganckie szklane lustro"
 */
class Commerce extends CoreCommerce
{
    public const string MASCULINE = 'm';
    public const string FEMININE = 'f';
    public const string NEUTER = 'n';

    /**
     * Masculine forms ending with "-y", "-ki" or "-gi" - feminine and neuter forms are derived
     *
     * @var list<string>
     */
    protected const array PRODUCT_ADJECTIVE = [
        'Elegancki', 'Klasyczny', 'Kompaktowy', 'Wytrzymały', 'Ergonomiczny', 'Fantastyczny', 'Ręcznie robiony',
        'Lekki', 'Luksusowy', 'Nowoczesny', 'Praktyczny', 'Solidny', 'Ekologiczny', 'Inteligentny', 'Mały', 'Duży',
        'Smukły', 'Rustykalny', 'Wyjątkowy', 'Niesamowity', 'Stylowy', 'Wygodny', 'Unikatowy', 'Odnowiony',
        'Markowy', 'Poręczny', 'Designerski', 'Funkcjonalny',
    ];

    /**
     * Masculine forms of material adjectives
     *
     * @var list<string>
     */
    protected const array PRODUCT_MATERIAL = [
        'Aluminiowy', 'Bambusowy', 'Bawełniany', 'Betonowy', 'Ceramiczny', 'Dębowy', 'Drewniany', 'Granitowy',
        'Gumowy', 'Jedwabny', 'Lniany', 'Marmurowy', 'Metalowy', 'Miedziany', 'Plastikowy', 'Skórzany', 'Stalowy',
        'Szklany', 'Wełniany', 'Wiklinowy',
    ];

    /**
     * Products with their grammatical gender
     *
     * @var array<string, string>
     */
    protected const array PRODUCT_GENDER = [
        'Plecak' => self::MASCULINE, 'Rower' => self::MASCULINE, 'Zegar' => self::MASCULINE,
        'Komputer' => self::MASCULINE, 'Kapelusz' => self::MASCULINE, 'Nóż' => self::MASCULINE,
        'Kubek' => self::MASCULINE, 'Talerz' => self::MASCULINE, 'Ręcznik' => self::MASCULINE,
        'Portfel' => self::MASCULINE, 'Zegarek' => self::MASCULINE, 'Stół' => self::MASCULINE,
        'Fotel' => self::MASCULINE, 'Wazon' => self::MASCULINE, 'Dzbanek' => self::MASCULINE,
        'Parasol' => self::MASCULINE, 'Torba' => self::FEMININE, 'Piłka' => self::FEMININE,
        'Ławka' => self::FEMININE, 'Butelka' => self::FEMININE, 'Miska' => self::FEMININE,
        'Klawiatura' => self::FEMININE, 'Lampa' => self::FEMININE, 'Mysz' => self::FEMININE,
        'Poduszka' => self::FEMININE, 'Koszula' => self::FEMININE, 'Sofa' => self::FEMININE,
        'Kurtka' => self::FEMININE, 'Czapka' => self::FEMININE, 'Szafa' => self::FEMININE,
        'Krzesło' => self::NEUTER, 'Biurko' => self::NEUTER, 'Lustro' => self::NEUTER, 'Łóżko' => self::NEUTER,
    ];

    /** @var list<string> */
    protected const array PRODUCT = [
        'Plecak', 'Rower', 'Zegar', 'Komputer', 'Kapelusz', 'Nóż', 'Kubek', 'Talerz', 'Ręcznik', 'Portfel', 'Zegarek',
        'Stół', 'Fotel', 'Wazon', 'Dzbanek', 'Parasol', 'Torba', 'Piłka', 'Ławka', 'Butelka', 'Miska', 'Klawiatura',
        'Lampa', 'Mysz', 'Poduszka', 'Koszula', 'Sofa', 'Kurtka', 'Czapka', 'Szafa', 'Krzesło', 'Biurko', 'Lustro',
        'Łóżko',
    ];

    /** @var list<string> */
    protected const array DEPARTMENT = [
        'Motoryzacja', 'Dla niemowląt', 'Uroda', 'Książki', 'Odzież', 'Komputery', 'Elektronika', 'Gry', 'Ogród',
        'Artykuły spożywcze', 'Zdrowie', 'Dom', 'Przemysł', 'Biżuteria', 'Dla dzieci', 'Filmy', 'Muzyka', 'Turystyka',
        'Obuwie', 'Sport', 'Narzędzia', 'Zabawki',
    ];

    /** @var list<string> */
    protected const array PROMOTION_WORD = [
        'PROMOCJA', 'RABAT', 'WITAJ', 'LATO', 'ZIMA', 'WIOSNA', 'JESIEN', 'BLACKFRIDAY', 'VIP', 'DOSTAWA', 'OKAZJA',
        'NOWYROK', 'DZIEKI', 'EKSTRA', 'SUPER',
    ];

    /**
     * Adjective, material and product in matching gender, e.g. "Elegancka skórzana torba"
     */
    public function productName(): string
    {
        $product = $this->product();
        $gender = static::PRODUCT_GENDER[$product] ?? self::MASCULINE;

        return $this->inflect($this->productAdjective(), $gender) . ' '
            . mb_strtolower($this->inflect($this->productMaterial(), $gender) . ' ' . $product);
    }

    /**
     * Masculine adjective in given gender: "elegancki" -> "elegancka" / "eleganckie", "drewniany" -> "drewniana"
     * / "drewniane"; for multi-word adjectives ("Ręcznie robiony") only the last word changes
     */
    protected function inflect(string $adjective, string $gender): string
    {
        if ($gender === self::MASCULINE) {
            return $adjective;
        }

        $soft = preg_match('/[kg]i$/u', $adjective) === 1;

        return mb_substr($adjective, 0, -1) . match ($gender) {
            self::FEMININE => 'a',
            default => $soft ? 'ie' : 'e',
        };
    }
}
