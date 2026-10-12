# UserAgent

`$generator->userAgent()` - interface `Quidam\Definitions\Extension\UserAgentExtensionInterface`, default implementation `Quidam\Core\UserAgent`

Browser user agents in current formats, search engine / AI / social media bots.

## Methods

| Method | Returns |
|---|---|
| `userAgent(?bool $mobile = null)` | browser picked by popularity |
| `chrome(?bool $mobile = null)` | Chrome on Windows / Mac / Linux, or Android |
| `firefox(?bool $mobile = null)` | Firefox on Windows / Mac / Linux, or Android |
| `safari(?bool $mobile = null)` | Safari on Mac, or iPhone |
| `edge(?bool $mobile = null)` | Edge on Windows / Mac / Linux, or Android |
| `opera()` | Opera desktop |
| `botUserAgent()` | crawler: Googlebot, Bingbot, Applebot, GPTBot, ClaudeBot, ... |

`$mobile`: `true` - mobile only, `false` - desktop only, `null` - any (55% mobile).

## Examples

```php
$userAgent = $generator->userAgent();

$userAgent->userAgent();
// 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Mobile Safari/537.36'

$userAgent->chrome(false);
// 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36'

$userAgent->safari(true);
// 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Mobile/15E148 Safari/604.1'

$userAgent->firefox();
// 'Mozilla/5.0 (Android 15; Mobile; rv:155.0) Gecko/155.0 Firefox/155.0'

$userAgent->botUserAgent();
// 'Mozilla/5.0 (compatible; Baiduspider/2.0; +http://www.baidu.com/search/spider.html)'

// access log entries
$log = sprintf('%s "%s /%s" %d "%s"',
    $generator->internet()->ipv4(),
    $generator->internet()->httpMethod(),
    $generator->internet()->slug(2, false),
    $generator->internet()->httpStatusCode(),
    $userAgent->userAgent(),
);
```

## Browser popularity

| Browser | Share |
|---|---|
| Chrome | 65% |
| Safari | 18% |
| Edge | 6% |
| Firefox | 3% |
| Opera | 3% (desktop only, excluded from `userAgent(mobile: true)`) |

## Versions follow the clock

Hardcoded versions get outdated - here they are estimated from [clock](../quidam.md#clock) "now":

* Chrome, Edge, Firefox - every 4 weeks a new version (from known release: Chrome 138 / Firefox 140 on 2025-06-24); user agents use current or up to 8 older versions
* Opera - its Chromium version minus 15
* Safari - yearly in September (26 in 2025, 27 in 2026...; before: 18 in 2024, 17 in 2023), current or previous one
* Android (Firefox) - yearly (16 in 2025), current or up to 4 older

With frozen clock versions are stable, with system clock they move forward over time. Estimation can be off by a version or two - fine for dummy data. Release anchors, browser weights and bots are `protected const` - extend the class to update them (see [Extending generators](../extending.md#overridable-constants)).

## Formats

* Chromium browsers (Chrome, Edge, Opera) use User-Agent Reduction format: `Chrome/152.0.0.0` (only major version), frozen platform tokens `Windows NT 10.0; Win64; x64`, `Macintosh; Intel Mac OS X 10_15_7`, `Linux; Android 10; K`
* Edge adds `Edg/` (desktop) or `EdgA/` (Android), Opera `OPR/`
* Safari on iOS 26+ reports OS version frozen at `18_6`
