<?php

declare(strict_types = 1);

namespace Quidam\LanguagePack\pl_PL;

use Quidam\Core\Address as CoreAddress;
use Quidam\Exception\InvalidArgumentException;

/**
 * Polish addresses: "ul. Różana 66/4, 59-900 Zgorzelec" - real cities and streets; postcode, voivodeship, county and
 * coordinates match the city (GeoNames). Polish country names.
 */
class Address extends CoreAddress
{
    /** @var list<string> */
    protected const array CITY_PREFIX = ['Nowy', 'Nowa', 'Stary', 'Stara', 'Górny', 'Dolny', 'Wielki', 'Mały'];

    /** @var list<string> */
    protected const array CITY_SUFFIX = ['ów', 'owo', 'ice', 'in', 'no', 'ew', 'ki', 'sk'];

    /**
     * Street types - "ul." (street) mostly
     *
     * @var list<string>
     */
    protected const array STREET_SUFFIX = ['ul.', 'al.', 'pl.', 'os.'];

    /** @var array<string, int> street type => weight */
    protected const array STREET_TYPE_WEIGHTS = ['ul.' => 85, 'al.' => 7, 'pl.' => 4, 'os.' => 4];

    /** @var list<string> */
    protected const array BUILDING_NUMBER = ['%', '%#', '%#', '%##', '%#A', '%#B', '%#/%', '%#/%#'];

    /** @var list<string> */
    protected const array SECONDARY_ADDRESS = ['m. %', 'm. %#', 'lok. %', 'lok. %#'];

    /**
     * Voivodeships by ISO 3166-2:PL code
     *
     * @var array<string, string>
     */
    protected const array STATES = [
        'PL-02' => 'dolnośląskie',
        'PL-04' => 'kujawsko-pomorskie',
        'PL-06' => 'lubelskie',
        'PL-08' => 'lubuskie',
        'PL-10' => 'łódzkie',
        'PL-12' => 'małopolskie',
        'PL-14' => 'mazowieckie',
        'PL-16' => 'opolskie',
        'PL-18' => 'podkarpackie',
        'PL-20' => 'podlaskie',
        'PL-22' => 'pomorskie',
        'PL-24' => 'śląskie',
        'PL-26' => 'świętokrzyskie',
        'PL-28' => 'warmińsko-mazurskie',
        'PL-30' => 'wielkopolskie',
        'PL-32' => 'zachodniopomorskie',
    ];

    /**
     * ISO 3166-1 countries with Polish names
     *
     * @var list<array{string, string, string}>
     */
    protected const array COUNTRIES = [
        ['AD', 'AND', 'Andora'],
        ['AE', 'ARE', 'Zjednoczone Emiraty Arabskie'],
        ['AF', 'AFG', 'Afganistan'],
        ['AG', 'ATG', 'Antigua i Barbuda'],
        ['AI', 'AIA', 'Anguilla'],
        ['AL', 'ALB', 'Albania'],
        ['AM', 'ARM', 'Armenia'],
        ['AO', 'AGO', 'Angola'],
        ['AQ', 'ATA', 'Antarktyda'],
        ['AR', 'ARG', 'Argentyna'],
        ['AS', 'ASM', 'Samoa Amerykańskie'],
        ['AT', 'AUT', 'Austria'],
        ['AU', 'AUS', 'Australia'],
        ['AW', 'ABW', 'Aruba'],
        ['AX', 'ALA', 'Wyspy Alandzkie'],
        ['AZ', 'AZE', 'Azerbejdżan'],
        ['BA', 'BIH', 'Bośnia i Hercegowina'],
        ['BB', 'BRB', 'Barbados'],
        ['BD', 'BGD', 'Bangladesz'],
        ['BE', 'BEL', 'Belgia'],
        ['BF', 'BFA', 'Burkina Faso'],
        ['BG', 'BGR', 'Bułgaria'],
        ['BH', 'BHR', 'Bahrajn'],
        ['BI', 'BDI', 'Burundi'],
        ['BJ', 'BEN', 'Benin'],
        ['BL', 'BLM', 'Saint-Barthélemy'],
        ['BM', 'BMU', 'Bermudy'],
        ['BN', 'BRN', 'Brunei'],
        ['BO', 'BOL', 'Boliwia'],
        ['BQ', 'BES', 'Niderlandy Karaibskie'],
        ['BR', 'BRA', 'Brazylia'],
        ['BS', 'BHS', 'Bahamy'],
        ['BT', 'BTN', 'Bhutan'],
        ['BV', 'BVT', 'Wyspa Bouveta'],
        ['BW', 'BWA', 'Botswana'],
        ['BY', 'BLR', 'Białoruś'],
        ['BZ', 'BLZ', 'Belize'],
        ['CA', 'CAN', 'Kanada'],
        ['CC', 'CCK', 'Wyspy Kokosowe'],
        ['CD', 'COD', 'Demokratyczna Republika Konga'],
        ['CF', 'CAF', 'Republika Środkowoafrykańska'],
        ['CG', 'COG', 'Kongo'],
        ['CH', 'CHE', 'Szwajcaria'],
        ['CI', 'CIV', 'Côte d’Ivoire'],
        ['CK', 'COK', 'Wyspy Cooka'],
        ['CL', 'CHL', 'Chile'],
        ['CM', 'CMR', 'Kamerun'],
        ['CN', 'CHN', 'Chiny'],
        ['CO', 'COL', 'Kolumbia'],
        ['CR', 'CRI', 'Kostaryka'],
        ['CU', 'CUB', 'Kuba'],
        ['CV', 'CPV', 'Republika Zielonego Przylądka'],
        ['CW', 'CUW', 'Curaçao'],
        ['CX', 'CXR', 'Wyspa Bożego Narodzenia'],
        ['CY', 'CYP', 'Cypr'],
        ['CZ', 'CZE', 'Czechy'],
        ['DE', 'DEU', 'Niemcy'],
        ['DJ', 'DJI', 'Dżibuti'],
        ['DK', 'DNK', 'Dania'],
        ['DM', 'DMA', 'Dominika'],
        ['DO', 'DOM', 'Dominikana'],
        ['DZ', 'DZA', 'Algieria'],
        ['EC', 'ECU', 'Ekwador'],
        ['EE', 'EST', 'Estonia'],
        ['EG', 'EGY', 'Egipt'],
        ['EH', 'ESH', 'Sahara Zachodnia'],
        ['ER', 'ERI', 'Erytrea'],
        ['ES', 'ESP', 'Hiszpania'],
        ['ET', 'ETH', 'Etiopia'],
        ['FI', 'FIN', 'Finlandia'],
        ['FJ', 'FJI', 'Fidżi'],
        ['FK', 'FLK', 'Falklandy'],
        ['FM', 'FSM', 'Mikronezja'],
        ['FO', 'FRO', 'Wyspy Owcze'],
        ['FR', 'FRA', 'Francja'],
        ['GA', 'GAB', 'Gabon'],
        ['GB', 'GBR', 'Wielka Brytania'],
        ['GD', 'GRD', 'Grenada'],
        ['GE', 'GEO', 'Gruzja'],
        ['GF', 'GUF', 'Gujana Francuska'],
        ['GG', 'GGY', 'Guernsey'],
        ['GH', 'GHA', 'Ghana'],
        ['GI', 'GIB', 'Gibraltar'],
        ['GL', 'GRL', 'Grenlandia'],
        ['GM', 'GMB', 'Gambia'],
        ['GN', 'GIN', 'Gwinea'],
        ['GP', 'GLP', 'Gwadelupa'],
        ['GQ', 'GNQ', 'Gwinea Równikowa'],
        ['GR', 'GRC', 'Grecja'],
        ['GS', 'SGS', 'Georgia Południowa i Sandwich Południowy'],
        ['GT', 'GTM', 'Gwatemala'],
        ['GU', 'GUM', 'Guam'],
        ['GW', 'GNB', 'Gwinea Bissau'],
        ['GY', 'GUY', 'Gujana'],
        ['HK', 'HKG', 'SRA Hongkong (Chiny)'],
        ['HM', 'HMD', 'Wyspy Heard i McDonalda'],
        ['HN', 'HND', 'Honduras'],
        ['HR', 'HRV', 'Chorwacja'],
        ['HT', 'HTI', 'Haiti'],
        ['HU', 'HUN', 'Węgry'],
        ['ID', 'IDN', 'Indonezja'],
        ['IE', 'IRL', 'Irlandia'],
        ['IL', 'ISR', 'Izrael'],
        ['IM', 'IMN', 'Wyspa Man'],
        ['IN', 'IND', 'Indie'],
        ['IO', 'IOT', 'Brytyjskie Terytorium Oceanu Indyjskiego'],
        ['IQ', 'IRQ', 'Irak'],
        ['IR', 'IRN', 'Iran'],
        ['IS', 'ISL', 'Islandia'],
        ['IT', 'ITA', 'Włochy'],
        ['JE', 'JEY', 'Jersey'],
        ['JM', 'JAM', 'Jamajka'],
        ['JO', 'JOR', 'Jordania'],
        ['JP', 'JPN', 'Japonia'],
        ['KE', 'KEN', 'Kenia'],
        ['KG', 'KGZ', 'Kirgistan'],
        ['KH', 'KHM', 'Kambodża'],
        ['KI', 'KIR', 'Kiribati'],
        ['KM', 'COM', 'Komory'],
        ['KN', 'KNA', 'Saint Kitts i Nevis'],
        ['KP', 'PRK', 'Korea Północna'],
        ['KR', 'KOR', 'Korea Południowa'],
        ['KW', 'KWT', 'Kuwejt'],
        ['KY', 'CYM', 'Kajmany'],
        ['KZ', 'KAZ', 'Kazachstan'],
        ['LA', 'LAO', 'Laos'],
        ['LB', 'LBN', 'Liban'],
        ['LC', 'LCA', 'Saint Lucia'],
        ['LI', 'LIE', 'Liechtenstein'],
        ['LK', 'LKA', 'Sri Lanka'],
        ['LR', 'LBR', 'Liberia'],
        ['LS', 'LSO', 'Lesotho'],
        ['LT', 'LTU', 'Litwa'],
        ['LU', 'LUX', 'Luksemburg'],
        ['LV', 'LVA', 'Łotwa'],
        ['LY', 'LBY', 'Libia'],
        ['MA', 'MAR', 'Maroko'],
        ['MC', 'MCO', 'Monako'],
        ['MD', 'MDA', 'Mołdawia'],
        ['ME', 'MNE', 'Czarnogóra'],
        ['MF', 'MAF', 'Saint-Martin'],
        ['MG', 'MDG', 'Madagaskar'],
        ['MH', 'MHL', 'Wyspy Marshalla'],
        ['MK', 'MKD', 'Macedonia Północna'],
        ['ML', 'MLI', 'Mali'],
        ['MM', 'MMR', 'Mjanma (Birma)'],
        ['MN', 'MNG', 'Mongolia'],
        ['MO', 'MAC', 'SRA Makau (Chiny)'],
        ['MP', 'MNP', 'Mariany Północne'],
        ['MQ', 'MTQ', 'Martynika'],
        ['MR', 'MRT', 'Mauretania'],
        ['MS', 'MSR', 'Montserrat'],
        ['MT', 'MLT', 'Malta'],
        ['MU', 'MUS', 'Mauritius'],
        ['MV', 'MDV', 'Malediwy'],
        ['MW', 'MWI', 'Malawi'],
        ['MX', 'MEX', 'Meksyk'],
        ['MY', 'MYS', 'Malezja'],
        ['MZ', 'MOZ', 'Mozambik'],
        ['NA', 'NAM', 'Namibia'],
        ['NC', 'NCL', 'Nowa Kaledonia'],
        ['NE', 'NER', 'Niger'],
        ['NF', 'NFK', 'Norfolk'],
        ['NG', 'NGA', 'Nigeria'],
        ['NI', 'NIC', 'Nikaragua'],
        ['NL', 'NLD', 'Holandia'],
        ['NO', 'NOR', 'Norwegia'],
        ['NP', 'NPL', 'Nepal'],
        ['NR', 'NRU', 'Nauru'],
        ['NU', 'NIU', 'Niue'],
        ['NZ', 'NZL', 'Nowa Zelandia'],
        ['OM', 'OMN', 'Oman'],
        ['PA', 'PAN', 'Panama'],
        ['PE', 'PER', 'Peru'],
        ['PF', 'PYF', 'Polinezja Francuska'],
        ['PG', 'PNG', 'Papua-Nowa Gwinea'],
        ['PH', 'PHL', 'Filipiny'],
        ['PK', 'PAK', 'Pakistan'],
        ['PL', 'POL', 'Polska'],
        ['PM', 'SPM', 'Saint-Pierre i Miquelon'],
        ['PN', 'PCN', 'Pitcairn'],
        ['PR', 'PRI', 'Portoryko'],
        ['PS', 'PSE', 'Terytoria Palestyńskie'],
        ['PT', 'PRT', 'Portugalia'],
        ['PW', 'PLW', 'Palau'],
        ['PY', 'PRY', 'Paragwaj'],
        ['QA', 'QAT', 'Katar'],
        ['RE', 'REU', 'Reunion'],
        ['RO', 'ROU', 'Rumunia'],
        ['RS', 'SRB', 'Serbia'],
        ['RU', 'RUS', 'Rosja'],
        ['RW', 'RWA', 'Rwanda'],
        ['SA', 'SAU', 'Arabia Saudyjska'],
        ['SB', 'SLB', 'Wyspy Salomona'],
        ['SC', 'SYC', 'Seszele'],
        ['SD', 'SDN', 'Sudan'],
        ['SE', 'SWE', 'Szwecja'],
        ['SG', 'SGP', 'Singapur'],
        ['SH', 'SHN', 'Wyspa Świętej Heleny'],
        ['SI', 'SVN', 'Słowenia'],
        ['SJ', 'SJM', 'Svalbard i Jan Mayen'],
        ['SK', 'SVK', 'Słowacja'],
        ['SL', 'SLE', 'Sierra Leone'],
        ['SM', 'SMR', 'San Marino'],
        ['SN', 'SEN', 'Senegal'],
        ['SO', 'SOM', 'Somalia'],
        ['SR', 'SUR', 'Surinam'],
        ['SS', 'SSD', 'Sudan Południowy'],
        ['ST', 'STP', 'Wyspy Świętego Tomasza i Książęca'],
        ['SV', 'SLV', 'Salwador'],
        ['SX', 'SXM', 'Sint Maarten'],
        ['SY', 'SYR', 'Syria'],
        ['SZ', 'SWZ', 'Eswatini'],
        ['TC', 'TCA', 'Turks i Caicos'],
        ['TD', 'TCD', 'Czad'],
        ['TF', 'ATF', 'Francuskie Terytoria Południowe i Antarktyczne'],
        ['TG', 'TGO', 'Togo'],
        ['TH', 'THA', 'Tajlandia'],
        ['TJ', 'TJK', 'Tadżykistan'],
        ['TK', 'TKL', 'Tokelau'],
        ['TL', 'TLS', 'Timor Wschodni'],
        ['TM', 'TKM', 'Turkmenistan'],
        ['TN', 'TUN', 'Tunezja'],
        ['TO', 'TON', 'Tonga'],
        ['TR', 'TUR', 'Turcja'],
        ['TT', 'TTO', 'Trynidad i Tobago'],
        ['TV', 'TUV', 'Tuvalu'],
        ['TW', 'TWN', 'Tajwan'],
        ['TZ', 'TZA', 'Tanzania'],
        ['UA', 'UKR', 'Ukraina'],
        ['UG', 'UGA', 'Uganda'],
        ['UM', 'UMI', 'Dalekie Wyspy Mniejsze Stanów Zjednoczonych'],
        ['US', 'USA', 'Stany Zjednoczone'],
        ['UY', 'URY', 'Urugwaj'],
        ['UZ', 'UZB', 'Uzbekistan'],
        ['VA', 'VAT', 'Watykan'],
        ['VC', 'VCT', 'Saint Vincent i Grenadyny'],
        ['VE', 'VEN', 'Wenezuela'],
        ['VG', 'VGB', 'Brytyjskie Wyspy Dziewicze'],
        ['VI', 'VIR', 'Wyspy Dziewicze Stanów Zjednoczonych'],
        ['VN', 'VNM', 'Wietnam'],
        ['VU', 'VUT', 'Vanuatu'],
        ['WF', 'WLF', 'Wallis i Futuna'],
        ['WS', 'WSM', 'Samoa'],
        ['YE', 'YEM', 'Jemen'],
        ['YT', 'MYT', 'Majotta'],
        ['ZA', 'ZAF', 'Republika Południowej Afryki'],
        ['ZM', 'ZMB', 'Zambia'],
        ['ZW', 'ZWE', 'Zimbabwe'],
    ];

