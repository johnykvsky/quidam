<?php

declare(strict_types = 1);

namespace Quidam\LanguagePack\pl_PL;

use Quidam\Core\Payment as CorePayment;
use Quidam\Exception\InvalidArgumentException;

/**
 * Polish banks; IBAN and account numbers (NRB) with a real bank code and a valid sort code check digit. Currency and
 * price format ("1 234,56 zł") come from the locale.
 */
class Payment extends CorePayment
{
    protected const string LOCAL_CURRENCY_NAME = 'złoty';

    /**
     * Bank code (first 3 digits of the sort code) => bank name
     *
     * @var array<int, string>
     */
    protected const array BANKS = [
        101 => 'Narodowy Bank Polski',
        102 => 'Powszechna Kasa Oszczędności Bank Polski Spółka Akcyjna',
        103 => 'Bank Handlowy w Warszawie Spółka Akcyjna',
        105 => 'ING Bank Śląski Spółka Akcyjna',
        106 => 'Bank BPH Spółka Akcyjna',
        109 => 'Santander Bank Polska Spółka Akcyjna',
        113 => 'Bank Gospodarstwa Krajowego',
        114 => 'mBank Spółka Akcyjna',
        116 => 'Bank Millennium Spółka Akcyjna',
        122 => 'Bank Handlowo-Kredytowy Spółka Akcyjna w Katowicach w likwidacji',
        124 => 'Bank Polska Kasa Opieki Spółka Akcyjna',
        132 => 'Bank Pocztowy Spółka Akcyjna',
        154 => 'Bank Ochrony Środowiska Spółka Akcyjna',
        158 => 'Mercedes-Benz Bank Polska Spółka Akcyjna',
        161 => 'SGB-Bank Spółka Akcyjna',
        168 => 'PLUS BANK Spółka Akcyjna',
        184 => 'Société Générale Spółka Akcyjna Oddział w Polsce',
        187 => 'Nest Bank Spółka Akcyjna',
        189 => 'Pekao Bank Hipoteczny Spółka Akcyjna',
        191 => 'Deutsche Bank Polska Spółka Akcyjna',
        193 => 'BANK POLSKIEJ SPÓŁDZIELCZOŚCI SPÓŁKA AKCYJNA',
        194 => 'Credit Agricole Bank Polska Spółka Akcyjna',
        195 => 'Idea Bank Spółka Akcyjna',
        203 => 'BNP Paribas Bank Polska Spółka Akcyjna',
        212 => 'Santander Consumer Bank Spółka Akcyjna',
        215 => 'mBank Hipoteczny Spółka Akcyjna',
        216 => 'Toyota Bank Polska Spółka Akcyjna',
        219 => 'DNB Bank Polska Spółka Akcyjna',
        224 => 'Banque PSA Finance Spółka Akcyjna Oddział w Polsce',
        225 => 'Svenska Handelsbanken AB Spółka Akcyjna Oddział w Polsce',
        235 => 'BNP Paribas S.A. Oddział w Polsce',
        236 => 'Danske Bank A/S Spółka Akcyjna Oddział w Polsce',
        237 => 'Skandinaviska Enskilda Banken AB (Spółka Akcyjna) - Oddział w Polsce',
        239 => 'CAIXABANK, S.A. (SPÓŁKA AKCYJNA) ODDZIAŁ W POLSCE',
        241 => 'Elavon Financial Services Designated Activity Company (Spółka z O.O. o Wyznaczonym Przedmiocie Działalności) Oddział w Polsce',
        243 => 'BNP Paribas Securities Services Spółka Komandytowo - Akcyjna Oddział w Polsce',
        247 => 'HAITONG BANK, S.A. Spółka Akcyjna Oddział w Polsce',
        248 => 'Getin Noble Bank Spółka Akcyjna',
        249 => 'Alior Bank Spółka Akcyjna',
        251 => 'Aareal Bank Aktiengesellschaft (Spółka Akcyjna) - Oddział w Polsce',
        254 => 'Citibank Europe plc (Publiczna Spółka Akcyjna) Oddział w Polsce',
        255 => 'Ikano Bank AB (publ) Spółka Akcyjna Oddział w Polsce',
        256 => 'Nordea Bank Abp Spółka Akcyjna Oddział w Polsce',
        258 => 'J.P. Morgan Europe Limited Spółka z ograniczoną odpowiedzialnością Oddział w Polsce',
        260 => 'Bank of China (Luxembourg) S.A. Spółka Akcyjna Oddział w Polsce',
        262 => 'Industrial and Commercial Bank of China (Europe) S.A. (Spółka Akcyjna) Oddział w Polsce',
        264 => 'RCI Banque Spółka Akcyjna Oddział w Polsce',
        265 => 'EUROCLEAR Bank SA/NV (Spółka Akcyjna) - Oddział w Polsce',
        266 => 'Intesa Sanpaolo S.p.A. Spółka Akcyjna Oddział w Polsce',
        267 => 'Western Union International Bank GmbH, Sp. z o.o. Oddział w Polsce',
        269 => 'PKO Bank Hipoteczny Spółka Akcyjna',
        270 => 'TF BANK AB (Spółka z ograniczoną odpowiedzialnością) Oddział w Polsce',
        271 => 'FCE Bank Spółka Akcyjna Oddział w Polsce',
        272 => 'AS Inbank Spółka Akcyjna - Oddział w Polsce',
        273 => 'China Construction Bank (Europe) S.A. (Spółka Akcyjna) Oddział w Polsce',
        274 => 'MUFG Bank (Europe) N.V. S.A. Oddział w Polsce',
        275 => 'John Deere Bank S.A. Spółka Akcyjna Oddział w Polsce',
        277 => 'Volkswagen Bank GmbH Spółka z ograniczoną odpowiedzialnością Oddział w Polsce',
        278 => 'ING Bank Hipoteczny Spółka Akcyjna',
        279 => 'Raiffeisen Bank International AG (Spółka Akcyjna) Oddział w Polsce',
        280 => 'HSBC France (Spółka Akcyjna) Oddział w Polsce',
        281 => 'Goldman Sachs Bank Europe SE Spółka Europejska Oddział w Polsce',
        283 => 'J.P. Morgan AG (Spółka Akcyjna) Oddział w Polsce',
        284 => 'UBS Europe SE (Spółka Europejska) Oddział w Polsce',
        285 => 'Banca Farmafactoring S.p.A. Spółka Akcyjna Oddział w Polsce',
        286 => 'FCA Bank S.p.A. Spółka Akcyjna Oddział w Polsce',
        287 => 'Bank Nowy BFG Spółka Akcyjna',
        288 => 'ALLFUNDS BANK S.A.U. (SPÓŁKA AKCYJNA) ODDZIAŁ W POLSCE',
    ];

