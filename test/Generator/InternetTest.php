<?php

declare(strict_types = 1);

namespace Quidam\Test\Generator;

use Quidam\Definitions\Extension\InternetExtensionInterface;
use Quidam\Exception\InvalidArgumentException;

final class InternetTest extends GeneratorTestCase
{
    public function testEmails(): void
    {
        $internet = $this->dummy->ext(InternetExtensionInterface::class);

        $this->assertAlwaysMatches('/^[a-z][a-z0-9._]+$/', $internet->userName(...));
        self::assertAlwaysValid(FILTER_VALIDATE_EMAIL, $internet->email(...));
        $this->assertAlwaysMatches('/^[a-z0-9._]+@example\.(com|org|net)$/', $internet->email(...));
        $this->assertAlwaysMatches('/^[a-z0-9._]+@(gmail|outlook|yahoo|hotmail)\.com$/', $internet->freeEmail(...));
    }

    public function testDomainsAndUrls(): void
    {
        $internet = $this->dummy->ext(InternetExtensionInterface::class);

        $this->assertAlwaysMatches('/^[a-z]+(-?[a-z]+)?\.(com|biz|info|net|org)$/', $internet->domainName(...));
        self::assertAlwaysValid(FILTER_VALIDATE_DOMAIN, $internet->domainName(...), FILTER_FLAG_HOSTNAME);
        $this->assertAlwaysMatches('/^[a-z]+(-[a-z]+)*$/', static fn (): string => $internet->slug());
        $this->assertAlwaysMatches('/^https?:\/\//', $internet->url(...));
        self::assertAlwaysValid(FILTER_VALIDATE_URL, $internet->url(...));
        self::assertCount(3, explode('-', $internet->slug(3, false)));
    }

    public function testPassword(): void
    {
        $internet = $this->dummy->ext(InternetExtensionInterface::class);

        for ($i = 0; $i < self::REPEAT; ++$i) {
            $length = strlen($internet->password(10, 12));
            self::assertGreaterThanOrEqual(10, $length);
            self::assertLessThanOrEqual(12, $length);

            $password = $internet->password(4, 4);
            self::assertSame(4, strlen($password));
            self::assertMatchesRegularExpression('/[a-z]/', $password);
            self::assertMatchesRegularExpression('/[A-Z]/', $password);
            self::assertMatchesRegularExpression('/\d/', $password);
            self::assertMatchesRegularExpression('/[^a-zA-Z\d]/', $password);
        }
    }

    public function testPasswordTooShort(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->dummy->ext(InternetExtensionInterface::class)->password(3, 10);
    }

    public function testUserNamesAreVaried(): void
    {
        $userNames = [];
        for ($i = 0; $i < 1000; ++$i) {
            $userNames[$this->dummy->ext(InternetExtensionInterface::class)->userName()] = true;
        }

        self::assertGreaterThan(950, count($userNames));
    }

    public function testNetwork(): void
    {
        $internet = $this->dummy->ext(InternetExtensionInterface::class);

        self::assertAlwaysValid(FILTER_VALIDATE_IP, $internet->ipv4(...), FILTER_FLAG_IPV4);
        self::assertAlwaysValid(FILTER_VALIDATE_IP, $internet->ipv6(...), FILTER_FLAG_IPV6 | FILTER_FLAG_GLOBAL_RANGE);
        // from 2000::/4 - no 3fff::/20 documentation range, which PHP's filter doesn't know yet
        $this->assertAlwaysMatches('/^2/', $internet->ipv6(...));
        for ($i = 0; $i < 50; ++$i) {
            $ip = $internet->ipv6();
            self::assertSame(inet_ntop((string) inet_pton($ip)), $ip, 'canonical compressed form');
        }

        $this->assertAlwaysMatches('/^(10|172|192)\./', $internet->localIpv4(...));
        self::assertAlwaysValid(FILTER_VALIDATE_IP, $internet->localIpv4(...), FILTER_FLAG_IPV4);
        $this->assertAlwaysMatches('/^[0-9A-F][26AE](:[0-9A-F]{2}){5}$/', $internet->macAddress(...));
        self::assertAlwaysValid(FILTER_VALIDATE_IP, $internet->ipv4(...), FILTER_FLAG_IPV4 | FILTER_FLAG_GLOBAL_RANGE);

        // 100.64.0.0/10 (shared address space, carrier-grade NAT) is ~0.1% of addresses - too rare for REPEAT
        for ($i = 0; $i < 20000; ++$i) {
            self::assertNotSame(0x64400000, (int) ip2long($internet->ipv4()) & 0xffc00000, 'no shared address space');
        }

        $this->assertAlwaysMatches('/^(80|81)$/', static fn (): int => $internet->port(80, 81));
        $this->assertAlwaysMatches('/^(GET|POST|PUT|PATCH|DELETE|HEAD|OPTIONS)$/', $internet->httpMethod(...));
        $this->assertAlwaysMatches('/^[1-5]\d\d$/', static fn (): int => $internet->httpStatusCode());
        $this->assertAlwaysMatches('/^5\d\d$/', static fn (): int => $internet->httpStatusCode('serverError'));
    }

    public function testInvalidStatusCategory(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->dummy->ext(InternetExtensionInterface::class)->httpStatusCode('unknown');
    }

