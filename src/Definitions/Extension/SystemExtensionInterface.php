<?php

declare(strict_types = 1);

namespace Quidam\Definitions\Extension;

interface SystemExtensionInterface extends ExtensionInterface
{
    /**
     * Valid 5-field cron expression (minute hour day-of-month month day-of-week), e.g. "30 9-17 * * 1-5"
     */
    public function cronExpression(): string;

    /**
     * PSR-3 log level picked by typical frequency, e.g. "info"
     */
    public function logLevel(): string;

    /**
     * Environment variable name, e.g. "DATABASE_URL"
     */
    public function environmentVariable(): string;
}
