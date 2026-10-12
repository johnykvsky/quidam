# Hash

`$generator->hash()` - interface `Quidam\Definitions\Extension\HashExtensionInterface`, default implementation `Quidam\Core\Hash`

Random values in hash, encoding and token formats - for columns like `checksum`, `token`, `api_key`. They are **not** hashes of any real data.

## Methods

| Method | Returns | Example |
|---|---|---|
| `md5()` | `string` - 32 hex characters | `'8773141d0ff6296c9baa94650571f876'` |
| `sha1()` | `string` - 40 hex characters | `'f75512839fe8a3aae3c531eec3f6ddaee90f434e'` |
| `sha256()` | `string` - 64 hex characters | `'b9448a061a6b0be4ce98c5db84261ca45e7682f587391d8dbb81c37815b318f7'` |
| `sha512()` | `string` - 128 hex characters | |
| `base64(int $byteLength = 32)` | `string` - Base64 of random bytes | `'XNvQ3LKYnPkwX53S72tFEiqmsMW8OrHTy9RYNpBrDAE='` |
| `base64Url(int $byteLength = 32)` | `string` - URL safe Base64, no padding | `'0jhKxWnGEs_beviZoPs4ANYdSDFiuv2jpuh3Ik6adlQ'` |
| `jwt(array $claims = [])` | `string` - JWT-shaped token | `'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiI4NTkxMjUi...'` |
| `apiKey(string $prefix = '', int $length = 32)` | `string` - prefix + letters / digits | `apiKey('key_')` → `'key_PuNhlWxZUOFyxCnVdMz7I3n8TN4jjbUj'` |

## Examples

```php
$hash = $generator->hash();

$apiKey = [
    'name' => $generator->lorem()->word(),
    'token' => $hash->base64Url(),          // 43 characters for 32 bytes
    'checksum' => $hash->sha256(),
];

$shortToken = $hash->base64Url(12);         // 16 characters
```

## Notes

* `byteLength` must be at least 1
* `jwt()` has a real header (`{"alg":"HS256","typ":"JWT"}`) and payload (`sub`, `iat` = clock "now", `exp` = 1 hour later, plus your `$claims`, which can replace the defaults), but a **random signature** - it decodes in jwt.io and libraries, verification with any key fails. For authenticated requests in tests sign a real token
* `apiKey()` prefixes follow your provider's convention, i.e. `sk_test_`, `pk_live_`, `ghp_`
* with seed the values are repeatable, without seed random on every run (fast Xoshiro256** engine). For values that must be unpredictable - real test accounts on shared environments - use [`Quidam::createSecure()`](../quidam.md#secure-randomness); never use dummy data as production secrets
