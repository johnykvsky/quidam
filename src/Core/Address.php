<?php

declare(strict_types = 1);

namespace Quidam\Core;

use Quidam\Definitions\Extension\AddressExtensionInterface;
use Quidam\Definitions\Extension\PersonExtensionInterface;
use Quidam\Definitions\Extension\StringsExtensionInterface;
use Quidam\Definitions\Randomizer\RandomizerInterface;
use Quidam\Exception\InvalidArgumentException;

class Address implements AddressExtensionInterface
{
    /** @var list<string> */
    protected const array CITY_PREFIX = ['North', 'East', 'West', 'South', 'New', 'Lake', 'Port'];

    /** @var list<string> */
    protected const array CITY_SUFFIX = [
        'town', 'ton', 'land', 'ville', 'berg', 'burgh', 'borough', 'bury', 'view', 'port', 'mouth', 'stad', 'furt',
        'chester', 'fort', 'haven', 'side', 'shire', 'field', 'ford', 'wood', 'dale', 'bridge', 'brook',
    ];

    /** @var list<string> */
    protected const array STREET_SUFFIX = [
        'Street', 'Avenue', 'Alley', 'Park', 'Drive', 'Lane', 'Square', 'Hill', 'Road', 'Way', 'Court', 'Place',
        'Boulevard', 'Terrace', 'Crescent', 'Close', 'Gardens', 'Row',
    ];

    /** @var list<string> */
    protected const array BUILDING_NUMBER = ['%##', '%#', '%#', '%', '%', '%'];

    /** @var list<string> */
    protected const array POSTCODE = ['#####', '####'];

    /** @var list<string> masks for strings()->numerify() */
    protected const array SECONDARY_ADDRESS = ['Apt. %##', 'Apt. %#', 'Suite %##', 'Unit %#', 'Floor %'];

    /**
     * States, provinces or regions: code => name. Default is a sample without country - generic regions; language
     * packs set real ones (US states, Polish voivodeships...)
     *
     * @var array<string, string>
     */
    protected const array STATES = [
        'NR' => 'Northern Region', 'SR' => 'Southern Region', 'ER' => 'Eastern Region', 'WR' => 'Western Region',
        'CR' => 'Central Region', 'CD' => 'Capital District', 'CO' => 'Coastal Region', 'HL' => 'Highlands',
    ];

    /** County / district - "{name}" is a last name */
    protected const string COUNTY_FORMAT = '{name} District';

    /** Street line - "{street}" (streetName()) and "{buildingNumber}" are replaced */
    protected const string STREET_ADDRESS_FORMAT = '{street} {buildingNumber}';

    /**
     * Address line - "{street}" (street line), "{postcode}", "{city}", "{state}", "{stateCode}" and "{county}" are
     * replaced; the last three only in addressData()
     */
    protected const string ADDRESS_FORMAT = '{street}, {postcode} {city}';

