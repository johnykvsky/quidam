<?php

declare(strict_types = 1);

namespace Quidam\Core;

use DateTimeImmutable;
use Psr\Clock\ClockInterface;
use Quidam\Definitions\Extension\UserAgentExtensionInterface;
use Quidam\Definitions\Randomizer\RandomizerInterface;
use Quidam\Exception\InvalidArgumentException;

/**
 * Browser user agents in current formats. Versions are estimated from clock "now" (browsers release on fixed cadence),
 * so generated user agents don't get outdated - and stay repeatable with seed and frozen clock.
 */
class UserAgent implements UserAgentExtensionInterface
{
    /**
     * Browser => weight, roughly global market share
     *
     * @var array<string, int>
     */
    protected const array BROWSERS = ['chrome' => 65, 'safari' => 18, 'edge' => 6, 'firefox' => 3, 'opera' => 3];

    /** Percent of user agents coming from mobile devices, when not specified */
    protected const int MOBILE_CHANCE = 55;

    /**
     * Desktop platform tokens of Chromium browsers (frozen by User-Agent Reduction) => weight
     *
     * @var array<string, int>
     */
    protected const array DESKTOP_PLATFORMS = [
        'Windows NT 10.0; Win64; x64' => 70,
        'Macintosh; Intel Mac OS X 10_15_7' => 20,
        'X11; Linux x86_64' => 10,
    ];

    /**
     * Firefox doesn't freeze Mac token and uses dots
     *
     * @var array<string, int>
     */
    protected const array FIREFOX_DESKTOP_PLATFORMS = [
        'Windows NT 10.0; Win64; x64' => 70,
        'Macintosh; Intel Mac OS X 10.15' => 15,
        'X11; Linux x86_64' => 10,
        'X11; Ubuntu; Linux x86_64' => 5,
    ];

    /** Chromium mobile platform token (frozen by User-Agent Reduction) */
    protected const string ANDROID_PLATFORM = 'Linux; Android 10; K';

    /**
     * Known release used to estimate current version: [version, date], next ones every 4 weeks
     *
     * @var array{int, string}
     */
    protected const array CHROME_RELEASE = [138, '2025-06-24'];
    /** @var array{int, string} */
    protected const array FIREFOX_RELEASE = [140, '2025-06-24'];

    /** Opera major version is about 15 below its Chromium version */
    protected const int OPERA_OFFSET = 15;

    /** User agents use current version or one of this many older ones */
    protected const int OLDER_VERSIONS = 8;

    /** @var list<string> */
    protected const array BOTS = [
        'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
        'Mozilla/5.0 (Linux; Android 6.0.1; Nexus 5X Build/MMB29P) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Mobile Safari/537.36 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
        'Mozilla/5.0 (compatible; bingbot/2.0; +http://www.bing.com/bingbot.htm)',
        'DuckDuckBot/1.1; (+http://duckduckgo.com/duckduckbot.html)',
        'Mozilla/5.0 (compatible; Baiduspider/2.0; +http://www.baidu.com/search/spider.html)',
        'Mozilla/5.0 (compatible; YandexBot/3.0; +http://yandex.com/bots)',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_5) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/13.1.1 Safari/605.1.15 (Applebot/0.1; +http://www.apple.com/go/applebot)',
        'Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko; compatible; GPTBot/1.1; +https://openai.com/gptbot)',
        'Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko; compatible; ClaudeBot/1.0; +claudebot@anthropic.com)',
        'Twitterbot/1.0',
        'facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)',
        'LinkedInBot/1.0 (compatible; Mozilla/5.0; Apache-HttpClient +http://www.linkedin.com)',
        'Slackbot-LinkExpanding 1.0 (+https://api.slack.com/robots)',
    ];

    public function __construct(
        protected readonly RandomizerInterface $randomizer,
        protected readonly ClockInterface $clock,
    ) {
    }

    /**
     * Browser user agent, browser picked by popularity
     *
     * @param bool|null $mobile true - mobile only, false - desktop only, null - any
     */
    public function userAgent(?bool $mobile = null): string
    {
        $browsers = static::BROWSERS;
        if ($mobile === true) {
            // Opera desktop only
            unset($browsers['opera']);
        }

        return match ($this->randomizer->weightedElement($browsers)) {
            'chrome' => $this->chrome($mobile),
            'safari' => $this->safari($mobile),
            'edge' => $this->edge($mobile),
            'firefox' => $this->firefox($mobile),
            default => $this->opera(),
        };
    }

    /** @param bool|null $mobile true - Android, false - desktop, null - any */
    public function chrome(?bool $mobile = null): string
    {
        return $this->chromium($this->chromeVersion(), $this->isMobile($mobile));
    }

