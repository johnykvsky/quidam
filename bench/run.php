<?php

/**
 * Benchmark Quidam, DummyGenerator and FakerPHP - every library in its own processes, default PHP settings.
 *
 *   php bench/run.php [--quick]
 *
 * Writes raw results to bench/results/<date>.json and prints Markdown tables for docs/performance.md.
 * Library paths: FAKER_PATH, DUMMYGENERATOR_PATH, QUIDAM_PATH
 * (defaults: ../../fakerphp, ../../dummygenerator, ..).
 */

declare(strict_types = 1);

const LIBRARIES = ['faker' => 'FakerPHP', 'dummygenerator' => 'DummyGenerator', 'quidam' => 'Quidam'];
const MODES = ['default', 'seeded'];

$quick = in_array('--quick', $argv, true);
$processes = ['startup' => $quick ? 3 : 9, 'realtext-cold' => $quick ? 1 : 5, 'micro' => $quick ? 1 : 3, 'unique' => $quick ? 1 : 3];

/** Wall-clock limit of one "unique" process - a single unique call may never return */
const UNIQUE_TIMEOUT_S = 10;

// pin to one CPU when possible - less noise from scheduling and frequency changes
$pin = trim((string) shell_exec('command -v taskset')) !== '' ? 'taskset -c 2 ' : '';

/** @return array<string, mixed> */
function worker(string $pin, string $library, string $suite, string $mode): array
{
    $command = sprintf('%s%s %s %s %s %s', $pin, escapeshellarg(PHP_BINARY), escapeshellarg(__DIR__ . '/worker.php'), $library, $suite, $mode);
    $output = shell_exec($command . ' 2>&1');
    $result = json_decode((string) $output, true);

    if (!is_array($result)) {
        fwrite(STDERR, "Worker failed: $command\n$output\n");
        exit(1);
    }

    return $result;
}

/**
 * "unique" suite under `timeout` - result line when finished, otherwise the last progress line
 *
 * @return array{finished: bool, made: int, ms: float, ms1000: ?float, memory10000Kb: ?float}
 */
function uniqueWorker(string $pin, string $library, string $mode): array
{
    $command = sprintf('timeout %d %s%s %s %s unique %s', UNIQUE_TIMEOUT_S, $pin, escapeshellarg(PHP_BINARY), escapeshellarg(__DIR__ . '/worker.php'), $library, $mode);
    $lines = explode("\n", trim((string) shell_exec($command . ' 2>/dev/null')));
    $last = (string) end($lines);

    if (str_starts_with($last, 'result ')) {
        $result = (array) json_decode(substr($last, 7), true);

        return ['finished' => true, 'made' => 10000, 'ms' => 0.0, 'ms1000' => (float) $result['ms1000'], 'memory10000Kb' => (float) $result['memory10000Kb']];
    }

    // killed by timeout - time at 1000 from its progress line, if it got there
    $ms1000 = null;
    foreach ($lines as $line) {
        if (str_starts_with($line, 'progress 1000 ')) {
            $ms1000 = (float) substr($line, 14);
        }
    }

    [, $made, $ms] = explode(' ', $last) + [null, '0', '0'];

    return ['finished' => false, 'made' => (int) $made, 'ms' => (float) $ms, 'ms1000' => $ms1000, 'memory10000Kb' => null];
}

/** @param list<float> $values */
function median(array $values): float
{
    sort($values);

    return $values[intdiv(count($values), 2)];
}

$results = ['environment' => [
    'date' => date('Y-m-d H:i'),
    'php' => PHP_VERSION,
    'os' => php_uname('s') . ' ' . php_uname('r'),
    'cpu' => trim((string) shell_exec("grep -m1 'model name' /proc/cpuinfo | cut -d: -f2")),
    'opcache.enable_cli' => ini_get('opcache.enable_cli'),
    'pinned' => $pin !== '',
    'processes' => $processes,
]];

foreach (array_keys(LIBRARIES) as $library) {
    foreach (MODES as $mode) {
        fwrite(STDERR, "$library $mode...\n");

        $startup = [];
        for ($i = 0; $i < $processes['startup']; ++$i) {
            $startup[] = worker($pin, $library, 'startup', $mode);
        }

        $results[$library][$mode]['startupMs'] = median(array_column($startup, 'startupMs'));
        $results[$library][$mode]['startupPeakMemoryKb'] = median(array_column($startup, 'peakMemoryKb'));

        $cold = [];
        for ($i = 0; $i < $processes['realtext-cold']; ++$i) {
            $cold[] = worker($pin, $library, 'realtext-cold', $mode);
        }

        $results[$library][$mode]['realTextColdMs'] = $cold[0]['realTextColdMs'] === null ? null : median(array_column($cold, 'realTextColdMs'));
        $results[$library][$mode]['realTextMemoryKb'] = $cold[0]['realTextMemoryKb'] ?? null;

        // micro: median over processes of each process' median
        $micro = [];
        for ($i = 0; $i < $processes['micro']; ++$i) {
            $micro[] = worker($pin, $library, 'micro', $mode);
        }

        foreach (array_keys($micro[0]['scenarios']) as $scenario) {
            $results[$library][$mode]['scenarios'][$scenario] = median(array_map(static fn (array $run): float => $run['scenarios'][$scenario]['median'], $micro));
        }

        // unique: separate processes with a time limit
        $rounds = [];
        for ($i = 0; $i < $processes['unique']; ++$i) {
            $rounds[] = uniqueWorker($pin, $library, $mode);
        }

        $finished = array_filter($rounds, static fn (array $round): bool => $round['ms1000'] !== null);
        $results[$library][$mode]['unique1000'] = count($finished) === count($rounds)
            ? ['finished' => true, 'perEmailNs' => median(array_map(static fn (array $round): float => (float) $round['ms1000'] * 1e6 / 1000, $finished))]
            : ['finished' => false, 'made' => (int) median(array_map(static fn (array $round): float => (float) $round['made'], $rounds)), 'inMs' => UNIQUE_TIMEOUT_S * 1000];
        $complete = array_filter(array_column($rounds, 'memory10000Kb'), static fn (?float $kb): bool => $kb !== null);
        $results[$library][$mode]['unique10000MemoryKb'] = $complete === [] ? null : median($complete);

        $results[$library][$mode]['peakMemoryKb'] = median(array_column($micro, 'peakMemoryKb'));
    }
}

