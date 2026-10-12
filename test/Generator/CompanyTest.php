<?php

declare(strict_types = 1);

namespace Quidam\Test\Generator;

use Quidam\Definitions\Extension\CompanyExtensionInterface;

final class CompanyTest extends GeneratorTestCase
{
    public function testCompany(): void
    {
        $company = $this->dummy->ext(CompanyExtensionInterface::class);

        $suffix = '(Group|Holding|International|Partners|Associates|Enterprises|Co)';
        $this->assertAlwaysMatches(
            "/^([A-Z][A-Za-z]+ {$suffix}|[A-Z][A-Za-z]+ & [A-Z][A-Za-z]+|[A-Z][A-Za-z]+-[A-Z][A-Za-z]+ {$suffix}|[A-Z][A-Za-z]+ [A-Z][a-z]+ {$suffix})$/",
            $company->company(...),
        );
        $this->assertAlwaysMatches('/^[A-Z][A-Za-z ]+$/', $company->jobTitle(...));
        $this->assertAlwaysMatches('/^[A-Z][A-Za-z &]+$/', $company->industry(...));
        $this->assertAlwaysMatches('/^\S+ \S+ .+$/', $company->catchPhrase(...));
    }

    public function testNoRepeatedLastName(): void
    {
        for ($i = 0; $i < 2000; ++$i) {
            self::assertDoesNotMatchRegularExpression('/^(\w+)( & |-)\1\b/', $this->dummy->ext(CompanyExtensionInterface::class)->company());
        }
    }
}
