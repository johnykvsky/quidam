# Internet

`$generator->internet()` - interface `Quidam\Definitions\Extension\InternetExtensionInterface`, default implementation `Quidam\Core\Internet`

Emails, usernames, passwords, domains, URLs, IP and MAC addresses, HTTP methods and status codes.

## Methods

| Method | Returns | Example |
|---|---|---|
| `email(?string $firstName = null, ?string $lastName = null)` | `string` - in reserved `example.com/org/net` domain | `'derek.owen93@example.net'` |
| `freeEmail(?string $firstName = null, ?string $lastName = null)` | `string` - in real free provider domain | `'timothy.roberts@hotmail.com'` |
| `freeEmailDomain()` | `string` | `'gmail.com'` |
| `safeEmailDomain()` | `string` - reserved domain used by `email()` | `'example.com'` |
| `companyEmail(?string $firstName = null, ?string $lastName = null)` | `string` - in `domainName()` domain | `'dylan_ford@norris-apps.com'` |
| `userName(?string $firstName = null, ?string $lastName = null)` | `string` - lowercase | `'austin.kennedy'`, `'john_smith'`, `'jsmith42'`, `'emily584'` |
| `password(int $minLength = 8, int $maxLength = 20)` | `string` - lower, upper, digit and special character guaranteed | `'iB$juPIIuz0DHS'` |
| `domainName()` | `string` | `'lewis.com'`, `'norris-labs.biz'` |
| `domainWord()` | `string` | `'sullivansolutions'` |
| `tld()` | `string` | `'com'` |
| `url(bool $withQuery = false)` | `string` | `'https://norris-labs.biz/'`, `'https://www.hill.com/rem-omnis-ut.html'`, with query `'http://butlertech.com/in-quod-facere?totam=20'` |
| `protocol()` | `string` - `https` (70%) or `http` | `'https'` |
| `hostname()` | `string` - server host name | `'cache-31.king-online.com'` |
| `slug(int $nbWords = 6, bool $variableNbWords = true)` | `string` | `'quos-at-eum-quisquam-autem'` |
| `urlPath(int $segments = 2)` | `string` - segments of 1-2 words | `'/magnam/autem-odio'` |
| `hashtag()` | `string` - camelCase lorem words | `'#illumPorro'` |
| `socialHandle(?string $firstName = null, ?string $lastName = null)` | `string` - max 15 characters after `@` | `'@lhudson66'`, `socialHandle('Anna', 'Kowalski')` → `'@akowalski59'` |
| `ipv4()` | `string` - public unicast | `'27.244.72.221'` |
| `localIpv4()` | `string` - private (RFC 1918) | `'10.190.164.2'` |
| `ipv6()` | `string` - public unicast, compressed | `'25ae:982e:5911:e092:d818:6d6d:9437:9f48'`, `'2ade:9c9d:279e:d0:267:d586:a7dc:c7e4'` |
| `ipv4Cidr()` | `string` - network, prefix /8 - /30 | `'7.138.68.176/28'` |
| `ipv6Cidr()` | `string` - network (compressed), prefix /16 - /64 | `'2818:6d6d:8000::/33'` |
| `macAddress()` | `string` - unicast, locally administered | `'0E:9F:12:E2:D5:E9'` |
| `port(int $min = 1024, int $max = 65535)` | `int` | `53265` |
| `httpMethod()` | `string` | `'PUT'` |
| `httpStatusCode(?string $category = null)` | `int` | `501`, `httpStatusCode('clientError')` → `403` |

Status code categories: `informational`, `success`, `redirection`, `clientError`, `serverError`.

## Examples

```php
$internet = $generator->internet();

$account = [
    'login' => $internet->userName(),
    'email' => $internet->email(),
    'password' => password_hash($internet->password(12, 16), PASSWORD_DEFAULT),
    'website' => $internet->url(),
    'lastIp' => $internet->ipv4(),
];

// email matching a person - names given, random ones only for missing
$email = $internet->email('Anna', 'Kowalski');      // 'anna.kowalski80@example.org'

$request = [
    'method' => $internet->httpMethod(),
    'path' => '/' . $internet->slug(3, false),        // '/nisi-soluta-doloremque'
    'status' => $internet->httpStatusCode('success'),
];
```

## Notes

* `companyEmail()` uses a generated domain (`domainName()`), which may exist - unlike `email()`, it's not guaranteed unreachable. Use it for display, not for sending
* CIDR networks have host bits zeroed (`ipv4Cidr()` / `ipv6Cidr()` are based on public `ipv4()` / `ipv6()`), so they parse as networks in `ip`/`cidr` libraries

* `email()` is safe - reserved `example.*` domains can't reach a real inbox. `freeEmail()` uses real providers (`gmail.com`, `outlook.com`, `yahoo.com`, `hotmail.com`) - don't send anything to them
* usernames are built from [Person](person.md) names in 6 formats (`first.last`, `last.first`, `first_last`, `first.last42`, `flast42`, `first123`) - 10 000 draws give ~9 870 unique ones
* `userName()`, `email()` and `freeEmail()` accept names - to match a person you already generated. For a whole matching record (name, gender, email, birth date) use [`Profile::person()`](profile.md)
* names are turned into ASCII with [Transliterator](transliterator.md) before they become user names and domains: letters with diacritics, Greek and Cyrillic are transliterated, apostrophes and hyphens removed - `email('Łukasz', 'Wójcik')` gives i.e. `'lukasz.wojcik88@example.net'`, `"O'Brien"` gives `'obrien'`. A name with nothing left (i.e. Chinese characters) becomes `'user'`. Language packs get valid emails without overriding `Internet`
* `password()` minimum length is 4 (one character of each type)
* `ipv4()` excludes private, shared (carrier-grade NAT `100.64.0.0/10`), reserved, loopback, multicast and broadcast ranges
* `ipv6()` is a global unicast address from `2000::/4` (where addresses are allocated), without documentation (`2001:db8::/32`), 6to4 and other special ranges, in the compressed form (RFC 5952) - `'2ade:9c9d:279e:d0:...'`
* `macAddress()` clears multicast bit and sets locally administered bit - it can't collide with real hardware