    /**
     * ISO 3166-1 countries as [alpha-2, alpha-3, English short name]
     *
     * @var list<array{string, string, string}>
     *
     * @see https://en.wikipedia.org/wiki/ISO_3166-1
     */
    protected const array COUNTRIES = [
        ['AD', 'AND', 'Andorra'],
        ['AE', 'ARE', 'United Arab Emirates'],
        ['AF', 'AFG', 'Afghanistan'],
        ['AG', 'ATG', 'Antigua and Barbuda'],
        ['AI', 'AIA', 'Anguilla'],
        ['AL', 'ALB', 'Albania'],
        ['AM', 'ARM', 'Armenia'],
        ['AO', 'AGO', 'Angola'],
        ['AQ', 'ATA', 'Antarctica'],
        ['AR', 'ARG', 'Argentina'],
        ['AS', 'ASM', 'American Samoa'],
        ['AT', 'AUT', 'Austria'],
        ['AU', 'AUS', 'Australia'],
        ['AW', 'ABW', 'Aruba'],
        ['AX', 'ALA', 'Åland Islands'],
        ['AZ', 'AZE', 'Azerbaijan'],
        ['BA', 'BIH', 'Bosnia and Herzegovina'],
        ['BB', 'BRB', 'Barbados'],
        ['BD', 'BGD', 'Bangladesh'],
        ['BE', 'BEL', 'Belgium'],
        ['BF', 'BFA', 'Burkina Faso'],
        ['BG', 'BGR', 'Bulgaria'],
        ['BH', 'BHR', 'Bahrain'],
        ['BI', 'BDI', 'Burundi'],
        ['BJ', 'BEN', 'Benin'],
        ['BL', 'BLM', 'Saint Barthélemy'],
        ['BM', 'BMU', 'Bermuda'],
        ['BN', 'BRN', 'Brunei'],
        ['BO', 'BOL', 'Bolivia'],
        ['BQ', 'BES', 'Bonaire, Sint Eustatius and Saba'],
        ['BR', 'BRA', 'Brazil'],
        ['BS', 'BHS', 'Bahamas'],
        ['BT', 'BTN', 'Bhutan'],
        ['BV', 'BVT', 'Bouvet Island'],
        ['BW', 'BWA', 'Botswana'],
        ['BY', 'BLR', 'Belarus'],
        ['BZ', 'BLZ', 'Belize'],
        ['CA', 'CAN', 'Canada'],
        ['CC', 'CCK', 'Cocos Islands'],
        ['CD', 'COD', 'Democratic Republic of the Congo'],
        ['CF', 'CAF', 'Central African Republic'],
        ['CG', 'COG', 'Congo'],
        ['CH', 'CHE', 'Switzerland'],
        ['CI', 'CIV', 'Côte d\'Ivoire'],
        ['CK', 'COK', 'Cook Islands'],
        ['CL', 'CHL', 'Chile'],
        ['CM', 'CMR', 'Cameroon'],
        ['CN', 'CHN', 'China'],
        ['CO', 'COL', 'Colombia'],
        ['CR', 'CRI', 'Costa Rica'],
        ['CU', 'CUB', 'Cuba'],
        ['CV', 'CPV', 'Cabo Verde'],
        ['CW', 'CUW', 'Curaçao'],
        ['CX', 'CXR', 'Christmas Island'],
        ['CY', 'CYP', 'Cyprus'],
        ['CZ', 'CZE', 'Czechia'],
        ['DE', 'DEU', 'Germany'],
        ['DJ', 'DJI', 'Djibouti'],
        ['DK', 'DNK', 'Denmark'],
        ['DM', 'DMA', 'Dominica'],
        ['DO', 'DOM', 'Dominican Republic'],
        ['DZ', 'DZA', 'Algeria'],
        ['EC', 'ECU', 'Ecuador'],
        ['EE', 'EST', 'Estonia'],
        ['EG', 'EGY', 'Egypt'],
        ['EH', 'ESH', 'Western Sahara'],
        ['ER', 'ERI', 'Eritrea'],
        ['ES', 'ESP', 'Spain'],
        ['ET', 'ETH', 'Ethiopia'],
        ['FI', 'FIN', 'Finland'],
        ['FJ', 'FJI', 'Fiji'],
        ['FK', 'FLK', 'Falkland Islands'],
        ['FM', 'FSM', 'Micronesia'],
        ['FO', 'FRO', 'Faroe Islands'],
        ['FR', 'FRA', 'France'],
        ['GA', 'GAB', 'Gabon'],
        ['GB', 'GBR', 'United Kingdom'],
        ['GD', 'GRD', 'Grenada'],
        ['GE', 'GEO', 'Georgia'],
        ['GF', 'GUF', 'French Guiana'],
        ['GG', 'GGY', 'Guernsey'],
        ['GH', 'GHA', 'Ghana'],
        ['GI', 'GIB', 'Gibraltar'],
        ['GL', 'GRL', 'Greenland'],
        ['GM', 'GMB', 'Gambia'],
        ['GN', 'GIN', 'Guinea'],
        ['GP', 'GLP', 'Guadeloupe'],
        ['GQ', 'GNQ', 'Equatorial Guinea'],
        ['GR', 'GRC', 'Greece'],
        ['GS', 'SGS', 'South Georgia and the South Sandwich Islands'],
        ['GT', 'GTM', 'Guatemala'],
        ['GU', 'GUM', 'Guam'],
        ['GW', 'GNB', 'Guinea-Bissau'],
        ['GY', 'GUY', 'Guyana'],
        ['HK', 'HKG', 'Hong Kong'],
        ['HM', 'HMD', 'Heard Island and McDonald Islands'],
        ['HN', 'HND', 'Honduras'],
        ['HR', 'HRV', 'Croatia'],
        ['HT', 'HTI', 'Haiti'],
        ['HU', 'HUN', 'Hungary'],
        ['ID', 'IDN', 'Indonesia'],
        ['IE', 'IRL', 'Ireland'],
        ['IL', 'ISR', 'Israel'],
        ['IM', 'IMN', 'Isle of Man'],
        ['IN', 'IND', 'India'],
        ['IO', 'IOT', 'British Indian Ocean Territory'],
        ['IQ', 'IRQ', 'Iraq'],
        ['IR', 'IRN', 'Iran'],
        ['IS', 'ISL', 'Iceland'],
        ['IT', 'ITA', 'Italy'],
        ['JE', 'JEY', 'Jersey'],
        ['JM', 'JAM', 'Jamaica'],
        ['JO', 'JOR', 'Jordan'],
        ['JP', 'JPN', 'Japan'],
        ['KE', 'KEN', 'Kenya'],
        ['KG', 'KGZ', 'Kyrgyzstan'],
        ['KH', 'KHM', 'Cambodia'],
        ['KI', 'KIR', 'Kiribati'],
        ['KM', 'COM', 'Comoros'],
        ['KN', 'KNA', 'Saint Kitts and Nevis'],
        ['KP', 'PRK', 'North Korea'],
        ['KR', 'KOR', 'South Korea'],
        ['KW', 'KWT', 'Kuwait'],
        ['KY', 'CYM', 'Cayman Islands'],
        ['KZ', 'KAZ', 'Kazakhstan'],
        ['LA', 'LAO', 'Laos'],
        ['LB', 'LBN', 'Lebanon'],
        ['LC', 'LCA', 'Saint Lucia'],
        ['LI', 'LIE', 'Liechtenstein'],
        ['LK', 'LKA', 'Sri Lanka'],
        ['LR', 'LBR', 'Liberia'],
        ['LS', 'LSO', 'Lesotho'],
        ['LT', 'LTU', 'Lithuania'],
        ['LU', 'LUX', 'Luxembourg'],
        ['LV', 'LVA', 'Latvia'],
        ['LY', 'LBY', 'Libya'],
        ['MA', 'MAR', 'Morocco'],
        ['MC', 'MCO', 'Monaco'],
        ['MD', 'MDA', 'Moldova'],
        ['ME', 'MNE', 'Montenegro'],
        ['MF', 'MAF', 'Saint Martin'],
        ['MG', 'MDG', 'Madagascar'],
        ['MH', 'MHL', 'Marshall Islands'],
        ['MK', 'MKD', 'North Macedonia'],
        ['ML', 'MLI', 'Mali'],
        ['MM', 'MMR', 'Myanmar'],
        ['MN', 'MNG', 'Mongolia'],
        ['MO', 'MAC', 'Macao'],
        ['MP', 'MNP', 'Northern Mariana Islands'],
        ['MQ', 'MTQ', 'Martinique'],
        ['MR', 'MRT', 'Mauritania'],
        ['MS', 'MSR', 'Montserrat'],
        ['MT', 'MLT', 'Malta'],
        ['MU', 'MUS', 'Mauritius'],
        ['MV', 'MDV', 'Maldives'],
        ['MW', 'MWI', 'Malawi'],
        ['MX', 'MEX', 'Mexico'],
        ['MY', 'MYS', 'Malaysia'],
        ['MZ', 'MOZ', 'Mozambique'],
        ['NA', 'NAM', 'Namibia'],
        ['NC', 'NCL', 'New Caledonia'],
        ['NE', 'NER', 'Niger'],
        ['NF', 'NFK', 'Norfolk Island'],
        ['NG', 'NGA', 'Nigeria'],
        ['NI', 'NIC', 'Nicaragua'],
        ['NL', 'NLD', 'Netherlands'],
        ['NO', 'NOR', 'Norway'],
        ['NP', 'NPL', 'Nepal'],
        ['NR', 'NRU', 'Nauru'],
        ['NU', 'NIU', 'Niue'],
        ['NZ', 'NZL', 'New Zealand'],
        ['OM', 'OMN', 'Oman'],
        ['PA', 'PAN', 'Panama'],
        ['PE', 'PER', 'Peru'],
        ['PF', 'PYF', 'French Polynesia'],
        ['PG', 'PNG', 'Papua New Guinea'],
        ['PH', 'PHL', 'Philippines'],
        ['PK', 'PAK', 'Pakistan'],
        ['PL', 'POL', 'Poland'],
        ['PM', 'SPM', 'Saint Pierre and Miquelon'],
        ['PN', 'PCN', 'Pitcairn'],
        ['PR', 'PRI', 'Puerto Rico'],
        ['PS', 'PSE', 'Palestine'],
        ['PT', 'PRT', 'Portugal'],
        ['PW', 'PLW', 'Palau'],
        ['PY', 'PRY', 'Paraguay'],
        ['QA', 'QAT', 'Qatar'],
        ['RE', 'REU', 'Réunion'],
        ['RO', 'ROU', 'Romania'],
        ['RS', 'SRB', 'Serbia'],
        ['RU', 'RUS', 'Russia'],
        ['RW', 'RWA', 'Rwanda'],
        ['SA', 'SAU', 'Saudi Arabia'],
        ['SB', 'SLB', 'Solomon Islands'],
        ['SC', 'SYC', 'Seychelles'],
        ['SD', 'SDN', 'Sudan'],
        ['SE', 'SWE', 'Sweden'],
        ['SG', 'SGP', 'Singapore'],
        ['SH', 'SHN', 'Saint Helena'],
        ['SI', 'SVN', 'Slovenia'],
        ['SJ', 'SJM', 'Svalbard and Jan Mayen'],
        ['SK', 'SVK', 'Slovakia'],
        ['SL', 'SLE', 'Sierra Leone'],
        ['SM', 'SMR', 'San Marino'],
        ['SN', 'SEN', 'Senegal'],
        ['SO', 'SOM', 'Somalia'],
        ['SR', 'SUR', 'Suriname'],
        ['SS', 'SSD', 'South Sudan'],
        ['ST', 'STP', 'Sao Tome and Principe'],
        ['SV', 'SLV', 'El Salvador'],
        ['SX', 'SXM', 'Sint Maarten'],
        ['SY', 'SYR', 'Syria'],
        ['SZ', 'SWZ', 'Eswatini'],
        ['TC', 'TCA', 'Turks and Caicos Islands'],
        ['TD', 'TCD', 'Chad'],
        ['TF', 'ATF', 'French Southern Territories'],
        ['TG', 'TGO', 'Togo'],
        ['TH', 'THA', 'Thailand'],
        ['TJ', 'TJK', 'Tajikistan'],
        ['TK', 'TKL', 'Tokelau'],
        ['TL', 'TLS', 'Timor-Leste'],
        ['TM', 'TKM', 'Turkmenistan'],
        ['TN', 'TUN', 'Tunisia'],
        ['TO', 'TON', 'Tonga'],
        ['TR', 'TUR', 'Türkiye'],
        ['TT', 'TTO', 'Trinidad and Tobago'],
        ['TV', 'TUV', 'Tuvalu'],
        ['TW', 'TWN', 'Taiwan'],
        ['TZ', 'TZA', 'Tanzania'],
        ['UA', 'UKR', 'Ukraine'],
        ['UG', 'UGA', 'Uganda'],
        ['UM', 'UMI', 'United States Minor Outlying Islands'],
        ['US', 'USA', 'United States'],
        ['UY', 'URY', 'Uruguay'],
        ['UZ', 'UZB', 'Uzbekistan'],
        ['VA', 'VAT', 'Vatican City'],
        ['VC', 'VCT', 'Saint Vincent and the Grenadines'],
        ['VE', 'VEN', 'Venezuela'],
        ['VG', 'VGB', 'British Virgin Islands'],
        ['VI', 'VIR', 'U.S. Virgin Islands'],
        ['VN', 'VNM', 'Vietnam'],
        ['VU', 'VUT', 'Vanuatu'],
        ['WF', 'WLF', 'Wallis and Futuna'],
        ['WS', 'WSM', 'Samoa'],
        ['YE', 'YEM', 'Yemen'],
        ['YT', 'MYT', 'Mayotte'],
        ['ZA', 'ZAF', 'South Africa'],
        ['ZM', 'ZMB', 'Zambia'],
        ['ZW', 'ZWE', 'Zimbabwe'],
    ];

