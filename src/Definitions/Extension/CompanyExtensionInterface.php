<?php

declare(strict_types = 1);

namespace Quidam\Definitions\Extension;

interface CompanyExtensionInterface extends ExtensionInterface
{
    public function company(): string;

    public function companySuffix(): string;

    public function jobTitle(): string;

    public function industry(): string;

    public function catchPhrase(): string;
}
