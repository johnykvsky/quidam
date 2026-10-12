<?php

declare(strict_types = 1);

namespace Quidam\Core;

use Quidam\Definitions\Calculator\EanCalculatorInterface;
use Quidam\Definitions\Calculator\IsbnCalculatorInterface;
use Quidam\Definitions\Extension\BarcodeExtensionInterface;
use Quidam\Definitions\Extension\StringsExtensionInterface;
use Quidam\Definitions\Randomizer\RandomizerInterface;

/**
 * Product and publication codes with valid structure and check digit
 */
class Barcode implements BarcodeExtensionInterface
{
    /**
     * GS1 member organisation (country) prefix ranges, for product codes. Without restricted circulation (02, 04, 2xx),
     * coupons (05, 98x, 99x) and publications (977 - 979) prefixes.
     *
     * @var list<array{int, int}>
     *
     * @see https://www.gs1.org/standards/id-keys/company-prefix
     */
    protected const array GS1_PREFIXES = [
        [1, 19], [30, 39], [60, 139], [300, 379], [380, 380], [383, 383], [385, 385], [400, 440], [450, 459],
        [460, 469], [471, 471], [474, 482], [489, 489], [490, 499], [500, 509], [520, 521], [528, 531], [535, 535],
        [539, 539], [540, 549], [560, 560], [569, 569], [570, 579], [590, 590], [594, 594], [599, 601], [690, 699],
        [700, 709], [729, 729], [730, 739], [760, 769], [779, 780], [789, 790], [800, 839], [840, 849], [858, 860],
        [868, 869], [870, 879], [880, 880], [885, 885], [888, 888], [890, 890], [893, 893], [899, 899], [900, 919],
        [930, 939], [940, 949], [955, 955],
    ];

    /**
     * ISBN registration groups under 978 prefix (also valid for ISBN-10)
     *
     * @var list<string>
     *
     * @see https://www.isbn-international.org/range_file_generation
     */
    protected const array ISBN_978_GROUPS = [
        '0', '1', '2', '3', '4', '5', '7',
        '80', '81', '82', '83', '84', '85', '86', '87', '88', '89', '90', '91', '92', '93', '94',
        '600', '601', '602', '603', '604', '605', '606', '607', '608', '609', '611', '612', '613', '614', '615', '616',
        '617', '618', '619', '620', '621', '950', '951', '952', '953', '954', '955', '956', '957', '958', '959', '960',
        '961', '962', '963', '964', '965', '966', '967', '968', '969', '970', '971', '972', '973', '974', '975', '976',
        '977', '978', '979', '980', '981', '982', '983', '984', '985', '986', '987', '988', '989',
    ];

    /**
     * ISBN registration groups under 979 prefix (no ISBN-10 equivalent)
     *
     * @var list<string>
     */
    protected const array ISBN_979_GROUPS = ['8', '10', '11', '12', '13'];

    /**
     * UPC-A number system digits of regular products (2, 4 - in-store, 5 - coupons, 3 - drugs)
     *
     * @var list<int>
     */
    protected const array UPC_NUMBER_SYSTEMS = [0, 1, 6, 7, 8];

    public function __construct(
        protected readonly RandomizerInterface $randomizer,
        protected readonly StringsExtensionInterface $strings,
        protected readonly EanCalculatorInterface $ean,
        protected readonly IsbnCalculatorInterface $isbn,
    ) {
    }

    /**
     * EAN-13 / GTIN-13 product code with GS1 country prefix, e.g. "5901234123457"
     */
    public function ean13(): string
    {
        return $this->withCheckDigit($this->gs1Prefix(minimum: 1) . $this->digits(9));
    }

    /**
     * EAN-8 / GTIN-8 product code for small packages, e.g. "59012341"
     */
    public function ean8(): string
    {
        // EAN-8 codes starting with 0 or 2 are restricted (velocity / in-store) codes
        return $this->withCheckDigit($this->gs1Prefix(minimum: 300) . $this->digits(4));
    }

    /**
     * UPC-A product code used in US and Canada, e.g. "036000291452"
     */
    public function upcA(): string
    {
        $code = $this->randomizer->randomElement(static::UPC_NUMBER_SYSTEMS) . $this->digits(10);

        // UPC-A is EAN-13 with leading zero
        return substr($this->withCheckDigit('0' . $code), 1);
    }

    /**
     * ISBN-13 with valid prefix and registration group, e.g. "9788375066254"
     */
    public function isbn13(): string
    {
        [$prefix, $group] = $this->randomizer->getBool(85)
            ? ['978', $this->randomizer->randomElement(static::ISBN_978_GROUPS)]
            : ['979', $this->randomizer->randomElement(static::ISBN_979_GROUPS)];

        return $this->withCheckDigit($prefix . $group . $this->digits(9 - strlen($group)));
    }

    /**
     * ISBN-10 with valid registration group, check digit can be "X", e.g. "837506625X"
     */
    public function isbn10(): string
    {
        $group = $this->randomizer->randomElement(static::ISBN_978_GROUPS);
        $code = $group . $this->digits(9 - strlen($group));

        return $code . $this->isbn->checkDigit($code);
    }

    /**
     * ISMN (printed music) in 13-digit form, e.g. "9790260000438"
     */
    public function ismn(): string
    {
        return $this->withCheckDigit('9790' . $this->digits(8));
    }

    /**
     * 3-digit GS1 prefix (zero-padded) not lower than $minimum
     */
    protected function gs1Prefix(int $minimum): string
    {
        $ranges = array_values(array_filter(static::GS1_PREFIXES, static fn (array $range): bool => $range[0] >= $minimum));
        [$from, $to] = $this->randomizer->randomElement($ranges);

        return sprintf('%03d', $this->randomizer->getInt($from, $to));
    }

    protected function digits(int $count): string
    {
        return $this->strings->numerify(str_repeat('#', $count));
    }

    /**
     * Appends GS1 mod 10 check digit (EAN-8, EAN-13, ISBN-13, ISMN)
     */
    protected function withCheckDigit(string $code): string
    {
        return $code . $this->ean->checkDigit($code);
    }
}
