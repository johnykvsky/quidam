<?php

declare(strict_types = 1);

namespace Quidam\Definitions\Extension;

interface VersionExtensionInterface extends ExtensionInterface
{
    /**
     * Semantic version, e.g. "2.14.3", with pre-release "2.14.3-beta.2" and build metadata "2.14.3+a1b2c3d"
     *
     * @see https://semver.org
     */
    public function semver(bool $preRelease = false, bool $build = false): string;

    /**
     * 40 hex characters, or 7 with $short
     */
    public function gitCommitHash(bool $short = false): string;

    /**
     * Branch name, e.g. "feature/add-user-export", "fix/JIRA-482-login-timeout", "main"
     */
    public function gitBranchName(): string;

    /**
     * Conventional commit message subject, e.g. "feat(api): add user export", "fix: handle login timeout"
     */
    public function gitCommitMessage(): string;

    /**
     * Version constraint as used by Composer / npm, e.g. "^2.4", "~1.2.3", ">=1.4 <2.0", "^3.0 || ^4.0"
     */
    public function semverConstraint(): string;
}
