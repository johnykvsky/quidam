<?php

declare(strict_types = 1);

/**
 * Data quality of FakerPHP and Quidam generators - share of values that are wrong, unsafe or unrealistic.
 *
 *   php bench/data-quality.php [samples]
 *
 * FakerPHP path: FAKER_PATH (default ../../fakerphp, with its vendor/ installed). Results: docs/data-quality.md.
 */

error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);

require (getenv('FAKER_PATH') ?: __DIR__ . '/../../fakerphp') . '/vendor/autoload.php';
require __DIR__ . '/../vendor/autoload.php';

use Quidam\Core\Address;
use Quidam\Core\Barcode;
use Quidam\Core\PhoneNumber;
use Quidam\Definitions\Addon\TextInterface;
use Quidam\Quidam;

$samples = (int) ($argv[1] ?? 10000);
$faker = Faker\Factory::create();
$faker->seed(7);
$quidam = Quidam::create(seed: 7);

$constant = static fn (string $class, string $name): mixed => new ReflectionClassConstant($class, $name)->getValue();
$share = static function (callable $generator, callable $isBad) use ($samples): string {
    $bad = 0;

    for ($i = 0; $i < $samples; ++$i) {
        $bad += $isBad($generator()) ? 1 : 0;
    }

    return sprintf('%.1f%%', 100 * $bad / $samples);
};

$countries = array_column($constant(Address::class, 'COUNTRIES'), 0);
$gs1 = $constant(Barcode::class, 'GS1_PREFIXES');
$isbnGroups = ['978' => $constant(Barcode::class, 'ISBN_978_GROUPS'), '979' => $constant(Barcode::class, 'ISBN_979_GROUPS')];
$reportingBodies = $constant(PhoneNumber::class, 'IMEI_REPORTING_BODIES');
$firstOctet = static fn (string $mac): int => (int) hexdec(substr(str_replace([':', '-'], '', $mac), 0, 2));

// [check, Faker generator, Quidam generator, is the value bad]
$checks = [
    ['`email()` outside reserved `example.*` domains', $faker->email(...), $quidam->internet()->email(...),
        static fn (string $email): bool => preg_match('/@example\.(com|org|net)$/', $email) !== 1],
    ['`ipv4()` private, shared or reserved', $faker->ipv4(...), $quidam->internet()->ipv4(...),
        static fn (string $ip): bool => filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_GLOBAL_RANGE) === false],
    ['`ipv6()` not global unicast', $faker->ipv6(...), $quidam->internet()->ipv6(...),
        static fn (string $ip): bool => filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 | FILTER_FLAG_GLOBAL_RANGE) === false],
    ['`macAddress()` multicast - not a device address', $faker->macAddress(...), $quidam->internet()->macAddress(...),
        static fn (string $mac): bool => ($firstOctet($mac) & 1) === 1],
    ['`macAddress()` universally administered - may be a real device', $faker->macAddress(...), $quidam->internet()->macAddress(...),
        static fn (string $mac): bool => ($firstOctet($mac) & 2) === 0],
    ['`swiftBicNumber()` country not in ISO 3166', $faker->swiftBicNumber(...), $quidam->payment()->swiftBicNumber(...),
        // XK (Kosovo) is user-assigned in ISO 3166, but SWIFT and IBAN use it
        static fn (string $bic): bool => !in_array(substr($bic, 4, 2), [...$countries, 'XK'], true)],
    ['`swiftBicNumber()` test BIC (location code `?0`)', $faker->swiftBicNumber(...), $quidam->payment()->swiftBicNumber(...),
        static fn (string $bic): bool => $bic[7] === '0'],
    ['`creditCardNumber()` 13 digits ("Visa Retired")', $faker->creditCardNumber(...), $quidam->payment()->creditCardNumber(...),
        static fn (string $number): bool => strlen($number) === 13],
    ['`ean13()` prefix not assigned by GS1', $faker->ean13(...), $quidam->barcode()->ean13(...),
        static function (string $ean) use ($gs1): bool {
            $prefix = (int) substr($ean, 0, 3);

            foreach ($gs1 as [$from, $to]) {
                if ($prefix >= $from && $prefix <= $to) {
                    return false;
                }
            }

            return true;
        }],
    ['`isbn13()` unknown registration group', $faker->isbn13(...), $quidam->barcode()->isbn13(...),
        static function (string $isbn) use ($isbnGroups): bool {
            foreach ($isbnGroups[substr($isbn, 0, 3)] ?? [] as $group) {
                if (str_starts_with(substr($isbn, 3), $group)) {
                    return false;
                }
            }

            return true;
        }],
    ['`imei()` unknown reporting body', $faker->imei(...), $quidam->phoneNumber()->imei(...),
        static fn (string $imei): bool => !in_array(substr($imei, 0, 2), $reportingBodies, true)],
    ['`randomFloat(1, 0, 0.98)` above max', static fn (): float => $faker->randomFloat(1, 0, 0.98),
        static fn (): float => $quidam->number()->randomFloat(1, 0, 0.98), static fn (float $x): bool => $x > 0.98],
    ['`randomFloat(2, 0.001, 0.009)` outside range', static fn (): float => $faker->randomFloat(2, 0.001, 0.009),
        // no 2-decimal number in range - Quidam throws instead of returning one outside
        static fn (): float => 0.005, static fn (float $x): bool => $x < 0.001 || $x > 0.009],
    ['`password()` without lowercase, uppercase, digit and special character', $faker->password(...), $quidam->internet()->password(...),
        static fn (string $password): bool => preg_match('/[a-z]/', $password) !== 1 || preg_match('/[A-Z]/', $password) !== 1
            || preg_match('/\d/', $password) !== 1 || preg_match('/[^a-zA-Z\d]/', $password) !== 1],
    ['`password()` shorter than 8 characters', $faker->password(...), $quidam->internet()->password(...),
        static fn (string $password): bool => strlen($password) < 8],
    ['`name()` with a title or suffix (`Mrs.`, `DVM`, `III`)', $faker->name(...), $quidam->person()->name(...),
        static fn (string $name): bool => preg_match('/^(Mr|Mrs|Ms|Miss|Dr|Prof)\.|\s(Jr\.|Sr\.|I|II|III|IV|V|MD|DDS|PhD|DVM)$/', $name) === 1],
    ['`realText()` with unpaired brackets or quotes', static fn (): string => $faker->realText(200),
        static fn (): string => $quidam->ext(TextInterface::class)->realText(160, 200),
        static fn (string $text): bool => substr_count($text, '(') !== substr_count($text, ')')
            || substr_count($text, '“') !== substr_count($text, '”') || substr_count($text, '"') % 2 === 1],
    ['`latitude()` beyond ±60° (Earth surface: 13.4%)', $faker->latitude(...), $quidam->address()->latitude(...),
        static fn (float $latitude): bool => abs($latitude) > 60],
];

