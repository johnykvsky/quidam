<?php

declare(strict_types = 1);

namespace Quidam\Definitions\Extension;

interface HashExtensionInterface extends ExtensionInterface
{
    /** 32 hex characters */
    public function md5(): string;

    /** 40 hex characters */
    public function sha1(): string;

    /** 64 hex characters */
    public function sha256(): string;

    /** 128 hex characters */
    public function sha512(): string;

    /**
     * Base64 encoded random bytes, e.g. for tokens or secrets
     */
    public function base64(int $byteLength = 32): string;

    /**
     * URL safe Base64 (RFC 4648 §5) without padding
     */
    public function base64Url(int $byteLength = 32): string;

    /**
     * JWT-shaped token: real header and payload (sub, iat, exp = iat + 1 hour, from clock "now"), random signature -
     * decodes fine, but doesn't verify with any key. $claims are added to the payload (or replace defaults).
     *
     * @param array<string, mixed> $claims
     */
    public function jwt(array $claims = []): string;

    /**
     * API key - prefix and $length random letters / digits, e.g. apiKey('key_') → "key_4eC39HqLyjWDarjtT1zdp7dc"
     */
    public function apiKey(string $prefix = '', int $length = 32): string;
}
