<?php

declare(strict_types = 1);

namespace Quidam\Test\Fixtures\Filler;

use DateTime;
use DateTimeImmutable;
use Quidam\Test\Fixtures\Suit;
use Quidam\Test\Fixtures\UnitSuit;

final class FillerUser
{
    public string $nickname;

    public ?string $bio;

    public int $year;

    public string $untouched = 'default';

    /** @param list<string> $tags */
    public function __construct(
        public readonly string $id,
        public string $firstName,
        public string $lastName,
        public string $email,
        public int $age,
        public Suit $suit,
        public UnitSuit $unitSuit,
        public FillerAddress $address,
        public DateTimeImmutable $createdAt,
        public DateTime $updatedAt,
        public string $publishedAt,
        public bool $isAdmin,
        public float $balance,
        public int|string $mixedNumber,
        public array $tags = [],
        public string $role = 'user',
        public ?DateTimeImmutable $deletedAt = null,
        public ?FillerAddress $billingAddress = null,
    ) {
    }
}