    /**
     * Real places: [city, region code, county, [[postcode, latitude, longitude], ...]] - postcodes of the place with
     * their coordinates, from GeoNames (CC BY 4.0)
     *
     * @var list<array{string, string, string, list<array{string, float, float}>}>
     */
    protected const array PLACES = [
        ['Babienica', 'PL-24', 'powiat lubliniecki', [
            ['42-287', 50.6246, 18.972],
        ]],
        ['Bartoszyce', 'PL-28', 'powiat bartoszycki', [
            ['11-200', 54.2528, 20.8151], ['11-201', 54.2635, 20.7996], ['11-204', 54.2529, 20.8032],
            ['11-205', 54.2592, 20.822], ['11-206', 54.2589, 20.7923],
        ]],
        ['Bełchatów', 'PL-10', 'powiat bełchatowski', [
            ['97-400', 51.3614, 19.3724], ['97-402', 51.3578, 19.3557], ['97-405', 51.335, 19.3761],
            ['97-406', 51.3704, 19.3587], ['97-407', 51.3499, 19.3642], ['97-408', 51.3564, 19.3871],
        ]],
        ['Bezrzecze', 'PL-32', 'powiat policki', [
            ['71-216', 53.4588, 14.4571], ['71-218', 53.4609, 14.4505], ['71-220', 53.4604, 14.4609],
            ['71-230', 53.4603, 14.4622], ['71-234', 53.4548, 14.4602], ['71-236', 53.4559, 14.4632],
        ]],
        ['Będzin', 'PL-24', 'powiat będziński', [
            ['42-500', 50.3233, 19.134], ['42-504', 50.3524, 19.1493], ['42-506', 50.348, 19.0884],
        ]],
        ['Biała Podlaska', 'PL-06', 'powiat m. Biała Podlaska', [
            ['21-500', 52.0278, 23.1216],
        ]],
        ['Białystok', 'PL-20', 'powiat m. Białystok', [
            ['15-001', 53.1411, 23.1766], ['15-141', 53.1463, 23.1857], ['15-265', 53.1218, 23.181],
            ['15-422', 53.1315, 23.1567], ['15-584', 53.0864, 23.2119], ['15-727', 53.1303, 23.1185],
        ]],
        ['Bielawa', 'PL-02', 'powiat dzierżoniowski', [
            ['58-260', 50.6824, 16.6158],
        ]],
        ['Bielsko-Biała', 'PL-24', 'powiat m. Bielsko-Biała', [
            ['43-000', 49.8662, 19.1155], ['43-301', 49.7908, 19.0758], ['43-307', 49.8148, 19.0081],
            ['43-311', 49.808, 19.0625], ['43-317', 49.8132, 19.0286], ['43-344', 49.8517, 19.0869],
        ]],
        ['Bieruń', 'PL-24', 'powiat bieruńsko-lędziński', [
            ['43-144', 50.0955, 19.1381], ['43-150', 50.0867, 19.0952], ['43-155', 50.0744, 19.1721],
        ]],
        ['Bochnia', 'PL-12', 'powiat bocheński', [
            ['32-700', 49.9696, 20.4314], ['32-701', 49.9862, 20.4297], ['32-703', 49.9674, 20.4116],
            ['32-704', 49.9765, 20.4327],
        ]],
        ['Bogaczów', 'PL-02', 'powiat jaworski', [
            ['59-424', 51.0721, 16.0677],
        ]],
        ['Bogatynia', 'PL-02', 'powiat zgorzelecki', [
            ['59-916', 50.9447, 14.9101], ['59-920', 50.9031, 14.9632],
        ]],
        ['Boguszów-Gorce', 'PL-02', 'powiat wałbrzyski', [
            ['58-370', 50.755, 16.1993],
        ]],
        ['Bolesławiec', 'PL-02', 'powiat bolesławiecki', [
            ['59-700', 51.2628, 15.5599], ['59-701', 51.2551, 15.5767], ['59-702', 51.2515, 15.5775],
            ['59-703', 51.2637, 15.5619],
        ]],
        ['Braniewo', 'PL-28', 'powiat braniewski', [
            ['14-500', 54.3815, 19.8213],
        ]],
        ['Brodnica', 'PL-04', 'powiat brodnicki', [
            ['87-300', 53.2544, 19.4016], ['87-301', 53.2468, 19.4088], ['87-302', 53.2613, 19.3838],
            ['87-303', 53.2694, 19.4108],
        ]],
        ['Brzeg', 'PL-16', 'powiat brzeski', [
            ['49-030', 50.8598, 17.44], ['49-300', 50.8583, 17.468], ['49-304', 50.8513, 17.4516],
            ['49-305', 50.847, 17.4683], ['49-306', 50.8643, 17.4671],
        ]],
        ['Busko-Zdrój', 'PL-26', 'powiat buski', [
            ['28-100', 50.4701, 20.7193],
        ]],
        ['Bydgoszcz', 'PL-04', 'powiat m. Bydgoszcz', [
            ['85-000', 53.1502, 17.9778], ['85-121', 53.1186, 17.9956], ['85-309', 53.1146, 17.975],
            ['85-429', 53.1372, 17.9575], ['85-566', 53.1704, 17.9646], ['85-763', 53.1441, 17.954],
        ]],
        ['Bytom', 'PL-24', 'powiat m. Bytom', [
            ['41-900', 50.3627, 18.8846], ['41-905', 50.3443, 18.8624], ['41-907', 50.3367, 18.8939],
            ['41-909', 50.3246, 18.9329], ['41-914', 50.377, 18.8878], ['41-933', 50.3947, 18.8784],
        ]],
        ['Chełm', 'PL-06', 'powiat m. Chełm', [
            ['22-100', 51.1355, 23.4743],
        ]],
        ['Chojnice', 'PL-22', 'powiat chojnicki', [
            ['89-600', 53.6978, 17.5563],
        ]],
        ['Chorzów', 'PL-24', 'powiat m. Chorzów', [
            ['41-500', 50.3006, 18.9463], ['41-503', 50.3079, 18.9772], ['41-505', 50.3037, 18.9498],
            ['41-507', 50.3092, 18.9368], ['41-516', 50.2868, 18.9617], ['41-518', 50.2656, 18.944],
        ]],
        ['Chrzanów', 'PL-12', 'powiat chrzanowski', [
            ['32-500', 50.1355, 19.4022], ['32-501', 50.1431, 19.4179], ['32-502', 50.142, 19.4123],
            ['32-503', 50.1478, 19.4099],
        ]],
        ['Ciechanów', 'PL-14', 'powiat ciechanowski', [
            ['06-400', 52.8766, 20.6084], ['06-401', 52.8727, 20.6003], ['06-409', 52.8656, 20.6298],
            ['06-410', 52.8872, 20.5878], ['06-413', 52.8798, 20.6326],
        ]],
        ['Cieszyn', 'PL-24', 'powiat cieszyński', [
            ['43-400', 49.7538, 18.6415],
        ]],
        ['Czaplinek', 'PL-32', 'powiat drawski', [
            ['78-550', 53.5571, 16.2348],
        ]],
        ['Czarna Woda', 'PL-22', 'powiat starogardzki', [
            ['83-262', 53.8412, 18.1014],
        ]],
        ['Czechowice-Dziedzice', 'PL-24', 'powiat bielski', [
            ['43-500', 49.9022, 19.0051], ['43-502', 49.9109, 19.0039],
        ]],
        ['Czeladź', 'PL-24', 'powiat będziński', [
            ['41-250', 50.3165, 19.0723], ['41-253', 50.3047, 19.0988], ['41-254', 50.3228, 19.0743],
        ]],
        ['Czerwionka-Leszczyny', 'PL-24', 'powiat rybnicki', [
            ['44-230', 50.163, 18.6823], ['44-238', 50.1378, 18.6356],
        ]],
        ['Częstochowa', 'PL-24', 'powiat m. Częstochowa', [
            ['42-130', 50.8295, 19.0515], ['42-207', 50.7859, 19.1494], ['42-215', 50.8452, 19.1077],
            ['42-218', 50.8275, 19.105], ['42-226', 50.8077, 19.0893], ['42-271', 50.7591, 19.0639],
        ]],
        ['Darłowo', 'PL-32', 'powiat sławieński', [
            ['76-150', 54.4247, 16.4059],
        ]],
        ['Dąbrowa Górnicza', 'PL-24', 'powiat m. Dąbrowa Górnicza', [
            ['42-520', 50.383, 19.2594], ['42-522', 50.3744, 19.3554], ['42-523', 50.3515, 19.338],
            ['42-524', 50.344, 19.4186], ['42-525', 50.3479, 19.4213], ['42-530', 50.3086, 19.2723],
        ]],
        ['Dębica', 'PL-18', 'powiat dębicki', [
            ['39-200', 50.0467, 21.4118],
        ]],
        ['Dębogórze', 'PL-22', 'powiat pucki', [
            ['81-198', 54.5885, 18.4621],
        ]],
        ['Dzierżoniów', 'PL-02', 'powiat dzierżoniowski', [
            ['58-200', 50.7324, 16.6477],
        ]],
        ['Elbląg', 'PL-28', 'powiat m. Elbląg', [
            ['82-300', 54.172, 19.4196], ['82-302', 54.1733, 19.4078], ['82-308', 54.1658, 19.4086],
            ['82-310', 54.1501, 19.4183], ['82-312', 54.1869, 19.3992], ['82-314', 54.1874, 19.4156],
        ]],
        ['Ełk', 'PL-28', 'powiat ełcki', [
            ['19-300', 53.8207, 22.3618],
        ]],
        ['Franciszków', 'PL-14', 'powiat żyrardowski', [
            ['96-315', 52.0128, 20.3223],
        ]],
        ['Gdańsk', 'PL-22', 'powiat m. Gdańsk', [
            ['80-001', 54.3023, 18.6308], ['80-140', 54.3517, 18.6183], ['80-286', 54.3652, 18.5936],
            ['80-399', 54.4061, 18.5917], ['80-560', 54.3861, 18.6384], ['80-756', 54.3506, 18.6657],
        ]],
        ['Gdynia', 'PL-22', 'powiat m. Gdynia', [
            ['81-000', 54.545, 18.4606], ['81-116', 54.549, 18.5378], ['81-222', 54.5311, 18.4951],
            ['81-350', 54.5236, 18.5432], ['81-441', 54.5046, 18.5417], ['81-557', 54.4852, 18.5315],
        ]],
        ['Giżycko', 'PL-28', 'powiat giżycki', [
            ['11-500', 54.0419, 21.7695],
        ]],
        ['Gliwice', 'PL-24', 'powiat m. Gliwice', [
            ['44-100', 50.2909, 18.6667], ['44-105', 50.3234, 18.7001], ['44-110', 50.3411, 18.6237],
            ['44-114', 50.2784, 18.6539], ['44-121', 50.3054, 18.629], ['44-145', 50.3682, 18.6402],
        ]],
        ['Głogów', 'PL-02', 'powiat głogowski', [
            ['67-200', 51.6556, 16.09],
        ]],
        ['Gniezno', 'PL-30', 'powiat gnieźnieński', [
            ['62-000', 52.512, 17.5985], ['62-200', 52.5291, 17.6002], ['62-201', 52.5482, 17.6004],
            ['62-204', 52.5418, 17.6228], ['62-220', 52.5311, 17.5585],
        ]],
        ['Gołubie', 'PL-22', 'powiat kartuski', [
            ['83-316', 54.2104, 18.0343],
        ]],
        ['Gorlice', 'PL-12', 'powiat gorlicki', [
            ['38-300', 49.6599, 21.1649], ['38-301', 49.653, 21.1579], ['38-320', 49.6702, 21.1788],
        ]],
        ['Gorzów Wielkopolski', 'PL-08', 'powiat m. Gorzów Wielkopolski', [
            ['66-400', 52.7358, 15.2369],
        ]],
        ['Grodzisk Mazowiecki', 'PL-14', 'powiat grodziski', [
            ['05-825', 52.1046, 20.6335], ['05-827', 52.1108, 20.6432],
        ]],
        ['Grudziądz', 'PL-04', 'powiat m. Grudziądz', [
            ['86-300', 53.4822, 18.7719],
        ]],
        ['Ilkowice', 'PL-12', 'powiat tarnowski', [
            ['33-131', 50.0919, 20.9057], ['33-132', 50.0997, 20.8988],
        ]],
        ['Iława', 'PL-28', 'powiat iławski', [
            ['14-200', 53.5955, 19.5683],
        ]],
        ['Inowrocław', 'PL-04', 'powiat inowrocławski', [
            ['88-100', 52.7867, 18.2609],
        ]],
        ['Jadowniki', 'PL-12', 'powiat brzeski', [
            ['32-800', 49.9692, 20.6453], ['32-851', 49.9687, 20.6451],
        ]],
        ['Jarosław', 'PL-18', 'powiat jarosławski', [
            ['37-500', 50.0212, 22.677], ['37-520', 50.0348, 22.6765], ['37-750', 50.0309, 22.6892],
        ]],
        ['Jaroszowa Wola', 'PL-14', 'powiat piaseczyński', [
            ['05-505', 51.9618, 21.0015], ['05-540', 51.9747, 21.0127],
        ]],
        ['Jasło', 'PL-18', 'powiat jasielski', [
            ['38-200', 49.7447, 21.4713], ['38-201', 49.7376, 21.4622], ['38-202', 49.753, 21.4779],
        ]],
        ['Jastarnia', 'PL-22', 'powiat pucki', [
            ['84-140', 54.7004, 18.6736],
        ]],
        ['Jastrzębie-Zdrój', 'PL-24', 'powiat m. Jastrzębie-Zdrój', [
            ['44-268', 49.9978, 18.6356], ['44-330', 49.9496, 18.5739], ['44-335', 49.9559, 18.5991],
            ['44-336', 49.9393, 18.6508], ['44-337', 49.9217, 18.6023], ['44-338', 49.9362, 18.5433],
        ]],
        ['Jawor', 'PL-02', 'powiat jaworski', [
            ['59-400', 51.0541, 16.1889],
        ]],
        ['Jaworzno', 'PL-24', 'powiat m. Jaworzno', [
            ['43-600', 50.2045, 19.2918], ['43-602', 50.2399, 19.2808], ['43-603', 50.2286, 19.2182],
            ['43-606', 50.1779, 19.3002], ['43-607', 50.1643, 19.3182], ['43-608', 50.1645, 19.251],
        ]],
        ['Jelcz-Laskowice', 'PL-02', 'powiat oławski', [
            ['55-220', 51.0335, 17.3352],
        ]],
        ['Jelenia Góra', 'PL-02', 'powiat m. Jelenia Góra', [
            ['58-500', 50.8932, 15.7405], ['58-506', 50.9144, 15.7658], ['58-560', 50.8643, 15.6866],
            ['58-570', 50.8411, 15.6391],
        ]],
        ['Jemielnica', 'PL-16', 'powiat strzelecki', [
            ['47-133', 50.5427, 18.3776], ['47-134', 50.5221, 18.4048],
        ]],
        ['Jeziorna', 'PL-08', 'powiat nowosolski', [
            ['67-100', 51.8882, 15.9485], ['67-108', 51.8887, 15.9502],
        ]],
        ['Józefów', 'PL-14', 'powiat otwocki', [
            ['05-410', 52.1482, 21.2184], ['05-420', 52.1326, 21.2269],
        ]],
        ['Kalisz', 'PL-30', 'powiat m. Kalisz', [
            ['62-800', 51.7498, 18.0869],
        ]],
        ['Kamienica Królewska', 'PL-22', 'powiat kartuski', [
            ['83-342', 54.3932, 17.8901],
        ]],
        ['Kamieniec Ząbkowicki', 'PL-02', 'powiat ząbkowicki', [
            ['57-230', 50.526, 16.8754], ['57-240', 50.5356, 16.897],
        ]],
        ['Kamień', 'PL-18', 'powiat rzeszowski', [
            ['36-053', 50.3259, 22.1192],
        ]],
        ['Katowice', 'PL-24', 'powiat m. Katowice', [
            ['40-001', 50.1965, 18.9717], ['40-119', 50.2714, 19.0128], ['40-301', 50.2769, 19.054],
            ['40-485', 50.2245, 19.0664], ['40-620', 50.2156, 18.9926], ['40-733', 50.2232, 18.9661],
        ]],
        ['Kędzierzyn-Koźle', 'PL-16', 'powiat kędzierzyńsko-kozielski', [
            ['47-200', 50.3405, 18.1374], ['47-205', 50.3482, 18.1549], ['47-220', 50.3491, 18.2133],
            ['47-223', 50.3224, 18.2448], ['47-224', 50.3381, 18.1971], ['47-230', 50.3769, 18.2872],
        ]],
        ['Kętrzyn', 'PL-28', 'powiat kętrzyński', [
            ['11-400', 54.0789, 21.3725], ['11-402', 54.0769, 21.3703], ['11-409', 54.0827, 21.3721],
            ['11-440', 54.0756, 21.3714],
        ]],
        ['Kielce', 'PL-26', 'powiat m. Kielce', [
            ['25-002', 50.873, 20.6216], ['25-141', 50.8407, 20.6209], ['25-330', 50.8661, 20.6698],
            ['25-426', 50.8764, 20.6187], ['25-552', 50.8867, 20.6361], ['25-677', 50.906, 20.5994],
        ]],
        ['Kluczbork', 'PL-16', 'powiat kluczborski', [
            ['46-200', 50.9697, 18.2129], ['46-201', 50.9615, 18.219], ['46-203', 50.9813, 18.2121],
            ['46-230', 50.9796, 18.2149],
        ]],
        ['Kłobuck', 'PL-24', 'powiat kłobucki', [
            ['42-100', 50.9054, 18.9398], ['42-125', 50.8911, 18.9898],
        ]],
        ['Kłodzko', 'PL-02', 'powiat kłodzki', [
            ['57-300', 50.4395, 16.6556],
        ]],
        ['Knurów', 'PL-24', 'powiat gliwicki', [
            ['44-190', 50.2237, 18.6733], ['44-191', 50.2294, 18.6589], ['44-193', 50.2017, 18.6408],
            ['44-194', 50.2287, 18.6607], ['44-196', 50.2365, 18.6523],
        ]],
        ['Kolonowskie', 'PL-16', 'powiat strzelecki', [
            ['47-100', 50.6486, 18.3792], ['47-110', 50.6515, 18.3859], ['47-113', 50.6605, 18.3605],
        ]],
        ['Koło', 'PL-30', 'powiat kolski', [
            ['62-600', 52.1978, 18.638],
        ]],
        ['Kołobrzeg', 'PL-32', 'powiat kołobrzeski', [
            ['78-100', 54.1705, 15.5654], ['78-102', 54.162, 15.5507], ['78-103', 54.1842, 15.5728],
            ['78-104', 54.1855, 15.5548], ['78-107', 54.1708, 15.5715],
        ]],
        ['Konin', 'PL-30', 'powiat m. Konin', [
            ['62-500', 52.2279, 18.2567], ['62-510', 52.2309, 18.295],
        ]],
        ['Konstancin-Jeziorna', 'PL-14', 'powiat piaseczyński', [
            ['05-510', 52.0795, 21.0978], ['05-520', 52.0939, 21.1195],
        ]],
        ['Koszalin', 'PL-32', 'powiat m. Koszalin', [
            ['75-001', 54.1903, 16.1879], ['75-205', 54.1921, 16.1755], ['75-355', 54.2032, 16.1823],
            ['75-451', 54.2072, 16.1949], ['75-607', 54.1915, 16.2027], ['75-697', 54.1904, 16.2302],
        ]],
        ['Koszwały', 'PL-22', 'powiat gdański', [
            ['83-011', 54.2945, 18.8091],
        ]],
        ['Kościan', 'PL-30', 'powiat kościański', [
            ['64-000', 52.0846, 16.6407],
        ]],
        ['Kościerzyna', 'PL-22', 'powiat kościerski', [
            ['82-400', 54.117, 17.9628],
        ]],
        ['Kozienice', 'PL-14', 'powiat kozienicki', [
            ['26-900', 51.5852, 21.5437],
        ]],
        ['Kraków', 'PL-12', 'powiat m. Kraków', [
            ['31-001', 50.0571, 19.9376], ['31-127', 50.0669, 19.9263], ['31-312', 50.0843, 19.91],
            ['31-475', 50.0792, 19.9644], ['31-638', 50.0942, 20.0267], ['31-876', 50.0856, 19.9868],
        ]],
        ['Krapkowice', 'PL-16', 'powiat krapkowicki', [
            ['47-300', 50.4736, 17.9589], ['47-303', 50.4834, 17.979], ['47-340', 50.4822, 17.9488],
        ]],
        ['Kraśnik', 'PL-06', 'powiat kraśnicki', [
            ['23-200', 50.9292, 22.2254], ['23-201', 50.9352, 22.2165], ['23-202', 50.9297, 22.2222],
            ['23-204', 50.9588, 22.1641], ['23-206', 50.9569, 22.1548], ['23-210', 50.9363, 22.2012],
        ]],
        ['Krępiec', 'PL-06', 'powiat świdnicki', [
            ['21-007', 51.198, 22.722], ['21-040', 51.2049, 22.712],
        ]],
        ['Krosno', 'PL-18', 'powiat m. Krosno', [
            ['38-400', 49.6913, 21.7592], ['38-401', 49.6957, 21.7191], ['38-402', 49.6841, 21.7792],
            ['38-404', 49.7155, 21.7132], ['38-411', 49.6798, 21.7577],
        ]],
        ['Krotoszyn', 'PL-30', 'powiat krotoszyński', [
            ['63-700', 51.6976, 17.4355],
        ]],
        ['Kutno', 'PL-10', 'powiat kutnowski', [
            ['99-300', 52.23, 19.355], ['99-302', 52.2412, 19.3531],
        ]],
        ['Kuźnica Masłońska', 'PL-24', 'powiat zawierciański', [
            ['42-450', 50.4575, 19.399],
        ]],
        ['Kwidzyn', 'PL-22', 'powiat kwidzyński', [
            ['82-500', 53.7328, 18.9361], ['82-501', 53.7286, 18.9274], ['82-502', 53.7268, 18.9378],
            ['82-503', 53.732, 18.9413], ['82-505', 53.737, 18.9498],
        ]],
        ['Legionowo', 'PL-14', 'powiat legionowski', [
            ['05-101', 52.4037, 20.927], ['05-118', 52.4026, 20.9241], ['05-119', 52.4136, 20.939],
            ['05-120', 52.4066, 20.9288],
        ]],
        ['Legnica', 'PL-02', 'powiat m. Legnica', [
            ['59-200', 51.2123, 16.1634], ['59-206', 51.1976, 16.1773], ['59-211', 51.2046, 16.213],
            ['59-213', 51.2058, 16.1512], ['59-220', 51.2018, 16.1742], ['59-243', 51.1927, 16.1869],
        ]],
        ['Leszno', 'PL-30', 'powiat m. Leszno', [
            ['64-100', 51.8443, 16.5763],
        ]],
        ['Lębork', 'PL-22', 'powiat lęborski', [
            ['84-300', 54.5383, 17.7431],
        ]],
        ['Lędziny', 'PL-24', 'powiat bieruńsko-lędziński', [
            ['43-140', 50.1283, 19.1074], ['43-143', 50.145, 19.1375], ['43-144', 50.1062, 19.1526],
        ]],
        ['Lidzbark Warmiński', 'PL-28', 'powiat lidzbarski', [
            ['11-100', 54.126, 20.5788],
        ]],
        ['Lubartów', 'PL-06', 'powiat lubartowski', [
            ['21-100', 51.4631, 22.6065], ['21-103', 51.4802, 22.6001],
        ]],
        ['Lubin', 'PL-02', 'powiat lubiński', [
            ['59-140', 51.399, 16.1766], ['59-300', 51.3965, 16.1927], ['59-301', 51.417, 16.188],
            ['59-305', 51.4032, 16.1716], ['59-330', 51.4058, 16.1716],
        ]],
        ['Lublin', 'PL-06', 'powiat m. Lublin', [
            ['20-001', 51.2461, 22.5544], ['20-109', 51.2461, 22.5663], ['20-277', 51.2225, 22.642],
            ['20-408', 51.2325, 22.5694], ['20-533', 51.2279, 22.5343], ['20-730', 51.2294, 22.4972],
        ]],
        ['Lubliniec', 'PL-24', 'powiat lubliniecki', [
            ['42-700', 50.6691, 18.6837],
        ]],
        ['Lubojenka', 'PL-24', 'powiat częstochowski', [
            ['42-233', 50.8968, 19.1657],
        ]],
        ['Luboń', 'PL-30', 'powiat poznański', [
            ['62-030', 52.3407, 16.8741],
        ]],
        ['Ławy', 'PL-14', 'powiat ostrołęcki', [
            ['07-410', 53.0699, 21.6295], ['07-411', 53.0685, 21.6311],
        ]],
        ['Łaziska Górne', 'PL-24', 'powiat mikołowski', [
            ['43-170', 50.1516, 18.8371], ['43-173', 50.1442, 18.8701],
        ]],
        ['Łęczna', 'PL-06', 'powiat łęczyński', [
            ['21-010', 51.3014, 22.8806], ['21-011', 51.2888, 22.8845], ['21-017', 51.2908, 22.8887],
        ]],
        ['Łomianki', 'PL-14', 'powiat warszawski zachodni', [
            ['05-092', 52.3354, 20.8877],
        ]],
        ['Łomża', 'PL-20', 'powiat m. Łomża', [
            ['18-400', 53.1738, 22.062],
        ]],
        ['Łoś', 'PL-14', 'powiat piaseczyński', [
            ['05-504', 51.981, 20.9553], ['05-505', 51.9727, 20.9478],
        ]],
        ['Łowicz', 'PL-10', 'powiat łowicki', [
            ['99-400', 52.106, 19.9383],
        ]],
        ['Łódź', 'PL-10', 'powiat m. Łódź', [
            ['91-001', 51.7848, 19.4592], ['91-076', 51.7757, 19.4296], ['91-203', 51.8049, 19.3929],
            ['91-362', 51.8198, 19.4294], ['91-493', 51.8151, 19.4472], ['91-738', 51.7842, 19.4786],
        ]],
        ['Magdalenka', 'PL-14', 'powiat piaseczyński', [
            ['05-506', 52.0925, 20.9076], ['05-552', 52.0802, 20.8991],
        ]],
        ['Malbork', 'PL-22', 'powiat malborski', [
            ['82-103', 54.0381, 19.0174], ['82-200', 54.0294, 19.0346], ['82-224', 54.0397, 19.0186],
        ]],
        ['Marylka', 'PL-14', 'powiat piaseczyński', [
            ['05-504', 51.9891, 20.8888], ['05-555', 51.9856, 20.8835],
        ]],
        ['Mielec', 'PL-18', 'powiat mielecki', [
            ['39-000', 50.298, 21.4121], ['39-300', 50.2885, 21.4484], ['39-301', 50.2941, 21.4372],
            ['39-302', 50.2949, 21.4188], ['39-309', 50.2852, 21.4471], ['39-313', 50.2871, 21.4192],
        ]],
        ['Mikołów', 'PL-24', 'powiat mikołowski', [
            ['43-190', 50.1829, 18.8793],
        ]],
        ['Mokrzyska', 'PL-12', 'powiat brzeski', [
            ['32-800', 50.0082, 20.6169],
        ]],
        ['Mysłowice', 'PL-24', 'powiat m. Mysłowice', [
            ['41-400', 50.2094, 19.136],
        ]],
        ['Myszków', 'PL-24', 'powiat myszkowski', [
            ['42-300', 50.5712, 19.3309], ['42-303', 50.5743, 19.3261],
        ]],
        ['Nowa Ruda', 'PL-02', 'powiat kłodzki', [
            ['57-400', 50.5709, 16.5221], ['57-401', 50.6054, 16.4926], ['57-402', 50.5329, 16.5562],
        ]],
        ['Nowa Sól', 'PL-08', 'powiat nowosolski', [
            ['67-100', 51.8028, 15.7093],
        ]],
        ['Nowe Kramsko', 'PL-08', 'powiat zielonogórski', [
            ['66-111', 52.1398, 15.7709],
        ]],
        ['Nowy Dwór Mazowiecki', 'PL-14', 'powiat nowodworski', [
            ['05-100', 52.4275, 20.7262], ['05-101', 52.4174, 20.7389], ['05-102', 52.4467, 20.7024],
            ['05-160', 52.4371, 20.6688],
        ]],
        ['Nowy Sącz', 'PL-12', 'powiat m. Nowy Sącz', [
            ['33-300', 49.6094, 20.7045],
        ]],
        ['Nowy Targ', 'PL-12', 'powiat nowotarski', [
            ['34-400', 49.4869, 20.0289],
        ]],
        ['Nysa', 'PL-16', 'powiat nyski', [
            ['48-300', 50.4691, 17.3358], ['48-303', 50.4574, 17.3434], ['48-304', 50.457, 17.3161],
        ]],
        ['Olkusz', 'PL-12', 'powiat olkuski', [
            ['32-300', 50.2818, 19.5634],
        ]],
        ['Olsztyn', 'PL-28', 'powiat m. Olsztyn', [
            ['10-003', 53.7773, 20.4797], ['10-130', 53.7771, 20.4704], ['10-254', 53.7929, 20.4911],
            ['10-410', 53.7854, 20.5336], ['10-572', 53.7767, 20.4802], ['10-702', 53.752, 20.4602],
        ]],
        ['Opole', 'PL-16', 'powiat m. Opole', [
            ['45-001', 50.6637, 17.9413], ['45-129', 50.6948, 17.9143], ['45-353', 50.663, 17.94],
            ['45-514', 50.6465, 17.9766], ['45-634', 50.6141, 17.9652], ['45-766', 50.6588, 17.8889],
        ]],
        ['Orzesze', 'PL-24', 'powiat mikołowski', [
            ['43-180', 50.1236, 18.7792],
        ]],
        ['Osówiec', 'PL-04', 'powiat bydgoski', [
            ['86-014', 53.1742, 17.8825],
        ]],
        ['Ostrołęka', 'PL-14', 'powiat m. Ostrołęka', [
            ['07-401', 53.0977, 21.6132], ['07-409', 53.075, 21.5672], ['07-410', 53.0786, 21.5767],
            ['07-417', 53.0572, 21.6112],
        ]],
        ['Ostrowiec Świętokrzyski', 'PL-26', 'powiat ostrowiecki', [
            ['27-400', 50.9467, 21.3869],
        ]],
        ['Ostróda', 'PL-28', 'powiat ostródzki', [
            ['14-100', 53.7048, 19.9636],
        ]],
        ['Ostrów Mazowiecka', 'PL-14', 'powiat ostrowski', [
            ['07-300', 52.8022, 21.8883],
        ]],
        ['Ostrów Wielkopolski', 'PL-30', 'powiat ostrowski', [
            ['63-400', 51.6466, 17.8113], ['63-401', 51.6476, 17.8153],
        ]],
        ['Ostrzeszów', 'PL-30', 'powiat ostrzeszowski', [
            ['63-500', 51.4278, 17.927],
        ]],
        ['Oświęcim', 'PL-12', 'powiat oświęcimski', [
            ['32-600', 50.0352, 19.2343],
        ]],
        ['Otwock', 'PL-14', 'powiat otwocki', [
            ['05-400', 52.1134, 21.2743],
        ]],
        ['Pabianice', 'PL-10', 'powiat pabianicki', [
            ['95-200', 51.66, 19.3533], ['95-201', 51.6645, 19.3461], ['95-202', 51.658, 19.3712],
            ['95-204', 51.6609, 19.3286], ['95-205', 51.6568, 19.3756], ['95-207', 51.664, 19.352],
        ]],
        ['Pawłowice', 'PL-24', 'powiat pszczyński', [
            ['43-250', 49.9579, 18.7155], ['43-251', 49.9608, 18.7089],
        ]],
        ['Pęcice', 'PL-14', 'powiat pruszkowski', [
            ['05-806', 52.15, 20.8421],
        ]],
        ['Piaseczno', 'PL-14', 'powiat piaseczyński', [
            ['05-500', 52.0699, 21.0167],
        ]],
        ['Piekary Śląskie', 'PL-24', 'powiat m. Piekary Śląskie', [
            ['41-940', 50.3833, 18.9424], ['41-943', 50.3927, 18.967], ['41-945', 50.4107, 18.9463],
            ['41-946', 50.3477, 18.9786], ['41-948', 50.3452, 19.0079], ['41-949', 50.3681, 18.9793],
        ]],
        ['Pieszyce', 'PL-02', 'powiat dzierżoniowski', [
            ['58-209', 50.7203, 16.5875], ['58-250', 50.7106, 16.5809], ['58-500', 50.716, 16.5899],
        ]],
        ['Pilchowo', 'PL-32', 'powiat policki', [
            ['71-370', 53.4988, 14.4915], ['71-371', 53.4952, 14.4853],
        ]],
        ['Piła', 'PL-30', 'powiat pilski', [
            ['64-920', 53.1505, 16.7461],
        ]],
        ['Piotrków Trybunalski', 'PL-10', 'powiat m. Piotrków Trybunalski', [
            ['97-300', 51.4111, 19.6895],
        ]],
        ['Pisz', 'PL-28', 'powiat piski', [
            ['12-200', 53.628, 21.8165],
        ]],
        ['Płazów', 'PL-18', 'powiat lubaczowski', [
            ['37-610', 50.3117, 23.2449], ['37-614', 50.3144, 23.2457],
        ]],
        ['Płock', 'PL-14', 'powiat m. Płock', [
            ['09-400', 52.5555, 19.6758], ['09-402', 52.5388, 19.7091], ['09-405', 52.5526, 19.6763],
            ['09-408', 52.5177, 19.7692], ['09-410', 52.5339, 19.7618], ['09-418', 52.5395, 19.712],
        ]],
        ['Police', 'PL-32', 'powiat policki', [
            ['72-009', 53.5393, 14.5393], ['72-010', 53.5428, 14.5612], ['72-011', 53.5415, 14.5586],
            ['72-012', 53.5424, 14.5715], ['72-015', 53.5931, 14.5387],
        ]],
        ['Postęp', 'PL-24', 'powiat myszkowski', [
            ['42-350', 50.5949, 19.2731],
        ]],
        ['Poznań', 'PL-30', 'powiat m. Poznań', [
            ['61-001', 52.4173, 16.96], ['61-205', 52.3923, 16.9568], ['61-373', 52.3802, 16.9467],
            ['61-502', 52.3867, 16.9185], ['61-645', 52.4422, 16.9274], ['61-761', 52.4097, 16.9351],
        ]],
        ['Pruszcz Gdański', 'PL-22', 'powiat gdański', [
            ['83-000', 54.2589, 18.6517],
        ]],
        ['Pruszków', 'PL-14', 'powiat pruszkowski', [
            ['05-800', 52.1697, 20.8075],
        ]],
        ['Przemyśl', 'PL-18', 'powiat m. Przemyśl', [
            ['37-700', 49.7863, 22.7718],
        ]],
        ['Przędzel', 'PL-18', 'powiat niżański', [
            ['37-420', 50.4882, 22.2241],
        ]],
        ['Pszczyna', 'PL-24', 'powiat pszczyński', [
            ['43-100', 49.976, 18.9383], ['43-200', 49.9818, 18.9427], ['43-243', 49.9922, 18.9646],
        ]],
        ['Puławy', 'PL-06', 'powiat puławski', [
            ['24-100', 51.4114, 21.9773],
        ]],
        ['Pułtusk', 'PL-14', 'powiat pułtuski', [
            ['06-100', 52.7025, 21.09],
        ]],
        ['Racibórz', 'PL-24', 'powiat raciborski', [
            ['47-400', 50.0887, 18.222], ['47-401', 50.0852, 18.2133], ['47-402', 50.0828, 18.2019],
            ['47-403', 50.0644, 18.1937],
        ]],
        ['Radom', 'PL-14', 'powiat m. Radom', [
            ['26-600', 51.3993, 21.1526], ['26-603', 51.3938, 21.1833], ['26-606', 51.3713, 21.1548],
            ['26-611', 51.3816, 21.1645], ['26-614', 51.4098, 21.182], ['26-617', 51.443, 21.1508],
        ]],
        ['Radomsko', 'PL-10', 'powiat radomszczański', [
            ['97-500', 51.0735, 19.4378],
        ]],
        ['Ruda Śląska', 'PL-24', 'powiat m. Ruda Śląska', [
            ['41-700', 50.2924, 18.8577], ['41-704', 50.3216, 18.8761], ['41-706', 50.2439, 18.8468],
            ['41-708', 50.3015, 18.8769], ['41-710', 50.2668, 18.8647], ['41-712', 50.319, 18.8838],
        ]],
        ['Rumia', 'PL-22', 'powiat wejherowski', [
            ['84-230', 54.5744, 18.3937],
        ]],
        ['Rybnik', 'PL-24', 'powiat m. Rybnik', [
            ['44-113', 50.1357, 18.6036], ['44-203', 50.0929, 18.5827], ['44-210', 50.0914, 18.5128],
            ['44-215', 50.0901, 18.5132], ['44-251', 50.0657, 18.6085], ['44-274', 50.0547, 18.516],
        ]],
        ['Rynarzewo', 'PL-04', 'powiat nakielski', [
            ['89-200', 53.061, 17.8213],
        ]],
        ['Rzeszów', 'PL-18', 'powiat m. Rzeszów', [
            ['35-001', 50.0405, 22.0051], ['35-055', 50.0339, 22.0067], ['35-112', 50.0344, 21.9828],
            ['35-224', 50.0475, 21.9851], ['35-317', 49.9732, 21.9895], ['35-600', 50.0041, 22.0391],
        ]],
        ['Sandomierz', 'PL-26', 'powiat sandomierski', [
            ['27-600', 50.6835, 21.746],
        ]],
        ['Sanok', 'PL-18', 'powiat sanocki', [
            ['38-500', 49.5589, 22.2015],
        ]],
        ['Siedlce', 'PL-14', 'powiat m. Siedlce', [
            ['08-110', 52.1656, 22.2731],
        ]],
        ['Siemianowice Śląskie', 'PL-24', 'powiat m. Siemianowice Śląskie', [
            ['41-100', 50.3034, 19.0305], ['41-101', 50.2939, 19.0178], ['41-103', 50.3288, 19.0232],
            ['41-106', 50.3036, 19.0066], ['41-946', 50.3345, 18.9897],
        ]],
        ['Sieradz', 'PL-10', 'powiat sieradzki', [
            ['98-200', 51.5908, 18.7331],
        ]],
        ['Skalbmierz', 'PL-26', 'powiat kazimierski', [
            ['28-530', 50.3202, 20.3992], ['28-539', 50.3146, 20.3963],
        ]],
        ['Skarżysko-Kamienna', 'PL-26', 'powiat skarżyski', [
            ['26-100', 51.1147, 20.9011], ['26-110', 51.1155, 20.8671], ['26-111', 51.1042, 20.8552],
            ['26-112', 51.1169, 20.8569], ['26-113', 51.1144, 20.8639], ['26-114', 51.1188, 20.8648],
        ]],
        ['Skierniewice', 'PL-10', 'powiat m. Skierniewice', [
            ['96-100', 51.9599, 20.1521], ['96-101', 51.947, 20.1492], ['96-102', 51.9653, 20.1362],
            ['96-103', 51.9529, 20.1524], ['96-104', 51.9788, 20.1501], ['96-105', 51.9559, 20.1241],
        ]],
        ['Słupsk', 'PL-22', 'powiat m. Słupsk', [
            ['76-200', 54.4646, 17.0281], ['76-206', 54.4505, 17.0547], ['76-248', 54.4528, 17.0533],
            ['76-270', 54.4581, 17.0444],
        ]],
        ['Sochaczew', 'PL-14', 'powiat sochaczewski', [
            ['96-500', 52.2331, 20.2459],
        ]],
        ['Sopot', 'PL-22', 'powiat m. Sopot', [
            ['81-702', 54.4382, 18.5648], ['81-728', 54.4379, 18.5722], ['81-753', 54.4438, 18.5672],
            ['81-782', 54.4416, 18.5677], ['81-826', 54.4404, 18.5469], ['81-851', 54.4355, 18.5611],
        ]],
        ['Sosnowiec', 'PL-24', 'powiat m. Sosnowiec', [
            ['41-200', 50.2754, 19.1472], ['41-205', 50.2905, 19.1305], ['41-209', 50.2809, 19.1144],
            ['41-214', 50.2932, 19.1161], ['41-216', 50.2778, 19.2263], ['41-218', 50.2734, 19.1897],
        ]],
        ['Stalowa Wola', 'PL-18', 'powiat stalowowolski', [
            ['37-450', 50.5758, 22.0556], ['37-464', 50.599, 22.045],
        ]],
        ['Starachowice', 'PL-26', 'powiat starachowicki', [
            ['27-200', 51.0411, 21.0713], ['27-500', 51.0397, 21.0564],
        ]],
        ['Stargard', 'PL-32', 'powiat stargardzki', [
            ['73-102', 53.2961, 15.0071], ['73-110', 53.3363, 15.032],
        ]],
        ['Starogard Gdański', 'PL-22', 'powiat starogardzki', [
            ['83-200', 53.9654, 18.519],
        ]],
        ['Studzienice', 'PL-22', 'powiat bytowski', [
            ['77-143', 54.0909, 17.5728],
        ]],
        ['Sulejówek', 'PL-14', 'powiat miński', [
            ['05-070', 52.2354, 21.2666], ['05-071', 52.2442, 21.2868],
        ]],
        ['Suwałki', 'PL-20', 'powiat m. Suwałki', [
            ['16-400', 54.0988, 22.9292], ['16-401', 54.1016, 22.9352], ['16-402', 54.096, 22.9285],
            ['16-431', 54.1143, 22.9448],
        ]],
        ['Swarzędz', 'PL-30', 'powiat poznański', [
            ['62-020', 52.4042, 17.0712],
        ]],
        ['Szczawin', 'PL-10', 'powiat zgierski', [
            ['95-001', 51.9076, 19.4831], ['95-002', 51.9002, 19.4973],
        ]],
        ['Szczecin', 'PL-32', 'powiat m. Szczecin', [
            ['71-001', 53.3988, 14.5025], ['71-137', 53.434, 14.5072], ['71-296', 53.4375, 14.4949],
            ['71-452', 53.4532, 14.5411], ['71-625', 53.439, 14.561], ['71-768', 53.4612, 14.5476],
        ]],
        ['Szczecinek', 'PL-32', 'powiat szczecinecki', [
            ['78-400', 53.7066, 16.6891],
        ]],
        ['Szczytno', 'PL-28', 'powiat szczycieński', [
            ['12-100', 53.5614, 20.9918],
        ]],
        ['Szówsko', 'PL-18', 'powiat jarosławski', [
            ['37-500', 50.0285, 22.7177], ['37-522', 50.0501, 22.7147],
        ]],
        ['Szteklin', 'PL-22', 'powiat starogardzki', [
            ['83-240', 53.8954, 18.4145],
        ]],
        ['Szwecja', 'PL-32', 'powiat wałecki', [
            ['78-611', 53.3469, 16.572],
        ]],
        ['Śrem', 'PL-30', 'powiat śremski', [
            ['63-100', 52.0878, 17.0143],
        ]],
        ['Świdnica', 'PL-02', 'powiat świdnicki', [
            ['58-100', 50.8416, 16.4872], ['58-105', 50.8501, 16.4706], ['58-130', 50.8414, 16.482],
            ['58-150', 50.8406, 16.4712], ['58-160', 50.843, 16.494],
        ]],
        ['Świdnik', 'PL-06', 'powiat świdnicki', [
            ['21-047', 51.2143, 22.694],
        ]],
        ['Świdwin', 'PL-32', 'powiat świdwiński', [
            ['78-300', 53.7735, 15.7756],
        ]],
        ['Świebodzice', 'PL-02', 'powiat świdnicki', [
            ['58-160', 50.8619, 16.3216],
        ]],
        ['Świebodzin', 'PL-08', 'powiat świebodziński', [
            ['66-200', 52.2464, 15.5309],
        ]],
        ['Świecie', 'PL-04', 'powiat świecki', [
            ['86-100', 53.4083, 18.4327], ['86-105', 53.4055, 18.4122],
        ]],
        ['Świętochłowice', 'PL-24', 'powiat m. Świętochłowice', [
            ['41-600', 50.2893, 18.9187], ['41-603', 50.2769, 18.9209], ['41-605', 50.3083, 18.9032],
            ['41-608', 50.3135, 18.9178],
        ]],
        ['Świnoujście', 'PL-32', 'powiat m. Świnoujście', [
            ['72-600', 53.9034, 14.2567], ['72-602', 53.8998, 14.2782], ['72-603', 53.857, 14.3147],
            ['72-605', 53.8862, 14.3426],
        ]],
        ['Tarnobrzeg', 'PL-18', 'powiat m. Tarnobrzeg', [
            ['39-400', 50.5864, 21.7025],
        ]],
        ['Tarnowskie Góry', 'PL-24', 'powiat tarnogórski', [
            ['42-069', 50.4661, 18.8414], ['42-600', 50.446, 18.8507], ['42-605', 50.424, 18.8683],
            ['42-609', 50.4835, 18.8083], ['42-612', 50.4433, 18.8214], ['42-680', 50.4658, 18.8007],
        ]],
        ['Tarnów', 'PL-12', 'powiat m. Tarnów', [
            ['33-100', 50.0158, 20.9769], ['33-101', 49.9991, 20.9301], ['33-102', 50.0376, 20.9566],
            ['33-103', 50.0469, 20.9947], ['33-104', 50.0182, 21.021], ['33-110', 50.0256, 21.0106],
        ]],
        ['Tczew', 'PL-22', 'powiat tczewski', [
            ['83-110', 54.0875, 18.7856],
        ]],
        ['Tomaszów Mazowiecki', 'PL-10', 'powiat tomaszowski', [
            ['97-200', 51.5279, 20.0216], ['97-201', 51.5266, 20.0264], ['97-202', 51.5322, 20.002],
            ['97-203', 51.5406, 20.0056], ['97-204', 51.5224, 20.0119],
        ]],
        ['Toruń', 'PL-04', 'powiat m. Toruń', [
            ['87-100', 53.0212, 18.6235], ['87-105', 53.0172, 18.6318], ['87-110', 53.0108, 18.6555],
            ['87-114', 52.9948, 18.5988], ['87-118', 53.0443, 18.5888], ['87-124', 53.0118, 18.5742],
        ]],
        ['Trzebiatów', 'PL-32', 'powiat gryficki', [
            ['72-320', 54.0598, 15.2707],
        ]],
        ['Turek', 'PL-30', 'powiat turecki', [
            ['62-700', 52.0157, 18.5031],
        ]],
        ['Tychy', 'PL-24', 'powiat m. Tychy', [
            ['43-100', 50.1269, 18.9844], ['43-109', 50.1121, 19.0416], ['43-110', 50.0968, 18.9978],
        ]],
        ['Ustka', 'PL-22', 'powiat słupski', [
            ['76-270', 54.5788, 16.8608],
        ]],
        ['Wałbrzych', 'PL-02', 'powiat m. Wałbrzych', [
            ['58-206', 50.8138, 16.2937], ['58-300', 50.7647, 16.2796], ['58-303', 50.7472, 16.2906],
            ['58-306', 50.8256, 16.3021], ['58-309', 50.8063, 16.2836], ['58-320', 50.8034, 16.2844],
        ]],
        ['Warszawa', 'PL-14', 'powiat m. st. Warszawa', [
            ['02-001', 52.2269, 20.9997], ['02-178', 52.184, 20.9586], ['02-348', 52.2149, 20.972],
            ['02-505', 52.2013, 21.0085], ['02-657', 52.1859, 21.0216], ['02-838', 52.1309, 21.022],
        ]],
        ['Wągrowiec', 'PL-30', 'powiat wągrowiecki', [
            ['62-100', 52.8096, 17.2055],
        ]],
        ['Wejherowo', 'PL-22', 'powiat wejherowski', [
            ['84-200', 54.6051, 18.2455],
        ]],
        ['Wilkowice', 'PL-24', 'powiat bielski', [
            ['43-325', 49.7471, 19.1242], ['43-365', 49.7606, 19.0968],
        ]],
        ['Władysławowo', 'PL-22', 'powiat pucki', [
            ['84-104', 54.7984, 18.3881], ['84-120', 54.792, 18.3989],
        ]],
        ['Włocławek', 'PL-04', 'powiat m. Włocławek', [
            ['87-800', 52.6449, 19.056],
        ]],
        ['Wodzisław Śląski', 'PL-24', 'powiat wodzisławski', [
            ['44-300', 50.0056, 18.452], ['44-304', 49.9854, 18.4929], ['44-307', 50.0133, 18.442],
            ['44-310', 50.0237, 18.4403], ['44-313', 50.0272, 18.4506], ['44-373', 50.0156, 18.3769],
        ]],
        ['Wola Kiedrzyńska', 'PL-24', 'powiat częstochowski', [
            ['42-233', 50.8701, 19.1107],
        ]],
        ['Wrocław', 'PL-02', 'powiat m. Wrocław', [
            ['50-001', 51.1313, 17.1133], ['50-072', 51.1103, 17.0187], ['50-153', 51.1113, 17.0417],
            ['50-266', 51.1152, 17.0403], ['50-368', 51.1152, 17.0676], ['50-503', 51.0888, 17.0484],
        ]],
        ['Września', 'PL-30', 'powiat wrzesiński', [
            ['62-300', 52.3274, 17.5693],
        ]],
        ['Wyszków', 'PL-14', 'powiat wyszkowski', [
            ['07-200', 52.5974, 21.4487],
        ]],
        ['Zabrze', 'PL-24', 'powiat m. Zabrze', [
            ['41-800', 50.3055, 18.7777], ['41-803', 50.3217, 18.8216], ['41-807', 50.3359, 18.7776],
            ['41-810', 50.2721, 18.7938], ['41-813', 50.3786, 18.807], ['41-817', 50.3428, 18.7657],
        ]],
        ['Zakopane', 'PL-12', 'powiat tatrzański', [
            ['34-500', 49.2962, 19.9646],
        ]],
        ['Zamość', 'PL-06', 'powiat m. Zamość', [
            ['22-240', 50.7097, 23.2631], ['22-400', 50.7213, 23.2563], ['22-402', 50.7325, 23.2541],
            ['22-403', 50.7143, 23.2267], ['22-405', 50.7264, 23.249], ['22-406', 50.7195, 23.2736],
        ]],
        ['Zawiercie', 'PL-24', 'powiat zawierciański', [
            ['42-400', 50.496, 19.4381], ['42-401', 50.4918, 19.4416], ['42-403', 50.4862, 19.4232],
            ['42-431', 50.4847, 19.4964],
        ]],
        ['Ząbki', 'PL-14', 'powiat wołomiński', [
            ['05-091', 52.2905, 21.1123],
        ]],
        ['Zborowskie', 'PL-10', 'powiat zduńskowolski', [
            ['98-220', 51.6653, 18.8238],
        ]],
        ['Zduńska Wola', 'PL-10', 'powiat zduńskowolski', [
            ['98-200', 51.5884, 18.9232], ['98-220', 51.5934, 18.9506], ['98-222', 51.5957, 18.953],
            ['98-231', 51.6055, 18.9334],
        ]],
        ['Zgierz', 'PL-10', 'powiat zgierski', [
            ['95-100', 51.8599, 19.4088], ['95-102', 51.863, 19.3923], ['95-105', 51.8554, 19.4243],
            ['95-110', 51.8493, 19.4245],
        ]],
        ['Zgorzelec', 'PL-02', 'powiat zgorzelecki', [
            ['59-900', 51.1449, 15.0139], ['59-901', 51.1398, 15.0123], ['59-902', 51.1407, 15.0215],
        ]],
        ['Zielona Góra', 'PL-08', 'powiat m. Zielona Góra', [
            ['65-001', 51.9427, 15.5117], ['65-075', 51.9398, 15.5036], ['65-180', 51.9435, 15.5236],
            ['65-324', 51.9283, 15.5135], ['65-417', 51.9366, 15.5068], ['65-559', 51.9335, 15.4924],
        ]],
        ['Żary', 'PL-08', 'powiat żarski', [
            ['68-200', 51.6314, 15.143],
        ]],
        ['Żory', 'PL-24', 'powiat m. Żory', [
            ['44-240', 50.0399, 18.6806], ['44-241', 50.0368, 18.6695], ['44-244', 50.0146, 18.6884],
            ['44-245', 50.0273, 18.6416], ['44-247', 50.0493, 18.6524],
        ]],
        ['Żyrardów', 'PL-14', 'powiat żyrardowski', [
            ['96-300', 52.057, 20.4429],
        ]],
        ['Żywiec', 'PL-24', 'powiat żywiecki', [
            ['34-300', 49.6861, 19.2077],
        ]],
    ];