echo "| | FakerPHP | Quidam |\n|---|---|---|\n";

foreach ($checks as [$label, $fakerGenerator, $quidamGenerator, $isBad]) {
    printf("| %s | %s | %s |\n", $label, $share($fakerGenerator, $isBad), $share($quidamGenerator, $isBad));
}

$chromeVersions = static function (callable $chrome): string {
    $versions = [];

    for ($i = 0; $i < 3000; ++$i) {
        if (preg_match('/Chrome\/(\d+)/', $chrome(), $match) === 1) {
            $versions[(int) $match[1]] = true;
        }
    }

    ksort($versions);

    return min(array_keys($versions)) . '-' . max(array_keys($versions));
};

$bloodGroups = static function (callable $bloodGroup) use ($samples): string {
    $counts = [];

    for ($i = 0; $i < $samples; ++$i) {
        $group = $bloodGroup();
        $counts[$group] = ($counts[$group] ?? 0) + 1;
    }

    arsort($counts);

    return implode(', ', array_map(static fn (string $group, int $count): string => sprintf('%s %.0f%%', $group, 100 * $count / $samples), array_keys($counts), $counts));
};

$oldWindows = 0;

for ($i = 0; $i < $samples; ++$i) {
    $oldWindows += preg_match('/Windows (95|98|CE|NT 4|NT 5)|Win 9x/', $faker->userAgent()) === 1 ? 1 : 0;
}

echo "\n";
echo '* Chrome versions in `chrome()`: FakerPHP ' . $chromeVersions($faker->chrome(...)) . ', Quidam ' . $chromeVersions($quidam->userAgent()->chrome(...)) . "\n";
printf("* FakerPHP `userAgent()` on Windows 95 / 98 / CE / NT 4 / 2000 / XP: %.1f%%\n", 100 * $oldWindows / $samples);
echo '* `bloodGroup()`: FakerPHP ' . $bloodGroups($faker->bloodGroup(...)) . "\n";
echo '* `bloodGroup()`: Quidam ' . $bloodGroups($quidam->blood()->bloodGroup(...)) . "\n";