    public function __construct(
        protected readonly RandomizerInterface $randomizer,
        protected readonly PersonExtensionInterface $person,
        protected readonly StringsExtensionInterface $strings,
    ) {
    }

    /**
     * First part of some city names, e.g. "North", "Port"
     */
    public function cityPrefix(): string
    {
        return $this->randomizer->randomElement(static::CITY_PREFIX);
    }

    /**
     * Ending of some city names, e.g. "ville", "ton"
     */
    public function citySuffix(): string
    {
        return $this->randomizer->randomElement(static::CITY_SUFFIX);
    }

    public function city(): string
    {
        $prefix = $this->randomizer->randomElement(static::CITY_PREFIX);
        $suffix = $this->randomizer->randomElement(static::CITY_SUFFIX);

        return match ($this->randomizer->getInt(0, 3)) {
            0 => $prefix . ' ' . $this->person->firstName() . $suffix,
            1 => $prefix . ' ' . $this->person->firstName(),
            2 => $this->person->firstName() . $suffix,
            default => $this->person->lastName() . $suffix,
        };
    }

    public function streetName(): string
    {
        $name = $this->randomizer->getBool() ? $this->person->firstName() : $this->person->lastName();

        return $name . ' ' . $this->randomizer->randomElement(static::STREET_SUFFIX);
    }

