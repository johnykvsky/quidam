# Version

`$generator->version()` - interface `Quidam\Definitions\Extension\VersionExtensionInterface`, default implementation `Quidam\Core\Version`

Semantic versions, version constraints, git commit hashes, branch names and commit messages.

## Methods

| Method | Returns | Example |
|---|---|---|
| `semver(bool $preRelease = false, bool $build = false)` | `string` | `'7.45.93'`, `semver(true, true)` → `'4.94.22-rc+68c51eb'` |
| `gitCommitHash(bool $short = false)` | `string` - 40 hex characters, 7 when short | `'8d83bf7ce9957b3914df586923026b2c99a12b28'`, `'13678dc'` |
| `semverConstraint()` | `string` - Composer / npm constraint | `'^2.0 \|\| ^3.0'`, `'0.20.*'`, `'~1.2.3'`, `'>=1.4 <2.0'` |
| `gitBranchName()` | `string` | `'refactor/simplify-csv-import'`, `'feature/SED-1650-update-session-handling'`, `'main'` |
| `gitCommitMessage()` | `string` - Conventional Commits subject | `'chore(cart): update session handling'`, `'feat: add user export'` |

## Examples

```php
$version = $generator->version();

$release = [
    'version' => $version->semver(),                    // '7.45.93'
    'commit' => $version->gitCommitHash(short: true),   // '13678dc'
];

$version->semver(preRelease: true);    // '2.14.3-beta' or '2.14.3-beta.2'
$version->semver(build: true);         // '2.14.3+a1b2c3d' or '2.14.3+20261008120000'
```

## Notes

* major 0-9, minor and patch 0-99
* pre-release: `alpha`, `beta` or `rc`, optionally with number (`beta.2`)
* build metadata: short commit hash or build date - date comes from the [clock](../quidam.md#clock)
* `gitBranchName()`: type prefix (`feature/`, `fix/`, `hotfix/`, `refactor/`, `chore/`, `docs/`, `test/`, `perf/`), sometimes a ticket (`ABC-123-`), then verb and subject in kebab case; 10% are `main` / `master` / `develop`
* `gitCommitMessage()`: `type(scope): description` with optional scope - types match branch prefixes (`feat`, `fix`...)
* overridable data: `PRE_RELEASE`, `CHANGE_TYPES`, `CHANGE_VERBS`, `CHANGE_SUBJECTS`, `CHANGE_SCOPES`, `MAIN_BRANCHES`
