<?php

declare(strict_types = 1);

namespace Quidam\Core;

use Psr\Clock\ClockInterface;
use Quidam\Definitions\Extension\HashExtensionInterface;
use Quidam\Definitions\Randomizer\RandomizerInterface;
use Quidam\Exception\InvalidArgumentException;

/**
 * Random values in hash / encoding / token formats - not hashes of real data
 */
class Hash implements HashExtensionInterface
{
    private const string BASE62 = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

    public function __construct(
        protected readonly RandomizerInterface $randomizer,
        protected readonly ClockInterface $clock,
    ) {
    }

    /** 32 hex characters */
    public function md5(): string
    {
        return $this->hex(16);
    }

    /** 40 hex characters */
    public function sha1(): string
    {
        return $this->hex(20);
    }

    /** 64 hex characters */
    public function sha256(): string
    {
        return $this->hex(32);
    }

    /** 128 hex characters */
    public function sha512(): string
    {
        return $this->hex(64);
    }

    /**
     * Base64 encoded random bytes, e.g. for tokens or secrets
     */
    public function base64(int $byteLength = 32): string
    {
        return base64_encode($this->bytes($byteLength));
    }

    /**
     * URL safe Base64 (RFC 4648 §5) without padding
     */
    public function base64Url(int $byteLength = 32): string
    {
        return $this->base64UrlEncode($this->bytes($byteLength));
    }

    /**
     * JWT-shaped token: real header and payload (sub, iat, exp = iat + 1 hour, from clock "now"), random signature -
     * decodes fine, but doesn't verify with any key. $claims are added to the payload (or replace defaults).
     *
     * @param array<string, mixed> $claims
     */
    public function jwt(array $claims = []): string
    {
        $issuedAt = $this->clock->now()->getTimestamp();
        $payload = [
            'sub' => (string) $this->randomizer->getInt(1, 999999),
            'iat' => $issuedAt,
            'exp' => $issuedAt + 3600,
            ...$claims,
        ];

        return $this->base64UrlEncode($this->json(['alg' => 'HS256', 'typ' => 'JWT']))
            . '.' . $this->base64UrlEncode($this->json($payload))
            . '.' . $this->base64Url(32);
    }

    /**
     * API key - prefix and $length random letters / digits, e.g. apiKey('key_') → "key_4eC39HqLyjWDarjtT1zdp7dc"
     */
    public function apiKey(string $prefix = '', int $length = 32): string
    {
        if ($length < 1) {
            throw new InvalidArgumentException('apiKey() $length must be at least 1');
        }

        return $prefix . $this->randomizer->getBytesFromString(self::BASE62, $length);
    }

    protected function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /** @param array<string, mixed> $data */
    protected function json(array $data): string
    {
        return json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    protected function hex(int $byteLength): string
    {
        return bin2hex($this->bytes($byteLength));
    }

    protected function bytes(int $byteLength): string
    {
        if ($byteLength < 1) {
            throw new InvalidArgumentException('Byte length must be at least 1');
        }

        return $this->randomizer->getBytes($byteLength);
    }
}