    /**
     * e.g. "Avenue"
     */
    public function streetSuffix(): string
    {
        return $this->randomizer->randomElement(static::STREET_SUFFIX);
    }

    /**
     * Apartment, suite, unit or floor, e.g. "Apt. 482", "Suite 210"
     */
    public function secondaryAddress(): string
    {
        return $this->strings->numerify($this->randomizer->randomElement(static::SECONDARY_ADDRESS));
    }

    /**
     * State / province / region name, e.g. "Central Region" (real ones in language packs)
     */
    public function state(): string
    {
        return $this->stateData()['name'];
    }

    /**
     * State / province / region code, e.g. "CR"
     */
    public function stateCode(): string
    {
        return $this->stateData()['code'];
    }

    /**
     * Matching code and name of one state / region, e.g. ['code' => 'CR', 'name' => 'Central Region']
     *
     * @return array{code: string, name: string}
     */
    public function stateData(): array
    {
        $code = $this->randomizer->randomKey(static::STATES);

        return ['code' => (string) $code, 'name' => static::STATES[$code]];
    }

    public function buildingNumber(): string
    {
        return $this->strings->numerify($this->randomizer->randomElement(static::BUILDING_NUMBER));
    }

    /**
     * Street and building number (STREET_ADDRESS_FORMAT), e.g. "Fisher Street 12"
     */
    public function streetAddress(): string
    {
        return strtr(static::STREET_ADDRESS_FORMAT, ['{street}' => $this->streetName(), '{buildingNumber}' => $this->buildingNumber()]);
    }

