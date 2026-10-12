<?php

declare(strict_types = 1);

namespace Quidam\Definitions\Extension;

interface UserAgentExtensionInterface extends ExtensionInterface
{
    /**
     * Browser user agent, browser picked by popularity
     *
     * @param bool|null $mobile true - mobile only, false - desktop only, null - any
     */
    public function userAgent(?bool $mobile = null): string;

    /** @param bool|null $mobile true - Android, false - desktop, null - any */
    public function chrome(?bool $mobile = null): string;

    /** @param bool|null $mobile true - Android, false - desktop, null - any */
    public function edge(?bool $mobile = null): string;

    /**
     * Desktop only
     */
    public function opera(): string;

    /** @param bool|null $mobile true - Android, false - desktop, null - any */
    public function firefox(?bool $mobile = null): string;

    /** @param bool|null $mobile true - iPhone, false - Mac, null - any */
    public function safari(?bool $mobile = null): string;

    /**
     * Search engine, AI or social media crawler
     */
    public function botUserAgent(): string;
}
