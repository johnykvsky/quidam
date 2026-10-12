<?php

declare(strict_types = 1);

namespace Quidam\Core;

use Quidam\Definitions\Extension\InternetExtensionInterface;
use Quidam\Definitions\Extension\LoremExtensionInterface;
use Quidam\Definitions\Extension\PersonExtensionInterface;
use Quidam\Definitions\Extension\StringsExtensionInterface;
use Quidam\Definitions\Randomizer\RandomizerInterface;
use Quidam\Definitions\Transliterator\TransliteratorInterface;
use Quidam\Exception\InvalidArgumentException;

class Internet implements InternetExtensionInterface
{
    /** @var list<string> */
    protected const array FREE_EMAIL_DOMAIN = ['gmail.com', 'outlook.com', 'yahoo.com', 'hotmail.com'];

    /** @var list<string> */
    protected const array SAFE_EMAIL_DOMAIN = ['example.com', 'example.org', 'example.net'];

    /** @var list<string> */
    protected const array TLD = ['com', 'com', 'com', 'com', 'com', 'com', 'biz', 'info', 'net', 'org'];

    /** @var list<string> */
    protected const array DOMAIN_WORD = [
        'apps', 'cloud', 'consulting', 'data', 'design', 'digital', 'group', 'hub', 'labs', 'media', 'net', 'online',
        'shop', 'soft', 'solutions', 'studio', 'systems', 'tech', 'web', 'works',
    ];

    protected const string PASSWORD_LOWER = 'abcdefghijklmnopqrstuvwxyz';
    protected const string PASSWORD_UPPER = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    protected const string PASSWORD_DIGIT = '0123456789';
    protected const string PASSWORD_SPECIAL = '!@#$%^&*()-_=+[]{};:,.<>?';

    /** @var list<array{string, string}> */
    protected const array LOCAL_IP_BLOCKS = [
        ['10.0.0.0', '10.255.255.255'],
        ['172.16.0.0', '172.31.255.255'],
        ['192.168.0.0', '192.168.255.255'],
    ];

    /** @var list<string> */
    protected const array HOST_PREFIXES = ['api', 'app', 'cache', 'db', 'mail', 'node', 'proxy', 'srv', 'web', 'worker'];

    /** @var list<string> */
    protected const array HTTP_METHODS = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'];

    /** @var array<string, list<int>> */
    protected const array HTTP_STATUS_CODES = [
        'informational' => [100, 101, 102, 103],
        'success' => [200, 201, 202, 204, 206],
        'redirection' => [301, 302, 304, 307, 308],
        'clientError' => [400, 401, 403, 404, 405, 409, 410, 422, 429],
        'serverError' => [500, 501, 502, 503, 504],
    ];

    public function __construct(
        protected readonly RandomizerInterface $randomizer,
        protected readonly PersonExtensionInterface $person,
        protected readonly LoremExtensionInterface $lorem,
        protected readonly StringsExtensionInterface $strings,
        protected readonly TransliteratorInterface $transliterator,
    ) {
    }

    /**
     * Email in reserved example.* domain - safe to use, it will never reach real inbox.
     * Built from given names (i.e. to match a person), random ones when null.
     */
    public function email(?string $firstName = null, ?string $lastName = null): string
    {
        return $this->userName($firstName, $lastName) . '@' . $this->randomizer->randomElement(static::SAFE_EMAIL_DOMAIN);
    }

    /**
     * Email in real free email provider domain (gmail.com, outlook.com...) - do not send anything to it.
     * Built from given names (i.e. to match a person), random ones when null.
     */
    public function freeEmail(?string $firstName = null, ?string $lastName = null): string
    {
        return $this->userName($firstName, $lastName) . '@' . $this->freeEmailDomain();
    }

    /**
     * Email in company domain (from domainName()) - may be a real domain, don't send anything to it.
     * Built from given names, random ones when null.
     */
    public function companyEmail(?string $firstName = null, ?string $lastName = null): string
    {
        return $this->userName($firstName, $lastName) . '@' . $this->domainName();
    }

    /**
     * Reserved domain used by email() - "example.com", "example.org" or "example.net"
     */
    public function safeEmailDomain(): string
    {
        return $this->randomizer->randomElement(static::SAFE_EMAIL_DOMAIN);
    }

    public function freeEmailDomain(): string
    {
        return $this->randomizer->randomElement(static::FREE_EMAIL_DOMAIN);
    }

    /**
     * User name built from given names (i.e. to match a person), random ones when null
     */
    public function userName(?string $firstName = null, ?string $lastName = null): string
    {
        $firstName = $this->namePart($firstName ?? $this->person->firstName());
        $lastName = $this->namePart($lastName ?? $this->person->lastName());

        return match ($this->randomizer->getInt(0, 5)) {
            0 => $firstName . '.' . $lastName,
            1 => $lastName . '.' . $firstName,
            2 => $firstName . '_' . $lastName,
            3 => $firstName . '.' . $lastName . $this->strings->numerify('##'),
            4 => mb_substr($firstName, 0, 1) . $lastName . $this->strings->numerify('##'),
            default => $firstName . $this->strings->numerify('###'),
        };
    }