    public function postcode(): string
    {
        return $this->strings->numerify($this->randomizer->randomElement(static::POSTCODE));
    }

    /**
     * Address line (ADDRESS_FORMAT), e.g. "Fisher Street 12, 12345 Lakeville"
     */
    public function address(): string
    {
        return $this->formatAddress(['street' => $this->streetLine(), 'postcode' => $this->postcode(), 'city' => $this->city()]);
    }

    /**
     * County / district, e.g. "Harrison District" - fictional in default data
     */
    public function county(): string
    {
        return strtr(static::COUNTY_FORMAT, ['{name}' => $this->person->lastName()]);
    }

    /**
     * City with its region, county, postcode and coordinates, e.g. ['city' => 'North Johnhaven', 'state' => 'Central Region',
     * 'stateCode' => 'CR', 'county' => 'Harrison District', 'postcode' => '75201', 'latitude' => ..., 'longitude' => ...].
     * Default data is fictional - region and code match, coordinates are random; language packs return real places
     * where all values match.
     *
     * @param string|null $stateCode only this region (a code of STATES)
     * @return array{city: string, state: string, stateCode: string, county: string, postcode: string, latitude: float, longitude: float}
     */
    public function cityData(?string $stateCode = null): array
    {
        $state = $stateCode === null ? $this->stateData() : $this->stateByCode($stateCode);
        $coordinates = $this->coordinates();

        return [
            'city' => $this->city(),
            'state' => $state['name'],
            'stateCode' => $state['code'],
            'county' => $this->county(),
            'postcode' => $this->postcode(),
            'latitude' => $coordinates['latitude'],
            'longitude' => $coordinates['longitude'],
        ];
    }