    public function testInvalidPort(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->dummy->ext(InternetExtensionInterface::class)->port(0);
    }

    /** @param callable(): string $callback */
    private static function assertAlwaysValid(int $filter, callable $callback, int $flags = 0): void
    {
        for ($i = 0; $i < self::REPEAT; ++$i) {
            $value = $callback();
            self::assertNotFalse(filter_var($value, $filter, $flags), $value);
        }
    }

    public function testEmailsFromGivenNames(): void
    {
        $internet = $this->dummy->ext(InternetExtensionInterface::class);

        for ($i = 0; $i < self::REPEAT; ++$i) {
            self::assertMatchesRegularExpression('/(anna|kowalski)/', $internet->userName('Anna', 'Kowalski'));
            self::assertMatchesRegularExpression('/^[a-z._0-9]*(anna|kowalski)[a-z._0-9]*@example\.(com|org|net)$/', $internet->email('Anna', 'Kowalski'));
            self::assertMatchesRegularExpression('/(anna|kowalski).*@(gmail|outlook|yahoo|hotmail)\.com$/', $internet->freeEmail('Anna', 'Kowalski'));
            // first name, or its initial + random last name
            self::assertMatchesRegularExpression('/anna|^a[a-z]+\d{2}$/', $internet->userName('Anna'));
        }
    }

    public function testUserNameInitialOfMultibyteName(): void
    {
        for ($i = 0; $i < self::REPEAT; ++$i) {
            self::assertTrue(mb_check_encoding($this->dummy->ext(InternetExtensionInterface::class)->userName('Łucja', 'Nowak'), 'UTF-8'));
        }
    }

    public function testCompanyEmailAndHandles(): void
    {
        $internet = $this->dummy->ext(InternetExtensionInterface::class);

        $this->assertAlwaysMatches('/^[a-z0-9._]+@[a-z]+(-?[a-z]+)?\.(com|biz|info|net|org)$/', static fn (): string => $internet->companyEmail());
        $this->assertAlwaysMatches('/(anna|kowalski).*@/', static fn (): string => $internet->companyEmail('Anna', 'Kowalski'));
        $this->assertAlwaysMatches('/^@[a-z0-9_]{1,15}$/', static fn (): string => $internet->socialHandle());
        $this->assertAlwaysMatches('/^#[a-z]+([A-Z][a-z]*)*$/', $internet->hashtag(...));
        $this->assertAlwaysMatches('/^https?$/', $internet->protocol(...));
        $this->assertAlwaysMatches('/^[a-z]+-\d{2}\.[a-z]+(-?[a-z]+)?\.(com|biz|info|net|org)$/', $internet->hostname(...));
        self::assertAlwaysValid(FILTER_VALIDATE_DOMAIN, $internet->hostname(...), FILTER_FLAG_HOSTNAME);
    }

    public function testUrlWithQuery(): void
    {
        $internet = $this->dummy->ext(InternetExtensionInterface::class);

        for ($i = 0; $i < self::REPEAT; ++$i) {
            $url = $internet->url(true);
            self::assertNotFalse(filter_var($url, FILTER_VALIDATE_URL), $url);
            parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
            self::assertGreaterThanOrEqual(1, count($query));
            self::assertLessThanOrEqual(3, count($query));
            self::assertStringNotContainsString('?', $internet->url());
        }
    }

    public function testCidr(): void
    {
        $internet = $this->dummy->ext(InternetExtensionInterface::class);

        for ($i = 0; $i < self::REPEAT; ++$i) {
            [$network, $prefix] = explode('/', $internet->ipv4Cidr());
            self::assertNotFalse(filter_var($network, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4));
            self::assertGreaterThanOrEqual(8, (int) $prefix);
            self::assertLessThanOrEqual(30, (int) $prefix);
            self::assertSame(0, (int) ip2long($network) & ((1 << (32 - (int) $prefix)) - 1), 'host bits are zero');

            [$network6, $prefix6] = explode('/', $internet->ipv6Cidr());
            self::assertNotFalse(filter_var($network6, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6));
            self::assertStringStartsWith('2', $network6, 'network of a public address');
            self::assertGreaterThanOrEqual(16, (int) $prefix6);
            self::assertLessThanOrEqual(64, (int) $prefix6);
            $bits = implode('', array_map(static fn (string $byte): string => sprintf('%08b', ord($byte)), str_split((string) inet_pton($network6))));
            self::assertSame(str_repeat('0', 128 - (int) $prefix6), substr($bits, (int) $prefix6), 'host bits are zero');
        }
    }

    public function testUrlPathAndSafeEmailDomain(): void
    {
        $internet = $this->dummy->ext(InternetExtensionInterface::class);

        $this->assertAlwaysMatches('#^(/[a-z]+(-[a-z]+)?){2}$#', static fn (): string => $internet->urlPath());
        $this->assertAlwaysMatches('#^(/[a-z]+(-[a-z]+)?){5}$#', static fn (): string => $internet->urlPath(5));
        $this->assertAlwaysMatches('/^example\.(com|org|net)$/', $internet->safeEmailDomain(...));

        $this->expectException(InvalidArgumentException::class);
        $internet->urlPath(0);
    }
}