    /**
     * Password with at least one lowercase letter, uppercase letter, digit and special character
     */
    public function password(int $minLength = 8, int $maxLength = 20): string
    {
        if ($minLength < 4) {
            throw new InvalidArgumentException('password() $minLength must be at least 4 to contain all character types');
        }

        if ($minLength > $maxLength) {
            throw new InvalidArgumentException('password() $minLength cannot be higher than $maxLength');
        }

        $pools = [static::PASSWORD_LOWER, static::PASSWORD_UPPER, static::PASSWORD_DIGIT, static::PASSWORD_SPECIAL];
        $characters = array_map(fn (string $pool): string => $this->randomizer->getBytesFromString($pool, 1), $pools);

        $length = $this->randomizer->getInt($minLength, $maxLength);
        if ($length > count($pools)) {
            $rest = $this->randomizer->getBytesFromString(implode('', $pools), $length - count($pools));
            $characters = [...$characters, ...str_split($rest)];
        }

        return implode('', $this->randomizer->shuffleElements($characters));
    }

    public function domainName(): string
    {
        return $this->domainWord() . '.' . $this->tld();
    }

    public function domainWord(): string
    {
        $lastName = $this->namePart($this->person->lastName());

        return match ($this->randomizer->getInt(0, 2)) {
            0 => $lastName,
            1 => $lastName . '-' . $this->randomizer->randomElement(static::DOMAIN_WORD),
            default => $lastName . $this->randomizer->randomElement(static::DOMAIN_WORD),
        };
    }

    public function tld(): string
    {
        return $this->randomizer->randomElement(static::TLD);
    }

    /** @param bool $withQuery Appends 1-3 query parameters, e.g. "?page=4&sort=quia" */
    public function url(bool $withQuery = false): string
    {
        $url = ($this->randomizer->getBool(70) ? 'https://' : 'http://')
            . ($this->randomizer->getBool() ? 'www.' : '')
            . $this->domainName()
            . '/';

        $url = match ($this->randomizer->getInt(0, 2)) {
            0 => $url,
            1 => $url . $this->slug(),
            default => $url . $this->slug() . '.html',
        };

        if (!$withQuery) {
            return $url;
        }

        $query = [];
        for ($i = $this->randomizer->getInt(1, 3); $i > 0; --$i) {
            $query[$this->lorem->word()] = $this->randomizer->getBool() ? $this->randomizer->getInt(1, 100) : $this->lorem->word();
        }

        return $url . '?' . http_build_query($query);
    }

    /**
     * "https" (70%) or "http"
     */
    public function protocol(): string
    {
        return $this->randomizer->getBool(70) ? 'https' : 'http';
    }

    /**
     * Server host name, e.g. "web-03.norris-labs.biz"
     */
    public function hostname(): string
    {
        return sprintf('%s-%02d.%s', $this->randomizer->randomElement(static::HOST_PREFIXES), $this->randomizer->getInt(1, 99), $this->domainName());
    }

    /**
     * e.g. "#doloremQuia"
     */
    public function hashtag(): string
    {
        $words = array_map(ucfirst(...), $this->lorem->words($this->randomizer->getInt(1, 3)));

        return '#' . lcfirst(implode('', $words));
    }

    /**
     * Social media handle (letters, digits, "_", at most 15 characters), e.g. "@anna_kowalski"
     * Built from given names, random ones when null.
     */
    public function socialHandle(?string $firstName = null, ?string $lastName = null): string
    {
        return '@' . substr(str_replace(['.', '-'], '_', $this->userName($firstName, $lastName)), 0, 15);
    }

    /**
     * URL path of $segments segments, each 1-2 words, e.g. "/quia/dolor-sit"
     */
    public function urlPath(int $segments = 2): string
    {
        if ($segments < 1) {
            throw new InvalidArgumentException('urlPath() $segments must be at least 1');
        }

        $parts = [];
        for ($i = 0; $i < $segments; ++$i) {
            $parts[] = implode('-', $this->lorem->words($this->randomizer->getInt(1, 2)));
        }

        return '/' . implode('/', $parts);
    }

    /** @param bool $variableNbWords If true, word count is randomized by +/- 40% */
    public function slug(int $nbWords = 6, bool $variableNbWords = true): string
    {
        if ($nbWords < 1) {
            throw new InvalidArgumentException('slug() $nbWords must be at least 1');
        }

        if ($variableNbWords) {
            $nbWords = (int) ($nbWords * $this->randomizer->getInt(60, 140) / 100) + 1;
        }

        return implode('-', $this->lorem->words($nbWords));
    }

