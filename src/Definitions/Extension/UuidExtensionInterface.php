<?php

declare(strict_types = 1);

namespace Quidam\Definitions\Extension;

use DateTimeInterface;

interface UuidExtensionInterface extends ExtensionInterface
{
    /** Namespaces for uuid3() / uuid5() (RFC 9562) */
    public const string NAMESPACE_DNS = '6ba7b810-9dad-11d1-80b4-00c04fd430c8';

    public const string NAMESPACE_URL = '6ba7b811-9dad-11d1-80b4-00c04fd430c8';

    public const string NAMESPACE_OID = '6ba7b812-9dad-11d1-80b4-00c04fd430c8';

    public const string NAMESPACE_X500 = '6ba7b814-9dad-11d1-80b4-00c04fd430c8';

    /** Nil UUID (RFC 9562) - all bits zero, i.e. "no value" in APIs */
    public const string NIL = '00000000-0000-0000-0000-000000000000';

    /** Max UUID (RFC 9562) - all bits one */
    public const string MAX = 'ffffffff-ffff-ffff-ffff-ffffffffffff';

    /** URL-safe alphabet of Nano ID */
    public const string NANOID_ALPHABET = '_-0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

    /** Twitter snowflake epoch, 2010-11-04 01:42:54.657 UTC, in milliseconds */
    public const int SNOWFLAKE_EPOCH = 1288834974657;

    public function uuid4(): string;

    /**
     * Name-based UUID (MD5) - always the same for the same namespace and name, i.e. stable ids in fixtures
     *
     * @param string $namespace UUID, i.e. Uuid::NAMESPACE_DNS or own one
     */
    public function uuid3(string $namespace, string $name): string;

    /**
     * Name-based UUID (SHA-1) - always the same for the same namespace and name, i.e. stable ids in fixtures:
     * uuid5(Uuid::NAMESPACE_URL, 'https://example.com/users/42')
     *
     * @param string $namespace UUID, i.e. Uuid::NAMESPACE_DNS or own one
     */
    public function uuid5(string $namespace, string $name): string;

    /**
     * Nano ID - $size characters from URL-safe alphabet (or given one), e.g. "V1StGXR8_Z5jdHi6B-myT"
     */
    public function nanoid(int $size = 21, string $alphabet = self::NANOID_ALPHABET): string;

    /**
     * Snowflake id (64-bit int: 41 bits of milliseconds since $epoch, 10 bits worker, 12 bits sequence) -
     * sortable by time, e.g. 1846382094818476032. Uses clock "now" when no date given.
     *
     * @param int $epoch Milliseconds since unix epoch, Twitter one by default (Discord: 1420070400000)
     */
    public function snowflake(?DateTimeInterface $dateTime = null, int $epoch = self::SNOWFLAKE_EPOCH): int;

    /**
     * Time-ordered UUID (RFC 9562). Without date uses clock "now" and is monotonic - every next uuid7()
     * sorts after previous one, also when generated within the same millisecond (or with frozen clock).
     */
    public function uuid7(?DateTimeInterface $dateTime = null): string;

    /**
     * ULID - 26 characters, sortable by time. Without date uses clock "now" and is monotonic - every next ulid() sorts
     * after previous one, also within the same millisecond (or with frozen clock).
     */
    public function ulid(?DateTimeInterface $dateTime = null): string;
}
