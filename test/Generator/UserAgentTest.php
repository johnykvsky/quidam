<?php

declare(strict_types = 1);

namespace Quidam\Test\Generator;

use DateTimeImmutable;
use Quidam\Definitions\Extension\UserAgentExtensionInterface;
use Quidam\Quidam;
use Quidam\Test\Fixtures\FrozenClock;

final class UserAgentTest extends GeneratorTestCase
{
    private UserAgentExtensionInterface $userAgent;

    protected function setUp(): void
    {
        parent::setUp();

        // Chrome / Firefox 138 / 140 released 2025-06-24, 4 weeks later: 139 / 141
        $this->userAgent = Quidam::create(clock: new FrozenClock(new DateTimeImmutable('2025-07-22')))->ext(UserAgentExtensionInterface::class);
    }

    public function testChrome(): void
    {
        $this->assertAlwaysMatches(
            '/^Mozilla\/5\.0 \((Windows NT 10\.0; Win64; x64|Macintosh; Intel Mac OS X 10_15_7|X11; Linux x86_64)\) AppleWebKit\/537\.36 \(KHTML, like Gecko\) Chrome\/1(3[1-9])\.0\.0\.0 Safari\/537\.36$/',
            fn (): string => $this->userAgent->chrome(false),
        );
        $this->assertAlwaysMatches(
            '/^Mozilla\/5\.0 \(Linux; Android 10; K\) AppleWebKit\/537\.36 \(KHTML, like Gecko\) Chrome\/13[1-9]\.0\.0\.0 Mobile Safari\/537\.36$/',
            fn (): string => $this->userAgent->chrome(true),
        );
    }

    public function testEdgeAndOpera(): void
    {
        $this->assertAlwaysMatches('/Chrome\/(13[1-9])\.0\.0\.0 Safari\/537\.36 Edg\/\1\.0\.0\.0$/', fn (): string => $this->userAgent->edge(false));
        $this->assertAlwaysMatches('/Chrome\/(13[1-9])\.0\.0\.0 Mobile Safari\/537\.36 EdgA\/\1\.0\.0\.0$/', fn (): string => $this->userAgent->edge(true));
        for ($i = 0; $i < self::REPEAT; ++$i) {
            $opera = $this->userAgent->opera();
            if (preg_match('/^Mozilla\/5\.0 \([^)]+\) .+ Chrome\/(13[1-9])\.0\.0\.0 Safari\/537\.36 OPR\/(\d+)\.0\.0\.0$/', $opera, $match) !== 1) {
                self::fail('Unexpected Opera user agent: ' . $opera);
            }

            self::assertSame((int) $match[1] - 15, (int) $match[2], 'Opera version is 15 below its Chromium');
        }
    }

    public function testFirefox(): void
    {
        $this->assertAlwaysMatches(
            '/^Mozilla\/5\.0 \((Windows NT 10\.0; Win64; x64|Macintosh; Intel Mac OS X 10\.15|X11; Linux x86_64|X11; Ubuntu; Linux x86_64); rv:(13[3-9]|14[01])\.0\) Gecko\/20100101 Firefox\/\2\.0$/',
            fn (): string => $this->userAgent->firefox(false),
        );
        $this->assertAlwaysMatches(
            '/^Mozilla\/5\.0 \(Android 1[2-6]; Mobile; rv:(13[3-9]|14[01])\.0\) Gecko\/\1\.0 Firefox\/\1\.0$/',
            fn (): string => $this->userAgent->firefox(true),
        );
    }

    public function testSafari(): void
    {
        // before Safari 26 release (2025-09-15): versions 17 and 18
        $this->assertAlwaysMatches(
            '/^Mozilla\/5\.0 \(Macintosh; Intel Mac OS X 10_15_7\) AppleWebKit\/605\.1\.15 \(KHTML, like Gecko\) Version\/1[78]\.\d Safari\/605\.1\.15$/',
            fn (): string => $this->userAgent->safari(false),
        );
        $this->assertAlwaysMatches(
            '/^Mozilla\/5\.0 \(iPhone; CPU iPhone OS (1[78])_(\d) like Mac OS X\) AppleWebKit\/605\.1\.15 \(KHTML, like Gecko\) Version\/\1\.\2 Mobile\/15E148 Safari\/604\.1$/',
            fn (): string => $this->userAgent->safari(true),
        );

        // after: 26 / 27 with iOS version frozen at 18_6
        $later = Quidam::create(clock: new FrozenClock(new DateTimeImmutable('2026-10-01')))->ext(UserAgentExtensionInterface::class);
        $this->assertAlwaysMatches('/Version\/2[67]\.\d Safari\/605\.1\.15$/', static fn (): string => $later->safari(false));
        $this->assertAlwaysMatches('/iPhone OS 18_6 like Mac OS X.+Version\/2[67]\.\d Mobile/', static fn (): string => $later->safari(true));
    }

    public function testUserAgentMobileFilter(): void
    {
        for ($i = 0; $i < 200; ++$i) {
            self::assertMatchesRegularExpression('/Android|iPhone/', $this->userAgent->userAgent(true));
            self::assertDoesNotMatchRegularExpression('/Android|iPhone/', $this->userAgent->userAgent(false));
            self::assertStringStartsWith('Mozilla/5.0 (', $this->userAgent->userAgent());
        }
    }

    public function testUserAgentIsWeighted(): void
    {
        $chrome = 0;
        for ($i = 0; $i < 1000; ++$i) {
            $userAgent = $this->userAgent->userAgent();
            if (str_contains($userAgent, 'Chrome/') && !preg_match('/Edg|OPR/', $userAgent)) {
                ++$chrome;
            }
        }

        self::assertGreaterThan(550, $chrome);
        self::assertLessThan(750, $chrome);
    }

    public function testVersionsFollowClock(): void
    {
        $later = Quidam::create(clock: new FrozenClock(new DateTimeImmutable('2026-10-08')))->ext(UserAgentExtensionInterface::class);

        // 2025-06-24 + 16 * 28 days = 2026-09-15 -> Chrome 154 current, 8 older in use
        $this->assertAlwaysMatches('/Chrome\/1(4[6-9]|5[0-4])\.0\.0\.0/', static fn (): string => $later->chrome());
    }

    public function testBots(): void
    {
        $this->assertAlwaysMatches('/bot|Bot|spider|facebookexternalhit/', $this->userAgent->botUserAgent(...));
    }
}