    /**
     * Public unicast IPv4 address - no private, shared (carrier-grade NAT), reserved, loopback, multicast or broadcast
     * ranges (see localIpv4())
     */
    public function ipv4(): string
    {
        // ~7% of 1.0.0.0 - 223.255.255.255 is private / reserved (above are multicast and reserved), retry is cheap
        for ($attempt = 0; $attempt < 100; ++$attempt) {
            $ip = long2ip($this->randomizer->getInt(0x01000000, 0xdfffffff));

            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_GLOBAL_RANGE) !== false) {
                return $ip;
            }
        }

        // replaced randomizer keeps hitting non-public ranges - 8.0.0.0/8 is entirely public
        return long2ip($this->randomizer->getInt(0x08000000, 0x08ffffff));
    }

    /**
     * IPv4 network in CIDR notation, prefix /8 - /30, e.g. "84.112.0.0/14"
     */
    public function ipv4Cidr(): string
    {
        $prefix = $this->randomizer->getInt(8, 30);
        $mask = (0xffffffff << (32 - $prefix)) & 0xffffffff;

        return long2ip((int) ip2long($this->ipv4()) & $mask) . '/' . $prefix;
    }

    public function localIpv4(): string
    {
        [$from, $to] = $this->randomizer->randomElement(static::LOCAL_IP_BLOCKS);

        return long2ip($this->randomizer->getInt((int) ip2long($from), (int) ip2long($to)));
    }

    /**
     * Public (global unicast) IPv6 address in compressed form, e.g. "2a03:4b80:1f2::9c" - from 2000::/4, where
     * addresses are allocated, without documentation, 6to4 and other special ranges (see localIpv4() for private ones)
     */
    public function ipv6(): string
    {
        return (string) inet_ntop($this->ipv6Bytes());
    }

    /**
     * IPv6 network in CIDR notation (compressed), prefix /16 - /64, e.g. "2a03:4b80::/32" - based on a public ipv6()
     */
    public function ipv6Cidr(): string
    {
        $prefix = $this->randomizer->getInt(16, 64);
        $address = $this->ipv6Bytes();
        $network = '';

        for ($byte = 0; $byte < 16; ++$byte) {
            $bits = max(0, min(8, $prefix - $byte * 8));
            $network .= chr(ord($address[$byte]) & (0xff << (8 - $bits)) & 0xff);
        }

        return inet_ntop($network) . '/' . $prefix;
    }

    /**
     * Unicast, locally administered MAC address - can't collide with real hardware addresses
     */
    public function macAddress(): string
    {
        $bytes = $this->randomizer->getBytes(6);
        // first byte: clear multicast bit (0x01), set locally administered bit (0x02)
        $bytes[0] = chr((ord($bytes[0]) & 0xfe) | 0x02);

        return implode(':', str_split(strtoupper(bin2hex($bytes)), 2));
    }

    public function port(int $min = 1024, int $max = 65535): int
    {
        if ($min < 1 || $max > 65535 || $min > $max) {
            throw new InvalidArgumentException('port() range must be within 1..65535 and min <= max');
        }

        return $this->randomizer->getInt($min, $max);
    }

    public function httpMethod(): string
    {
        return $this->randomizer->randomElement(static::HTTP_METHODS);
    }

    /** @param string|null $category One of: informational, success, redirection, clientError, serverError */
    public function httpStatusCode(?string $category = null): int
    {
        if ($category === null) {
            return $this->randomizer->randomElement(array_merge(...array_values(static::HTTP_STATUS_CODES)));
        }

        if (!isset(static::HTTP_STATUS_CODES[$category])) {
            throw new InvalidArgumentException(sprintf('Unknown HTTP status category "%s"', $category));
        }

        return $this->randomizer->randomElement(static::HTTP_STATUS_CODES[$category]);
    }

    /**
     * 16 bytes of a public IPv6 address - 2000::/4 without special ranges (~1% of it, retry is cheap)
     */
    protected function ipv6Bytes(): string
    {
        for ($attempt = 0; $attempt < 100; ++$attempt) {
            $bytes = $this->randomizer->getBytes(16);
            $bytes[0] = chr(0x20 | (ord($bytes[0]) & 0x0f));

            if (filter_var(inet_ntop($bytes), FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 | FILTER_FLAG_GLOBAL_RANGE) !== false) {
                return $bytes;
            }
        }

        // replaced randomizer keeps hitting special ranges - 2a00::/12 (RIPE NCC) is entirely allocated space
        $bytes = $this->randomizer->getBytes(16);
        $bytes[0] = chr(0x2a);
        $bytes[1] = chr(ord($bytes[1]) & 0x0f);

        return $bytes;
    }

    /**
     * Name as a part of user name or domain - ASCII lowercase letters and digits only ("Żółć" -> "zolc",
     * "O'Brien" -> "obrien"), "user" when nothing is left (i.e. names in scripts without transliteration)
     */
    protected function namePart(string $name): string
    {
        $part = (string) preg_replace('/[^a-z0-9]/', '', strtolower($this->transliterator->transliterate($name)));

        return $part !== '' ? $part : 'user';
    }
}
