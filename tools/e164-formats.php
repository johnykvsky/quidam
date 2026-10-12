<?php

declare(strict_types = 1);

/**
 * Rebuilds PhoneNumber::E164_FORMATS and PhoneNumber::COUNTRY_CALLING_CODES from libphonenumber's metadata - a one-off
 * maintenance tool, not part of the library:
 *
 *   curl -sSfLO https://raw.githubusercontent.com/google/libphonenumber/<tag>/resources/PhoneNumberMetadata.xml
 *   php tools/e164-formats.php PhoneNumberMetadata.xml <tag>
 *
 * E164_FORMATS - one mask per calling code, from the main region of the code: length of its example mobile number
 * (fixed line when there's none), leading zeros of the example kept as literal digits (Benin "01..."), then "%" for
 * a non-zero digit and "#" for any. COUNTRY_CALLING_CODES - calling code of every country in Address::COUNTRIES which
 * has own numbers.
 */

use Quidam\Core\Address;
use Quidam\Core\PhoneNumber;

require dirname(__DIR__) . '/vendor/autoload.php';

[, $file, $tag] = $argv + [1 => null, 2 => 'unknown version'];

if ($file === null || !is_file($file)) {
    fwrite(STDERR, "Usage: php tools/e164-formats.php <PhoneNumberMetadata.xml> [<libphonenumber tag>]\n");
    exit(2);
}

$xml = new DOMDocument();
if (!$xml->load($file, LIBXML_NONET)) {
    fwrite(STDERR, "Cannot parse $file\n");
    exit(1);
}

/**
 * Example number of the first number type which has one
 */
$example = static function (DOMElement $territory): ?string {
    foreach (['mobile', 'fixedLine'] as $type) {
        $node = $territory->getElementsByTagName($type)->item(0);
        $number = $node?->getElementsByTagName('exampleNumber')->item(0)?->textContent;

        if ($number !== null && preg_match('/^\d+$/', trim($number)) === 1) {
            return trim($number);
        }
    }

    return null;
};

$byCode = [];
$regions = [];

foreach ($xml->getElementsByTagName('territory') as $territory) {
    $id = $territory->getAttribute('id');
    $code = $territory->getAttribute('countryCode');

    if ($id === '001') {
        continue; // non-geographic: international freephone, satellite services - not countries
    }

    $regions[$id] = $code;
    $number = $example($territory);

    if ($number !== null && (!isset($byCode[$code]) || $territory->getAttribute('mainCountryForCode') === 'true')) {
        $byCode[$code] = $number;
    }
}

uksort($byCode, static fn (string $a, string $b): int => (int) $a <=> (int) $b);

$formats = [];
foreach ($byCode as $code => $number) {
    // leading zeros and the digit after them stay literal ("0195123456" -> "01########"), otherwise "%#..."
    $literal = preg_match('/^0+[1-9]/', $number, $match) === 1 ? $match[0] : '';
    $mask = $literal !== '' ? $literal . str_repeat('#', strlen($number) - strlen($literal)) : '%' . str_repeat('#', strlen($number) - 1);
    $formats[] = sprintf("'+%s %s'", $code, $mask);
}

$countries = array_column(new ReflectionClassConstant(Address::class, 'COUNTRIES')->getValue(), 0);
$callingCodes = array_intersect_key($regions, array_flip($countries));
ksort($callingCodes);

$source = new ReflectionClass(PhoneNumber::class)->getFileName();
$php = (string) file_get_contents((string) $source);

$replace = static function (string $php, string $constant, string $body): string {
    $result = preg_replace('/(protected const array ' . $constant . ' = \[\n).*?(\n    \];)/s', '${1}' . $body . '${2}', $php, 1, $count);

    if ($count !== 1 || $result === null) {
        fwrite(STDERR, "Constant $constant not found\n");
        exit(1);
    }

    return $result;
};

$php = $replace($php, 'E164_FORMATS', implode("\n", array_map(static fn (string $format): string => '        ' . $format . ',', $formats)));
$php = $replace($php, 'COUNTRY_CALLING_CODES', implode("\n", array_map(
    static fn (string $id, string $code): string => sprintf("        '%s' => '%s',", $id, $code),
    array_keys($callingCodes),
    $callingCodes,
)));
$php = (string) preg_replace('/(@see )https:\/\/github\.com\/google\/libphonenumber\S*/', '${1}https://github.com/google/libphonenumber/blob/' . $tag . '/resources/PhoneNumberMetadata.xml', $php);

file_put_contents((string) $source, $php);

printf("E164_FORMATS: %d calling codes, COUNTRY_CALLING_CODES: %d countries (libphonenumber %s)\n", count($formats), count($callingCodes), $tag);
printf("Countries without own numbers: %s\n", implode(', ', array_diff($countries, array_keys($callingCodes))));
