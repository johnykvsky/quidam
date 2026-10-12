<?php

declare(strict_types = 1);

namespace Quidam\Definitions\Extension;

interface InternetExtensionInterface extends ExtensionInterface
{
    /**
     * Email in reserved example.* domain - safe to use, it will never reach real inbox.
     * Built from given names (i.e. to match a person), random ones when null.
     */
    public function email(?string $firstName = null, ?string $lastName = null): string;

    /**
     * Email in real free email provider domain (gmail.com, outlook.com...) - do not send anything to it.
     * Built from given names (i.e. to match a person), random ones when null.
     */
    public function freeEmail(?string $firstName = null, ?string $lastName = null): string;

    /**
     * Email in company domain (from domainName()) - may be a real domain, don't send anything to it.
     * Built from given names, random ones when null.
     */
    public function companyEmail(?string $firstName = null, ?string $lastName = null): string;

    /**
     * Reserved domain used by email() - "example.com", "example.org" or "example.net"
     */
    public function safeEmailDomain(): string;

    public function freeEmailDomain(): string;

    /**
     * User name built from given names (i.e. to match a person), random ones when null
     */
    public function userName(?string $firstName = null, ?string $lastName = null): string;

    /**
     * Password with at least one lowercase letter, uppercase letter, digit and special character
     */
    public function password(int $minLength = 8, int $maxLength = 20): string;

    public function domainName(): string;

    public function domainWord(): string;

    public function tld(): string;

    /** @param bool $withQuery Appends 1-3 query parameters, e.g. "?page=4&sort=quia" */
    public function url(bool $withQuery = false): string;

    /**
     * "https" (70%) or "http"
     */
    public function protocol(): string;

    /**
     * Server host name, e.g. "web-03.norris-labs.biz"
     */
    public function hostname(): string;

    /**
     * e.g. "#doloremQuia"
     */
    public function hashtag(): string;

    /**
     * Social media handle (letters, digits, "_", at most 15 characters), e.g. "@anna_kowalski"
     * Built from given names, random ones when null.
     */
    public function socialHandle(?string $firstName = null, ?string $lastName = null): string;

    /**
     * URL path of $segments segments, each 1-2 words, e.g. "/quia/dolor-sit"
     */
    public function urlPath(int $segments = 2): string;

    /** @param bool $variableNbWords If true, word count is randomized by +/- 40% */
    public function slug(int $nbWords = 6, bool $variableNbWords = true): string;

    /**
     * Public unicast IPv4 address - no private, reserved, loopback, multicast or broadcast ranges (see localIpv4())
     */
    public function ipv4(): string;

    /**
     * IPv4 network in CIDR notation, prefix /8 - /30, e.g. "84.112.0.0/14"
     */
    public function ipv4Cidr(): string;

    public function localIpv4(): string;

    /**
     * Public (global unicast) IPv6 address in compressed form, e.g. "2a03:4b80:1f2::9c"
     */
    public function ipv6(): string;

    /**
     * IPv6 network in CIDR notation (compressed), prefix /16 - /64, e.g. "2a03:4b80::/32" - based on a public ipv6()
     */
    public function ipv6Cidr(): string;

    /**
     * Unicast, locally administered MAC address - can't collide with real hardware addresses
     */
    public function macAddress(): string;

    public function port(int $min = 1024, int $max = 65535): int;

    public function httpMethod(): string;

    /** @param string|null $category One of: informational, success, redirection, clientError, serverError */
    public function httpStatusCode(?string $category = null): int;
}
