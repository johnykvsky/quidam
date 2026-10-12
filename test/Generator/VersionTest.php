<?php

declare(strict_types = 1);

namespace Quidam\Test\Generator;

use DateTimeImmutable;
use Quidam\Definitions\Extension\VersionExtensionInterface;
use Quidam\Quidam;
use Quidam\Test\Fixtures\FrozenClock;

final class VersionTest extends GeneratorTestCase
{
    public function testSemver(): void
    {
        $version = $this->dummy->ext(VersionExtensionInterface::class);

        $this->assertAlwaysMatches('/^\d\.\d{1,2}\.\d{1,2}$/', static fn (): string => $version->semver());
        $this->assertAlwaysMatches('/^\d\.\d{1,2}\.\d{1,2}-(alpha|beta|rc)(\.\d)?$/', static fn (): string => $version->semver(preRelease: true));
        $this->assertAlwaysMatches('/^\d\.\d{1,2}\.\d{1,2}\+([0-9a-f]{7}|\d{14})$/', static fn (): string => $version->semver(build: true));
    }

    public function testSemverIsRepeatableWithSeedAndClock(): void
    {
        $clock = new FrozenClock(new DateTimeImmutable('2026-01-01 12:00:00'));
        $first = Quidam::create(5, $clock)->ext(VersionExtensionInterface::class);
        $second = Quidam::create(5, $clock)->ext(VersionExtensionInterface::class);

        for ($i = 0; $i < 20; ++$i) {
            self::assertSame($first->semver(true, true), $second->semver(true, true));
        }
    }

    public function testGitCommitHash(): void
    {
        $version = $this->dummy->ext(VersionExtensionInterface::class);

        $this->assertAlwaysMatches('/^[0-9a-f]{40}$/', static fn (): string => $version->gitCommitHash());
        $this->assertAlwaysMatches('/^[0-9a-f]{7}$/', static fn (): string => $version->gitCommitHash(short: true));
    }

    public function testGitBranchAndCommitMessage(): void
    {
        $version = $this->dummy->ext(VersionExtensionInterface::class);

        $this->assertAlwaysMatches('/^(main|master|develop|(feature|fix|hotfix|refactor|chore|docs|test|perf)\/([A-Z]{3}-\d{1,4}-)?[a-z]+(-[a-z]+)+)$/', $version->gitBranchName(...));
        $this->assertAlwaysMatches('/^(feat|fix|refactor|chore|docs|test|perf)(\([a-z]+\))?: [a-z]+( [a-z]+)+$/', $version->gitCommitMessage(...));
    }

    public function testSemverConstraint(): void
    {
        $this->assertAlwaysMatches(
            '/^(\^\d+\.\d+|~\d+\.\d+\.\d+|>=\d+\.\d+ <\d+\.0|\d+\.\d+\.\*|\^\d+\.0 \|\| \^\d+\.0)$/',
            $this->dummy->ext(VersionExtensionInterface::class)->semverConstraint(...),
        );
    }
}