    /** @param bool|null $mobile true - Android, false - desktop, null - any */
    public function edge(?bool $mobile = null): string
    {
        $version = $this->chromeVersion();
        $isMobile = $this->isMobile($mobile);

        return $this->chromium($version, $isMobile) . sprintf(' %s/%d.0.0.0', $isMobile ? 'EdgA' : 'Edg', $version);
    }

    /**
     * Desktop only
     */
    public function opera(): string
    {
        $version = $this->chromeVersion();

        return $this->chromium($version, false) . sprintf(' OPR/%d.0.0.0', $version - static::OPERA_OFFSET);
    }

    /** @param bool|null $mobile true - Android, false - desktop, null - any */
    public function firefox(?bool $mobile = null): string
    {
        $version = $this->recentVersion(static::FIREFOX_RELEASE);

        if ($this->isMobile($mobile)) {
            return sprintf('Mozilla/5.0 (Android %d; Mobile; rv:%2$d.0) Gecko/%2$d.0 Firefox/%2$d.0', $this->androidVersion(), $version);
        }

        return sprintf(
            'Mozilla/5.0 (%s; rv:%2$d.0) Gecko/20100101 Firefox/%2$d.0',
            $this->randomizer->weightedElement(static::FIREFOX_DESKTOP_PLATFORMS),
            $version,
        );
    }

    /** @param bool|null $mobile true - iPhone, false - Mac, null - any */
    public function safari(?bool $mobile = null): string
    {
        $major = $this->safariMajorVersion($this->randomizer->getInt(0, 1));
        $version = $major . '.' . $this->randomizer->getInt(0, 6);

        if ($this->isMobile($mobile)) {
            // since Safari 26 iOS version in user agent is frozen at 18_6
            $ios = $major >= 26 ? '18_6' : str_replace('.', '_', $version);

            return sprintf(
                'Mozilla/5.0 (iPhone; CPU iPhone OS %s like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/%s Mobile/15E148 Safari/604.1',
                $ios,
                $version,
            );
        }

        return sprintf(
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/%s Safari/605.1.15',
            $version,
        );
    }

    /**
     * Search engine, AI or social media crawler
     */
    public function botUserAgent(): string
    {
        return $this->randomizer->randomElement(static::BOTS);
    }

    protected function chromium(int $version, bool $mobile): string
    {
        if ($mobile) {
            return sprintf(
                'Mozilla/5.0 (%s) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/%d.0.0.0 Mobile Safari/537.36',
                static::ANDROID_PLATFORM,
                $version,
            );
        }

        return sprintf(
            'Mozilla/5.0 (%s) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/%d.0.0.0 Safari/537.36',
            $this->randomizer->weightedElement(static::DESKTOP_PLATFORMS),
            $version,
        );
    }

    protected function isMobile(?bool $mobile): bool
    {
        return $mobile ?? $this->randomizer->getBool(static::MOBILE_CHANCE);
    }

    protected function chromeVersion(): int
    {
        return $this->recentVersion(static::CHROME_RELEASE);
    }

    /**
     * Version released up to OLDER_VERSIONS releases before current one (4 weeks cadence from known release)
     *
     * @param array{int, string} $release
     */
    protected function recentVersion(array $release): int
    {
        [$version, $date] = $release;
        $days = intdiv($this->clock->now()->getTimestamp() - new DateTimeImmutable($date)->getTimestamp(), 86400);
        $current = $version + (int) floor($days / 28);

        if ($current < static::OLDER_VERSIONS + 1) {
            throw new InvalidArgumentException('Clock date is too early for browser versions');
        }

        return $this->randomizer->getInt($current - static::OLDER_VERSIONS, $current);
    }

    /**
     * Safari major version $yearsBack years before current one. Released each September,
     * numbering jumped from 18 (2024) to 26 (2025).
     */
    protected function safariMajorVersion(int $yearsBack): int
    {
        $release = new DateTimeImmutable('2025-09-15');
        $now = $this->clock->now();

        // full years since Safari 26 release, negative before it
        $years = $now >= $release ? $release->diff($now)->y : -($now->diff($release)->y + 1);
        $years -= $yearsBack;

        return $years >= 0 ? 26 + $years : 19 + $years;
    }

    /**
     * Android version: current one (16 released June 2025, yearly) or up to 4 older
     */
    protected function androidVersion(): int
    {
        $current = 16 + (int) floor(($this->clock->now()->getTimestamp() - new DateTimeImmutable('2025-06-10')->getTimestamp()) / (365.25 * 86400));

        return $this->randomizer->getInt(max(1, $current - 4), $current);
    }
}