    /**
     * cityData() with a street and the formatted address line built from these parts
     *
     * @param string|null $stateCode only this region (a code of STATES)
     * @return array{street: string, city: string, state: string, stateCode: string, county: string, postcode: string, latitude: float, longitude: float, address: string}
     */
    public function addressData(?string $stateCode = null): array
    {
        $data = ['street' => $this->streetLine()] + $this->cityData($stateCode);

        return $data + ['address' => $this->formatAddress($data)];
    }

    public function country(): string
    {
        return $this->countryData()['name'];
    }

    /**
     * ISO 3166-1 alpha-2 country code, e.g. "PL"
     */
    public function countryCode(): string
    {
        return $this->countryData()['alpha2'];
    }

    /**
     * ISO 3166-1 alpha-3 country code, e.g. "POL"
     */
    public function countryCodeAlpha3(): string
    {
        return $this->countryData()['alpha3'];
    }

    /**
     * Matching codes and name of one country, e.g. ['alpha2' => 'PL', 'alpha3' => 'POL', 'name' => 'Poland']
     *
     * @return array{alpha2: string, alpha3: string, name: string}
     */
    public function countryData(): array
    {
        [$alpha2, $alpha3, $name] = $this->randomizer->randomElement(static::COUNTRIES);

        return ['alpha2' => $alpha2, 'alpha3' => $alpha3, 'name' => $name];
    }

    /**
     * Latitude with points spread evenly over Earth surface (not over degrees, which would crowd them at poles)
     */
    public function latitude(float $min = -90.0, float $max = 90.0): float
    {
        $this->validateRange($min, $max, 90.0, 'Latitude');

        $sine = $this->randomizer->getFloat(sin(deg2rad($min)), sin(deg2rad($max)));

        return $this->roundWithin(rad2deg(asin($sine)), $min, $max);
    }

    public function longitude(float $min = -180.0, float $max = 180.0): float
    {
        $this->validateRange($min, $max, 180.0, 'Longitude');

        return $this->roundWithin($this->randomizer->getFloat($min, $max), $min, $max);
    }

    /** @return array{latitude: float, longitude: float} */
    public function coordinates(): array
    {
        return [
            'latitude' => $this->latitude(),
            'longitude' => $this->longitude(),
        ];
    }

    /**
     * First line of an address - street and number; language packs add flats where used
     */
    protected function streetLine(): string
    {
        return $this->streetAddress();
    }

    /**
     * Address line of address() and addressData() - ADDRESS_FORMAT with given parts, placeholders without a part are
     * removed with separators around them
     *
     * @param array<string, string|float> $data
     */
    protected function formatAddress(array $data): string
    {
        $replacements = [];
        foreach ($data as $key => $value) {
            $replacements['{' . $key . '}'] = (string) $value;
        }

        $address = strtr(static::ADDRESS_FORMAT, $replacements);

        return trim((string) preg_replace(['/[ ,]*\{\w+\}/', '/\s{2,}/'], ['', ' '], $address), ' ,');
    }

    /** @return array{code: string, name: string} */
    protected function stateByCode(string $stateCode): array
    {
        $code = strtoupper($stateCode);

        if (!isset(static::STATES[$code])) {
            throw new InvalidArgumentException(sprintf('Unknown state code "%s"', $stateCode));
        }

        return ['code' => $code, 'name' => static::STATES[$code]];
    }

    protected function validateRange(float $min, float $max, float $limit, string $name): void
    {
        if ($min < -$limit || $max > $limit || $min > $max) {
            throw new InvalidArgumentException(sprintf('%s range must be within -%2$s..%2$s and min <= max', $name, $limit));
        }
    }

    /**
     * Rounds to 6 decimals (~10 cm), keeping the result within range
     */
    protected function roundWithin(float $value, float $min, float $max): float
    {
        return min(max(round($value, 6), $min), $max);
    }
}
