<?php

declare(strict_types = 1);

namespace Quidam\Definitions\Extension;

interface BloodExtensionInterface extends ExtensionInterface
{
    /**
     * ABO type: "A", "B", "AB" or "O"
     */
    public function bloodType(): string;

    /**
     * Rh factor: "+" or "-"
     */
    public function bloodRh(): string;

    /**
     * Type with Rh factor, e.g. "A+", "O-"
     */
    public function bloodGroup(): string;
}
