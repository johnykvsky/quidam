<?php

declare(strict_types = 1);

namespace Quidam\Core;

use DateTimeInterface;
use Psr\Clock\ClockInterface;
use Quidam\Definitions\Extension\UuidExtensionInterface;
use Quidam\Definitions\Randomizer\RandomizerInterface;
use Quidam\Exception\InvalidArgumentException;

class Uuid implements UuidExtensionInterface
{
    private const string CROCKFORD_ALPHABET = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';

    /** Max 48-bit millisecond timestamp */
    private const int MAX_TIMESTAMP = 0xffffffffffff;

    private int $lastMsec = -1;

    private int $counter = 0;

    private int $lastUlidMsec = -1;

    /** @var list<int> Base32 digits (0-31) of the last ULID randomness */
    private array $lastUlidRandom = [];

    public function __construct(
        protected readonly RandomizerInterface $randomizer,
        protected readonly ClockInterface $clock,
    ) {
    }

    public function uuid4(): string
    {
        $data = $this->randomizer->getBytes(length: 16);
        // Set version to 0100 (UUID v4)
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        // Set variant to 10xx (RFC 4122)
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);

        return $this->format($data);
    }

    /**
     * Name-based UUID (MD5) - always the same for the same namespace and name, i.e. stable ids in fixtures
     *
     * @param string $namespace UUID, i.e. Uuid::NAMESPACE_DNS or own one
     */
    public function uuid3(string $namespace, string $name): string
    {
        return $this->nameBased($namespace, $name, 3, 'md5');
    }

    /**
     * Name-based UUID (SHA-1) - always the same for the same namespace and name, i.e. stable ids in fixtures:
     * uuid5(Uuid::NAMESPACE_URL, 'https://example.com/users/42')
     *
     * @param string $namespace UUID, i.e. Uuid::NAMESPACE_DNS or own one
     */
    public function uuid5(string $namespace, string $name): string
    {
        return $this->nameBased($namespace, $name, 5, 'sha1');
    }

    /**
     * Nano ID - $size characters from URL-safe alphabet (or given one), e.g. "V1StGXR8_Z5jdHi6B-myT"
     */
    public function nanoid(int $size = 21, string $alphabet = self::NANOID_ALPHABET): string
    {
        if ($size < 1) {
            throw new InvalidArgumentException('nanoid() $size must be at least 1');
        }

        return $this->randomizer->getBytesFromString($alphabet, $size);
    }

    /**
     * Snowflake id (64-bit int: 41 bits of milliseconds since $epoch, 10 bits worker, 12 bits sequence) -
     * sortable by time, e.g. 1846382094818476032. Uses clock "now" when no date given.
     *
     * @param int $epoch Milliseconds since unix epoch, Twitter one by default (Discord: 1420070400000)
     */
    public function snowflake(?DateTimeInterface $dateTime = null, int $epoch = self::SNOWFLAKE_EPOCH): int
    {
        $elapsed = $this->milliseconds($dateTime) - $epoch;

        if ($elapsed < 0 || $elapsed >= 1 << 41) {
            throw new InvalidArgumentException('snowflake() date must be within 69 years after $epoch');
        }

        return ($elapsed << 22) | ($this->randomizer->getInt(0, 1023) << 12) | $this->randomizer->getInt(0, 4095);
    }

    /**
     * Time-ordered UUID (RFC 9562). Without date uses clock "now" and is monotonic - every next uuid7()
     * sorts after previous one, also when generated within the same millisecond (or with frozen clock).
     */
    public function uuid7(?DateTimeInterface $dateTime = null): string
    {
        if ($dateTime === null) {
            [$msec, $counter] = $this->nextMonotonic();
        } else {
            $msec = $this->milliseconds($dateTime);
            $counter = $this->randomizer->getInt(0, 0xfff);
        }

        // 48-bit timestamp as 12 hex chars
        $timeHex = sprintf('%012x', $msec);

        // 62 random bits with variant 10xx (RFC 9562) in top bits
        $random = $this->randomizer->getBytes(8);
        $random[0] = chr((ord($random[0]) & 0x3f) | 0x80);
        $randomHex = bin2hex($random);

        // version 7 + 12-bit counter
        return sprintf(
            '%s-%s-7%03x-%s-%s',
            substr($timeHex, 0, 8),
            substr($timeHex, 8, 4),
            $counter,
            substr($randomHex, 0, 4),
            substr($randomHex, 4, 12),
        );
    }

    /**
     * ULID - 26 characters, sortable by time. Without date uses clock "now" and is monotonic - every next ulid() sorts
     * after previous one, also within the same millisecond (or with frozen clock): randomness of the previous one + 1,
     * as the ULID spec describes.
     */
    public function ulid(?DateTimeInterface $dateTime = null): string
    {
        if ($dateTime === null) {
            [$msec, $random] = $this->nextUlid();
        } else {
            $msec = $this->milliseconds($dateTime);
            $random = $this->ulidRandom();
        }

        // 48-bit timestamp -> 10 Crockford Base32 characters
        $timeChars = '';
        $time = $msec;
        for ($i = 9; $i >= 0; --$i) {
            $timeChars = self::CROCKFORD_ALPHABET[$time & 0x1f] . $timeChars;
            $time = intdiv($time, 32);
        }

        // 80-bit randomness -> 16 Crockford Base32 characters
        return $timeChars . implode('', array_map(static fn (int $digit): string => self::CROCKFORD_ALPHABET[$digit], $random));
    }

    protected function nameBased(string $namespace, string $name, int $version, string $algorithm): string
    {
        if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $namespace) !== 1) {
            throw new InvalidArgumentException(sprintf('Namespace "%s" is not a valid UUID', $namespace));
        }

        $bytes = substr(hash($algorithm, hex2bin(str_replace('-', '', $namespace)) . $name, true), 0, 16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | (($version & 0x0f) << 4));
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);

        return $this->format($bytes);
    }

    /**
     * 16 bytes as "xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx"
     */
    protected function format(string $bytes): string
    {
        $hex = bin2hex($bytes);

        return sprintf(
            '%s-%s-%s-%s-%s',
            substr($hex, 0, 8),
            substr($hex, 8, 4),
            substr($hex, 12, 4),
            substr($hex, 16, 4),
            substr($hex, 20, 12),
        );
    }

    /**
     * Timestamp in milliseconds, of given date or clock "now"
     */
    protected function milliseconds(?DateTimeInterface $dateTime): int
    {
        $dateTime ??= $this->clock->now();
        $msec = (int) $dateTime->format('Uv');

        if ($msec < 0 || $msec > self::MAX_TIMESTAMP) {
            throw new InvalidArgumentException(sprintf(
                'Time-based ids support dates from 1970-01-01 to 10889-08-02, %s given',
                $dateTime->format(DateTimeInterface::ATOM),
            ));
        }

        return $msec;
    }

    /**
     * 80 random bits as 16 Base32 digits
     *
     * @return list<int>
     */
    protected function ulidRandom(): array
    {
        $digits = [];
        for ($i = 0; $i < 16; ++$i) {
            $digits[] = $this->randomizer->getInt(0, 31);
        }

        return $digits;
    }

    /**
     * Returns [milliseconds, randomness] greater than previous call: in a later millisecond fresh randomness, in the
     * same (or earlier) one the previous randomness + 1; when all 80 bits overflow, timestamp moves 1 ms forward.
     *
     * @return array{int, list<int>}
     */
    protected function nextUlid(): array
    {
        $msec = $this->milliseconds(null);

        if ($msec > $this->lastUlidMsec) {
            $this->lastUlidMsec = $msec;

            return [$msec, $this->lastUlidRandom = $this->ulidRandom()];
        }

        for ($i = 15; $i >= 0; --$i) {
            if ($this->lastUlidRandom[$i] < 31) {
                ++$this->lastUlidRandom[$i];

                return [$this->lastUlidMsec, $this->lastUlidRandom];
            }

            $this->lastUlidRandom[$i] = 0;
        }

        return [++$this->lastUlidMsec, $this->lastUlidRandom = $this->ulidRandom()];
    }

    /**
     * Returns [milliseconds, counter] always greater than previous call. In the same (or earlier) millisecond
     * counter is increased, when it overflows 12 bits timestamp is moved 1 ms forward.
     *
     * @return array{int, int}
     */
    protected function nextMonotonic(): array
    {
        $msec = $this->milliseconds(null);

        if ($msec > $this->lastMsec) {
            $this->lastMsec = $msec;
            // start in lower half, leaving room for increments
            $this->counter = $this->randomizer->getInt(0, 0x7ff);
        } elseif (++$this->counter > 0xfff) {
            ++$this->lastMsec;
            $this->counter = $this->randomizer->getInt(0, 0x7ff);
        }

        return [$this->lastMsec, $this->counter];
    }
}