    /**
     * Street names without the street type ("Różana", "Armii Krajowej")
     *
     * @var list<string>
     */
    protected const array STREETS = [
        '1 Maja', '11 Listopada', '3 Maja', 'Agrestowa', 'Akacjowa', 'Al. Jana Pawła II', 'Al. Jerozolimskie',
        'Al. Niepodległości', 'Al. Piłsudskiego', 'Al. Solidarności', 'Al. Wojska Polskiego', 'Al. Wyzwolenia',
        'Andersa', 'Armii Krajowej', 'Asnyka', 'Astrów', 'Azaliowa', 'Baczyńskiego', 'Bałtycka', 'Barlickiego',
        'Batalionów Chłopskich', 'Batorego', 'Bema', 'Beskidzka', 'Białostocka', 'Bielska', 'Bieszczadzka', 'Błękitna',
        'Boczna', 'Bogusławskiego', 'Bohaterów Westerplatte', 'Bolesława Chrobrego', 'Bolesława Krzywoustego',
        'Bolesława Prusa', 'Borowa', 'Botaniczna', 'Bracka', 'Bratków', 'Broniewskiego', 'Brzechwy', 'Brzoskwiniowa',
        'Brzozowa', 'Budowlanych', 'Bukowa', 'Bursztynowa', 'Bydgoska', 'Bytomska', 'Cedrowa', 'Cegielniana', 'Ceglana',
        'Chabrowa', 'Chełmońskiego', 'Chłodna', 'Chłopska', 'Chmielna', 'Chopina Fryderyka', 'Chorzowska', 'Ciasna',
        'Cicha', 'Cieszyńska', 'Cisowa', 'Cmentarna', 'Curie-Skłodowskiej', 'Czarnieckiego', 'Czereśniowa',
        'Częstochowska', 'Czwartaków', 'Daleka', 'Daszyńskiego', 'Dąbrowskiego', 'Dąbrowskiej', 'Dąbrowszczaków',
        'Dąbrówki', 'Dębowa', 'Diamentowa', 'Długa', 'Długosza', 'Dmowskiego', 'Dobra', 'Dolna', 'Dożynkowa',
        'Drzymały', 'Dworcowa', 'Dworska', 'Działkowa', 'Emilii Plater', 'Energetyków', 'Fabryczna', 'Fałata',
        'Fiołkowa', 'Folwarczna', 'Franciszkańska', 'Francuska', 'Fredry', 'Gagarina', 'Gajowa', 'Gałczyńskiego',
        'Gdańska', 'Gdyńska', 'Gliwicka', 'Głogowa', 'Głogowska', 'Głowackiego', 'Główna', 'Gminna', 'Gnieźnieńska',
        'Gojawiczyńskiej', 'Gołębia', 'Gościnna', 'Górna', 'Górnicza', 'Górnośląska', 'Grabowa', 'Graniczna',
        'Granitowa', 'Grochowska', 'Grodzka', 'Grota-Roweckiego', 'Grottgera', 'Grójecka', 'Grunwaldzka', 'Grzybowa',
        'Hallera', 'Handlowa', 'Harcerska', 'Hetmańska', 'Hoża', 'Husarska', 'Hutnicza', 'Inżynierska', 'Iwaszkiewicza',
        'Jagiellońska', 'Jagiełły', 'Jagodowa', 'Jałowcowa', 'Jana III Sobieskiego', 'Jana Pawła II', 'Jaracza',
        'Jarzębinowa', 'Jaskółcza', 'Jasna', 'Jastrzębia', 'Jaśminowa', 'Jaworowa', 'Jesienna', 'Jesionowa', 'Jeżynowa',
        'Jodłowa', 'Kalinowa', 'Kaliska', 'Kamienna', 'Karłowicza', 'Karpacka', 'Kartuska', 'Kasprowicza',
        'Kasprzaka Marcina', 'Kasztanowa', 'Kaszubska', 'Katowicka', 'Kazimierza Wielkiego', 'Kielecka', 'Kilińskiego',
        'Kleeberga', 'Klonowa', 'Kłosowa', 'Kochanowskiego', 'Kolberga', 'Kolejowa', 'Kolorowa', 'Kołłątaja',
        'Kołobrzeska', 'Konarskiego', 'Konopnickiej', 'Konstytucji 3 Maja', 'Konwaliowa', 'Kopalniana', 'Kopernika',
        'Koralowa', 'Korczaka', 'Korfantego', 'Kosmonautów', 'Kossaka', 'Kosynierów', 'Koszalińska', 'Koszykowa',
        'Kościelna', 'Kościuszki', 'Kowalska', 'Krakowska', 'Krańcowa', 'Krasickiego', 'Krasińskiego', 'Kraszewskiego',
        'Kresowa', 'Kręta', 'Królewska', 'Królowej Jadwigi', 'Krótka', 'Krucza', 'Kruczkowskiego', 'Krzywa',
        'Księżycowa', 'Kujawska', 'Kusocińskiego', 'Kwiatkowskiego', 'Kwiatowa', 'Lawendowa', 'Lazurowa', 'Lechicka',
        'Legionów', 'Legnicka', 'Lelewela', 'Leszczynowa', 'Leśmiana', 'Leśna', 'Letnia', 'Ligonia', 'Liliowa',
        'Limanowskiego', 'Lipowa', 'Lisia', 'Litewska', 'Lompy', 'Lotnicza', 'Lotników', 'Lubelska', 'Ludowa',
        'Lwowska', 'Łabędzia', 'Łagiewnicka', 'Łanowa', 'Łączna', 'Łąkowa', 'Łokietka', 'Łomżyńska', 'Łowicka',
        'Łódzka', 'Łukasiewicza', 'Łużycka', 'Maczka', 'Magazynowa', 'Majowa', 'Makowa', 'Makuszyńskiego',
        'Malczewskiego', 'Malinowa', 'Mała', 'Małachowskiego', 'Małopolska', 'Marszałkowska', 'Matejki', 'Mazowiecka',
        'Mazurska', 'Miarki', 'Mickiewicza', 'Miedziana', 'Mieszka I', 'Miła', 'Miodowa', 'Młynarska', 'Młyńska',
        'Modlińska', 'Modra', 'Modrzejewskiej', 'Modrzewiowa', 'Mokra', 'Moniuszki', 'Morcinka', 'Morelowa', 'Morska',
        'Mostowa', 'Myśliwska', 'Nadbrzeżna', 'Nadrzeczna', 'Nałkowskiej', 'Narutowicza', 'Niecała',
        'Niedziałkowskiego', 'Niemcewicza', 'Niepodległości', 'Niska', 'Norwida', 'Nowa', 'Nowowiejska',
        'Nowowiejskiego', 'Nowy Świat', 'Obrońców Westerplatte', 'Odrodzenia', 'Odrzańska', 'Ogrodowa', 'Okopowa',
        'Okólna', 'Okrężna', 'Okrzei', 'Okulickiego', 'Olchowa', 'Olimpijska', 'Olsztyńska', 'Opolska', 'Orkana',
        'Orla', 'Orzechowa', 'Orzeszkowej', 'Os. Jagiellońskie', 'Os. Piastowskie', 'Osiedlowa', 'Oświęcimska',
        'Owocowa', 'Paderewskiego', 'Parkowa', 'Partyzantów', 'Patriotów', 'Pawia', 'Perłowa', 'Piaskowa', 'Piastowska',
        'Piekarska', 'Piękna', 'Piłsudskiego', 'Piotra Skargi', 'Piotrkowska', 'Piwna', 'Pl. Kościuszki',
        'Pl. Wolności', 'Plebiscytowa', 'Płocka', 'Pocztowa', 'Podchorążych', 'Podgórna', 'Podhalańska', 'Podleśna',
        'Podmiejska', 'Podwale', 'Pogodna', 'Pokoju', 'Polna', 'Południowa', 'Pomorska', 'Poniatowskiego',
        'Popiełuszki', 'Poprzeczna', 'Portowa', 'Porzeczkowa', 'Powstańców', 'Powstańców Śląskich',
        'Powstańców Wielkopolskich', 'Poziomkowa', 'Poznańska', 'Północna', 'Promienna', 'Prosta', 'Przechodnia',
        'Przemysłowa', 'Przybyszewskiego', 'Przyjaźni', 'Pszenna', 'Ptasia', 'Pułaskiego', 'Puławska', 'Puszkina',
        'Racławicka', 'Radomska', 'Radosna', 'Rataja', 'Reja', 'Rejtana', 'Reymonta', 'Robotnicza', 'Rodzinna', 'Rolna',
        'Rolnicza', 'Równa', 'Różana', 'Rubinowa', 'Rumiankowa', 'Rybacka', 'Rybna', 'Rybnicka', 'Rycerska', 'Rynek',
        'Rzeczna', 'Rzemieślnicza', 'Sadowa', 'Sandomierska', 'Saperów', 'Sawickiej', 'Sądowa', 'Sąsiedzka',
        'Senatorska', 'Siemiradzkiego', 'Sienkiewicza', 'Sienna', 'Siewna', 'Sikorskiego', 'Składowa',
        'Skłodowskiej-Curie', 'Skośna', 'Skrajna', 'Słoneczna', 'Słonecznikowa', 'Słowackiego', 'Słowiańska',
        'Słowicza', 'Sobieskiego', 'Sokola', 'Solna', 'Solskiego', 'Sosnowa', 'Sowia', 'Sowińskiego', 'Spacerowa',
        'Spokojna', 'Sportowa', 'Spółdzielcza', 'Srebrna', 'Staffa', 'Stalowa', 'Staromiejska', 'Starowiejska',
        'Staszica', 'Stawowa', 'Stolarska', 'Strażacka', 'Stroma', 'Struga', 'Strumykowa', 'Strzelecka', 'Studzienna',
        'Sucha', 'Sucharskiego', 'Szafirowa', 'Szarych Szeregów', 'Szczecińska', 'Szczęśliwa', 'Szeroka', 'Szewska',
        'Szkolna', 'Szmaragdowa', 'Szpitalna', 'Szymanowskiego', 'Ściegiennego', 'Śląska', 'Średnia', 'Środkowa',
        'Świdnicka', 'Świerkowa', 'Świętojańska', 'Świętokrzyska', 'Targowa', 'Tatrzańska', 'Tęczowa', 'Topolowa',
        'Torowa', 'Toruńska', 'Towarowa', 'Traugutta', 'Truskawkowa', 'Tulipanowa', 'Tulipanów', 'Turkusowa',
        'Turystyczna', 'Tuwima', 'Tylna', 'Tysiąclecia', 'Ułańska', 'Urocza', 'Wałowa', 'Wandy', 'Wańkowicza',
        'Wapienna', 'Warmińska', 'Warszawska', 'Waryńskiego', 'Wąska', 'Wczasowa', 'Wesoła', 'Węglowa', 'Widok',
        'Wiejska', 'Wielkopolska', 'Wieniawskiego', 'Wierzbowa', 'Wilcza', 'Wileńska', 'Willowa', 'Wincentego Pola',
        'Wiosenna', 'Wiśniowa', 'Wita Stwosza', 'Witosa', 'Władysława IV', 'Wodna', 'Wojska Polskiego', 'Wolności',
        'Wolska', 'Wołodyjowskiego', 'Wrocławska', 'Wronia', 'Wróblewskiego', 'Wrzosowa', 'Wschodnia', 'Wspólna',
        'Wybickiego', 'Wysoka', 'Wyspiańskiego', 'Wyszyńskiego', 'Wyzwolenia', 'Zachodnia', 'Zacisze', 'Zajęcza',
        'Zakątek', 'Zakopiańska', 'Zamenhofa', 'Zamkowa', 'Zapolskiej', 'Zbożowa', 'Zdrojowa', 'Zgierska', 'Zielna',
        'Zielona', 'Złota', 'Zwierzyniecka', 'Zwycięstwa', 'Źródlana', 'Żabia', 'Żeglarska', 'Żelazna', 'Żeromskiego',
        'Żniwna', 'Żołnierska', 'Żółkiewskiego', 'Żurawia', 'Żwirki i Wigury', 'Żwirowa', 'Żytnia',
    ];

