<?php

declare(strict_types = 1);

namespace Quidam\Test\Generator;

use DateTimeImmutable;
use Quidam\Definitions\Extension\HashExtensionInterface;
use Quidam\Exception\InvalidArgumentException;
use Quidam\Quidam;
use Quidam\Test\Fixtures\FrozenClock;

final class HashTest extends GeneratorTestCase
{
    public function testHashes(): void
    {
        $hash = $this->dummy->ext(HashExtensionInterface::class);

        $this->assertAlwaysMatches('/^[0-9a-f]{32}$/', $hash->md5(...));
        $this->assertAlwaysMatches('/^[0-9a-f]{40}$/', $hash->sha1(...));
        $this->assertAlwaysMatches('/^[0-9a-f]{64}$/', $hash->sha256(...));
        $this->assertAlwaysMatches('/^[0-9a-f]{128}$/', $hash->sha512(...));
    }

    public function testBase64(): void
    {
        $hash = $this->dummy->ext(HashExtensionInterface::class);

        for ($i = 0; $i < self::REPEAT; ++$i) {
            self::assertSame(32, strlen((string) base64_decode($hash->base64(), true)));
            self::assertMatchesRegularExpression('/^[A-Za-z0-9_-]{43}$/', $hash->base64Url());
            self::assertSame(5, strlen((string) base64_decode(strtr($hash->base64Url(5), '-_', '+/'), true)));
        }
    }

    public function testInvalidLength(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->dummy->ext(HashExtensionInterface::class)->base64(0);
    }

    public function testJwt(): void
    {
        $now = new DateTimeImmutable('2026-10-09 12:00:00 UTC');
        $jwt = Quidam::create(clock: new FrozenClock($now))->ext(HashExtensionInterface::class)->jwt(['role' => 'admin', 'exp' => 1]);
        $parts = explode('.', $jwt);

        self::assertCount(3, $parts);
        self::assertMatchesRegularExpression('/^[A-Za-z0-9_-]+$/', implode('', $parts));

        $decode = static fn (string $part): mixed => json_decode((string) base64_decode(strtr($part, '-_', '+/'), true), true);
        self::assertSame(['alg' => 'HS256', 'typ' => 'JWT'], $decode($parts[0]));

        $payload = $decode($parts[1]);
        self::assertIsArray($payload);
        self::assertSame($now->getTimestamp(), $payload['iat']);
        self::assertSame(1, $payload['exp'], 'claims replace defaults');
        self::assertSame('admin', $payload['role']);
        self::assertIsString($payload['sub']);
        self::assertSame(32, strlen((string) base64_decode(strtr($parts[2], '-_', '+/'), true)));
    }

    public function testApiKey(): void
    {
        $hash = $this->dummy->ext(HashExtensionInterface::class);

        self::assertMatchesRegularExpression('/^sk_test_[A-Za-z0-9]{32}$/', $hash->apiKey('sk_test_'));
        self::assertMatchesRegularExpression('/^[A-Za-z0-9]{8}$/', $hash->apiKey(length: 8));

        $this->expectException(InvalidArgumentException::class);
        $hash->apiKey('x', 0);
    }
}
