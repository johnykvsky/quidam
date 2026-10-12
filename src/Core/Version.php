<?php

declare(strict_types = 1);

namespace Quidam\Core;

use Psr\Clock\ClockInterface;
use Quidam\Definitions\Extension\VersionExtensionInterface;
use Quidam\Definitions\Randomizer\RandomizerInterface;

class Version implements VersionExtensionInterface
{
    /** @var list<string> */
    protected const array PRE_RELEASE = ['alpha', 'beta', 'rc'];

    /** @var array<string, string> branch prefix => conventional commit type */
    protected const array CHANGE_TYPES = [
        'feature' => 'feat', 'fix' => 'fix', 'hotfix' => 'fix', 'refactor' => 'refactor', 'chore' => 'chore',
        'docs' => 'docs', 'test' => 'test', 'perf' => 'perf',
    ];

    /** @var list<string> */
    protected const array CHANGE_VERBS = [
        'add', 'remove', 'update', 'improve', 'handle', 'support', 'rename', 'simplify', 'validate', 'cache',
    ];

    /** @var list<string> */
    protected const array CHANGE_SUBJECTS = [
        'user export', 'login timeout', 'password reset', 'invoice pdf', 'payment retry', 'search filters',
        'email templates', 'api rate limit', 'session handling', 'file upload', 'order status', 'cart totals',
        'dark mode', 'audit log', 'webhook signature', 'date parsing', 'csv import', 'user avatar',
    ];

    /** @var list<string> */
    protected const array CHANGE_SCOPES = ['api', 'auth', 'billing', 'cart', 'core', 'deps', 'ui', 'db', 'ci', 'search'];

    /** @var list<string> */
    protected const array MAIN_BRANCHES = ['main', 'master', 'develop'];

    public function __construct(
        protected readonly RandomizerInterface $randomizer,
        protected readonly ClockInterface $clock,
    ) {
    }

    /**
     * Semantic version, e.g. "2.14.3", with pre-release "2.14.3-beta.2" and build metadata "2.14.3+a1b2c3d"
     *
     * @see https://semver.org
     */
    public function semver(bool $preRelease = false, bool $build = false): string
    {
        return sprintf(
            '%d.%d.%d%s%s',
            $this->randomizer->getInt(0, 9),
            $this->randomizer->getInt(0, 99),
            $this->randomizer->getInt(0, 99),
            $preRelease ? '-' . $this->preReleaseIdentifier() : '',
            $build ? '+' . $this->buildIdentifier() : '',
        );
    }

    /**
     * 40 hex characters, or 7 with $short
     */
    public function gitCommitHash(bool $short = false): string
    {
        $hash = bin2hex($this->randomizer->getBytes(20));

        return $short ? substr($hash, 0, 7) : $hash;
    }

    /**
     * Branch name, e.g. "feature/add-user-export", "fix/JIRA-482-login-timeout", "main"
     */
    public function gitBranchName(): string
    {
        if ($this->randomizer->getBool(10)) {
            return $this->randomizer->randomElement(static::MAIN_BRANCHES);
        }

        $ticket = $this->randomizer->getBool(30) ? strtoupper($this->randomizer->getBytesFromString('ABCDEFGHIJKLMNOPQRSTUVWXYZ', 3)) . '-' . $this->randomizer->getInt(1, 9999) . '-' : '';
        $description = $this->randomizer->randomElement(static::CHANGE_VERBS) . ' ' . $this->randomizer->randomElement(static::CHANGE_SUBJECTS);

        return $this->randomizer->randomKey(static::CHANGE_TYPES) . '/' . $ticket . str_replace(' ', '-', $description);
    }

    /**
     * Conventional commit message subject, e.g. "feat(api): add user export", "fix: handle login timeout"
     */
    public function gitCommitMessage(): string
    {
        $type = $this->randomizer->randomElement(static::CHANGE_TYPES);
        $scope = $this->randomizer->getBool(60) ? '(' . $this->randomizer->randomElement(static::CHANGE_SCOPES) . ')' : '';

        return $type . $scope . ': ' . $this->randomizer->randomElement(static::CHANGE_VERBS) . ' ' . $this->randomizer->randomElement(static::CHANGE_SUBJECTS);
    }

    /**
     * Version constraint as used by Composer / npm, e.g. "^2.4", "~1.2.3", ">=1.4 <2.0", "^3.0 || ^4.0"
     */
    public function semverConstraint(): string
    {
        $major = $this->randomizer->getInt(0, 9);
        $minor = $this->randomizer->getInt(0, 20);

        return match ($this->randomizer->getInt(0, 5)) {
            0, 1 => sprintf('^%d.%d', $major, $minor),
            2 => sprintf('~%d.%d.%d', $major, $minor, $this->randomizer->getInt(0, 20)),
            3 => sprintf('>=%d.%d <%d.0', $major, $minor, $major + 1),
            4 => sprintf('%d.%d.*', $major, $minor),
            default => sprintf('^%d.0 || ^%d.0', $major, $major + 1),
        };
    }

    /**
     * "beta" or "beta.2"
     */
    protected function preReleaseIdentifier(): string
    {
        $identifier = $this->randomizer->randomElement(static::PRE_RELEASE);

        return $this->randomizer->getBool() ? $identifier . '.' . $this->randomizer->getInt(1, 9) : $identifier;
    }

    /**
     * Short commit hash or build date (clock "now")
     */
    protected function buildIdentifier(): string
    {
        return $this->randomizer->getBool() ? $this->gitCommitHash(short: true) : $this->clock->now()->format('YmdHis');
    }
}