    /**
     * Real city or town, e.g. "Zgorzelec"
     */
    public function city(): string
    {
        return $this->randomizer->randomElement(static::PLACES)[0];
    }

    /**
     * Street with its type, e.g. "ul. Różana", "al. Armii Krajowej" - streets which have their own type in the data
     * ("Al. Jerozolimskie", "Pl. Wolności") keep it
     */
    public function streetName(): string
    {
        $type = $this->randomizer->weightedElement(static::STREET_TYPE_WEIGHTS);
        $street = $this->randomizer->randomElement(static::STREETS);

        if (preg_match('/^(Al|Pl|Os)\. (.+)$/u', $street, $match) === 1) {
            return mb_strtolower($match[1]) . '. ' . $match[2];
        }

        return $type . ' ' . $street;
    }

    /**
     * Real postcode of a random place, e.g. "59-900"
     */
    public function postcode(): string
    {
        return $this->cityData()['postcode'];
    }

    /**
     * Address line of one real place - street, postcode and city match, e.g. "ul. Różana 66, 59-900 Zgorzelec"
     */
    public function address(): string
    {
        return $this->addressData()['address'];
    }

    /**
     * County (powiat) of a random place, e.g. "powiat zgorzelecki"
     */
    public function county(): string
    {
        return $this->randomizer->randomElement(static::PLACES)[2];
    }

