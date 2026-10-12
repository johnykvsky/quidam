<?php

declare(strict_types = 1);

namespace Quidam\Core;

use Quidam\Definitions\Extension\SystemExtensionInterface;
use Quidam\Definitions\Randomizer\RandomizerInterface;

/**
 * Developer and operations data - cron expressions, log levels, environment variable names
 */
class System implements SystemExtensionInterface
{
    /** @var array<string, int> PSR-3 / RFC 5424 level => weight (how often it appears in a typical log) */
    protected const array LOG_LEVELS = [
        'debug' => 30, 'info' => 35, 'notice' => 10, 'warning' => 12, 'error' => 9, 'critical' => 2, 'alert' => 1,
        'emergency' => 1,
    ];

    /** @var list<string> */
    protected const array ENV_PREFIX = [
        'APP', 'API', 'AWS', 'CACHE', 'DB', 'DATABASE', 'LOG', 'MAIL', 'QUEUE', 'REDIS', 'S3', 'SENTRY', 'SESSION', 'SMTP',
    ];

    /** @var list<string> */
    protected const array ENV_SUFFIX = [
        'DEBUG', 'DRIVER', 'ENABLED', 'ENV', 'HOST', 'KEY', 'LEVEL', 'NAME', 'PASSWORD', 'PORT', 'REGION', 'SECRET',
        'TIMEOUT', 'URL', 'USER',
    ];

    public function __construct(
        protected readonly RandomizerInterface $randomizer,
    ) {
    }

    /**
     * Valid 5-field cron expression (minute hour day-of-month month day-of-week), e.g. "30 9-17 * * 1-5"
     */
    public function cronExpression(): string
    {
        $minute = match ($this->randomizer->getInt(0, 4)) {
            0 => '*',
            1 => '*/' . $this->randomizer->randomElement([5, 10, 15, 30]),
            2 => '0',
            default => (string) $this->randomizer->getInt(0, 59),
        };

        $hour = match ($this->randomizer->getInt(0, 4)) {
            0 => '*',
            1 => '*/' . $this->randomizer->randomElement([2, 3, 4, 6, 12]),
            2 => $this->randomizer->getInt(0, 11) . '-' . $this->randomizer->getInt(12, 23),
            default => (string) $this->randomizer->getInt(0, 23),
        };

        $dayOfMonth = $this->randomizer->getBool(20) ? (string) $this->randomizer->getInt(1, 28) : '*';
        $month = $this->randomizer->getBool(10) ? (string) $this->randomizer->getInt(1, 12) : '*';

        // day of month and day of week combined would mean "either" - keep only one of them restricted
        $dayOfWeek = $dayOfMonth === '*'
            ? $this->randomizer->randomElement(['*', '*', '1-5', '0', '6', '1', '0,6'])
            : '*';

        return implode(' ', [$minute, $hour, $dayOfMonth, $month, $dayOfWeek]);
    }

    /**
     * PSR-3 log level picked by typical frequency, e.g. "info"
     */
    public function logLevel(): string
    {
        return $this->randomizer->weightedElement(static::LOG_LEVELS);
    }

    /**
     * Environment variable name, e.g. "DATABASE_URL"
     */
    public function environmentVariable(): string
    {
        return $this->randomizer->randomElement(static::ENV_PREFIX) . '_' . $this->randomizer->randomElement(static::ENV_SUFFIX);
    }
}
