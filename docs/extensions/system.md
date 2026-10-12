# System

`$generator->system()` - interface `Quidam\Definitions\Extension\SystemExtensionInterface`, default implementation `Quidam\Core\System`

Developer and operations data - cron expressions, log levels and environment variable names. File paths are in [File](file.md), git branches and commit messages in [Version](version.md).

## Methods

| Method | Returns | Example |
|---|---|---|
| `cronExpression()` | `string` - valid 5-field expression | `'29 * * * *'`, `'0 */3 * * *'`, `'30 9-17 * * 1-5'` |
| `logLevel()` | `string` - PSR-3 level, picked by typical frequency | `'info'` |
| `environmentVariable()` | `string` - variable name | `'DATABASE_URL'`, `'S3_TIMEOUT'` |

## Examples

```php
$system = $generator->system();

$job = ['command' => 'app:report', 'schedule' => $system->cronExpression()];

$logEntry = [
    'level' => $system->logLevel(),                     // mostly info / debug, rarely emergency
    'message' => $generator->lorem()->sentence(),
    'createdAt' => $generator->dateTime()->dateTimePast('-1 day'),
];

$env = [$system->environmentVariable() => $generator->hash()->apiKey()];
```

## Notes

* cron fields: minute, hour, day of month, month, day of week - with `*`, steps (`*/15`), ranges (`9-17`) and lists (`0,6`). Only one of day-of-month / day-of-week is restricted (both mean "either" in cron)
* log level weights: `debug` 30, `info` 35, `notice` 10, `warning` 12, `error` 9, `critical` 2, `alert` 1, `emergency` 1 - override `LOG_LEVELS` to change them
* overridable data: `LOG_LEVELS`, `ENV_PREFIX`, `ENV_SUFFIX`