    /**
     * One real place with matching city, voivodeship, county, postcode and coordinates (near the postcode), e.g.
     * ['city' => 'Zgorzelec', 'state' => 'dolnośląskie', 'stateCode' => 'PL-02', 'county' => 'powiat zgorzelecki',
     * 'postcode' => '59-900', 'latitude' => 51.1497, 'longitude' => 15.0096]
     *
     * @param string|null $stateCode only places of this voivodeship, ISO 3166-2 code, e.g. "PL-14"
     * @return array{city: string, state: string, stateCode: string, county: string, postcode: string, latitude: float, longitude: float}
     */
    public function cityData(?string $stateCode = null): array
    {
        $places = static::PLACES;

        if ($stateCode !== null) {
            $places = array_values(array_filter($places, static fn (array $place): bool => strcasecmp($place[1], $stateCode) === 0));

            if ($places === []) {
                throw new InvalidArgumentException(sprintf('Unknown state code "%s"', $stateCode));
            }
        }

        [$city, $code, $county, $postcodes] = $this->randomizer->randomElement($places);
        [$postcode, $latitude, $longitude] = $this->randomizer->randomElement($postcodes);

        return [
            'city' => $city,
            'state' => static::STATES[$code],
            'stateCode' => $code,
            'county' => $county,
            'postcode' => $postcode,
            // a point near the postcode centre (up to ~300 m), so addresses don't share one point
            'latitude' => round($latitude + $this->randomizer->getFloat(-0.003, 0.003), 6),
            'longitude' => round($longitude + $this->randomizer->getFloat(-0.003, 0.003), 6),
        ];
    }
}