    /**
     * PLN with its Polish name - ['code' => 'PLN', 'name' => 'złoty', 'symbol' => 'zł']
     *
     * @return array{code: string, name: string, symbol: string}
     */
    public function localCurrency(): array
    {
        $currency = parent::localCurrency();

        return ['code' => $currency['code'], 'name' => static::LOCAL_CURRENCY_NAME, 'symbol' => $currency['symbol']];
    }

    /**
     * Bank name, e.g. "Powszechna Kasa Oszczędności Bank Polski Spółka Akcyjna"
     */
    public function bank(): string
    {
        return static::BANKS[$this->bankCode()];
    }

    /**
     * Polish IBAN by default (locale country) - bank part is a real bank code with a valid sort code check digit
     */
    public function iban(?string $countryCode = null, string $prefix = '', bool $formatted = false): string
    {
        if (strtoupper($countryCode ?? (string) $this->locale->countryCode()) === 'PL' && $prefix === '') {
            $prefix = $this->sortCode();
        }

        return parent::iban($countryCode, $prefix, $formatted);
    }

    /**
     * Polish account number (NRB) - 26 digits, the IBAN without "PL"; other lengths - random digits
     */
    public function bankAccountNumber(int $length = 26): string
    {
        return $length === 26 ? substr($this->iban('PL'), 2) : parent::bankAccountNumber($length);
    }

    /**
     * 8-digit sort code (numer rozliczeniowy): bank code, branch, check digit - of given or random bank
     */
    public function sortCode(?string $bankCode = null): string
    {
        $bankCode ??= $this->bankCode();

        if (!isset(static::BANKS[$bankCode])) {
            throw new InvalidArgumentException(sprintf('Unknown Polish bank code "%s"', $bankCode));
        }

        $number = $bankCode . $this->strings->numerify('####');

        return $number . $this->sortCodeCheckDigit($number);
    }

    protected function bankCode(): string
    {
        return (string) $this->randomizer->randomKey(static::BANKS);
    }

    /**
     * Weights 3, 9, 7, 1, 3, 9, 7 - the weighted sum with the check digit is divisible by 10
     */
    protected function sortCodeCheckDigit(string $sevenDigits): string
    {
        $sum = 0;
        foreach (str_split($sevenDigits) as $position => $digit) {
            $sum += (int) $digit * [3, 9, 7, 1, 3, 9, 7][$position];
        }

        return (string) ((10 - $sum % 10) % 10);
    }
}