@mkdir(__DIR__ . '/results');
$file = __DIR__ . '/results/' . date('Y-m-d_His') . '.json';
file_put_contents($file, json_encode($results, JSON_PRETTY_PRINT) . "\n");
fwrite(STDERR, "Raw results: $file\n\n");

// ---- Markdown ----

function format(?float $value, string $unit): string
{
    if ($value === null) {
        return 'n/a';
    }

    return match ($unit) {
        'ns' => $value >= 1e6 ? number_format($value / 1e6, 2) . ' ms' : ($value >= 1e3 ? number_format($value / 1e3, 2) . ' µs' : number_format($value) . ' ns'),
        'ms' => number_format($value, 2) . ' ms',
        'kb' => $value >= 1024 ? number_format($value / 1024, 1) . ' MB' : number_format($value) . ' KB',
        default => (string) $value,
    };
}

/**
 * @param array<string, ?float> $values library => value (lower is better)
 * @return list<string> cells with value and "×N" relative to the fastest
 */
function relative(array $values, string $unit): array
{
    $present = array_filter($values, static fn (?float $value): bool => $value !== null && $value > 0);
    $best = $present === [] ? null : min($present);

    return array_values(array_map(static function (?float $value) use ($best, $unit): string {
        if ($value === null) {
            return 'n/a';
        }

        $ratio = $best !== null && $best > 0 ? $value / $best : 1.0;

        return format($value, $unit) . ($ratio < 1.05 ? ' **fastest**' : sprintf(' (×%.1f)', $ratio));
    }, $values));
}

$header = '| | ' . implode(' | ', LIBRARIES) . " |\n|---|" . str_repeat('---|', count(LIBRARIES)) . "\n";

echo "### Startup - first instance in a fresh process (autoload + create + first name())\n\n", $header;
foreach (MODES as $mode) {
    $row = [];
    foreach (array_keys(LIBRARIES) as $library) {
        $row[$library] = $results[$library][$mode]['startupMs'];
    }

    echo "| $mode | ", implode(' | ', relative($row, 'ms')), " |\n";
}

$row = [];
foreach (array_keys(LIBRARIES) as $library) {
    $row[$library] = $results[$library]['default']['startupPeakMemoryKb'];
}
echo '| peak memory | ', implode(' | ', array_map(static fn (?float $v): string => format($v, 'kb'), $row)), " |\n\n";

foreach (MODES as $mode) {
    echo "### Per operation - $mode mode (median time per call, lower is better)\n\n", $header;
    $scenarios = array_keys(array_merge(...array_map(static fn (string $library): array => $results[$library][$mode]['scenarios'], array_keys(LIBRARIES))));

    foreach ($scenarios as $scenario) {
        $row = [];
        foreach (array_keys(LIBRARIES) as $library) {
            $row[$library] = $results[$library][$mode]['scenarios'][$scenario] ?? null;
        }

        echo "| `$scenario` | ", implode(' | ', relative($row, 'ns')), " |\n";
    }

    // unique emails - per email when 1000 finished, otherwise how many in the time limit
    $row = [];
    foreach (array_keys(LIBRARIES) as $library) {
        $row[$library] = $results[$library][$mode]['unique1000']['finished'] ? $results[$library][$mode]['unique1000']['perEmailNs'] : null;
    }

    $cells = relative($row, 'ns');
    foreach (array_values(array_keys(LIBRARIES)) as $index => $library) {
        $unique = $results[$library][$mode]['unique1000'];
        if (!$unique['finished']) {
            $cells[$index] = sprintf('**stalls** - %d of 1000 in %d s', $unique['made'], $unique['inMs'] / 1000);
        }
    }

    echo '| `unique email` (1000 per scope) | ', implode(' | ', $cells), " |\n\n";
}

echo "### Memory and cold start of heavier features (default mode)\n\n", $header;
foreach (['unique10000MemoryKb' => ['10 000 unique emails', 'kb'], 'realTextColdMs' => ['first realText() (builds model)', 'ms'], 'realTextMemoryKb' => ['realText model memory', 'kb'], 'peakMemoryKb' => ['peak memory, whole micro run', 'kb']] as $key => [$label, $unit]) {
    $row = [];
    foreach (array_keys(LIBRARIES) as $library) {
        $row[$library] = $results[$library]['default'][$key];
    }

    echo "| $label | ", implode(' | ', array_map(static fn (?float $v): string => format($v, $unit), $row)), " |\n";
}
