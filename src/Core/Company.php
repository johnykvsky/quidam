<?php

declare(strict_types = 1);

namespace Quidam\Core;

use Quidam\Definitions\Extension\CompanyExtensionInterface;
use Quidam\Definitions\Extension\PersonExtensionInterface;
use Quidam\Definitions\Randomizer\RandomizerInterface;

class Company implements CompanyExtensionInterface
{
    /** @var list<string> */
    protected const array SUFFIX = ['Group', 'Holding', 'International', 'Partners', 'Associates', 'Enterprises', 'Co'];

    /**
     * Company names - "{lastName}", "{otherLastName}" (a different one), "{word}" (NAME_WORD) and "{suffix}" are
     * replaced. Legal forms (Inc, Ltd, GmbH, sp. z o.o.) belong to language packs' SUFFIX.
     *
     * @var list<string>
     */
    protected const array COMPANY_FORMATS = [
        '{lastName} {suffix}', '{lastName} & {otherLastName}', '{lastName}-{otherLastName} {suffix}', '{lastName} {word} {suffix}',
    ];

    /** @var list<string> */
    protected const array NAME_WORD = [
        'Analytics', 'Capital', 'Consulting', 'Designs', 'Digital', 'Energy', 'Foods', 'Holdings', 'Industries', 'Labs',
        'Logistics', 'Media', 'Networks', 'Partners', 'Services', 'Software', 'Solutions', 'Systems', 'Technologies',
        'Ventures',
    ];

    /** @var list<string> */
    protected const array JOB_TITLE = [
        'Accountant', 'Account Manager', 'Administrative Assistant', 'Architect', 'Backend Developer', 'Business Analyst',
        'Chief Executive Officer', 'Chief Financial Officer', 'Chief Technology Officer', 'Consultant',
        'Content Writer', 'Customer Support Specialist', 'Data Analyst', 'Data Scientist', 'Database Administrator',
        'Designer', 'DevOps Engineer', 'Electrician', 'Financial Analyst', 'Frontend Developer', 'Graphic Designer',
        'HR Manager', 'IT Support Specialist', 'Lawyer', 'Marketing Manager', 'Mechanical Engineer', 'Nurse',
        'Office Manager', 'Operations Manager', 'Product Designer', 'Product Manager', 'Project Manager',
        'Quality Assurance Engineer', 'Recruiter', 'Sales Manager', 'Sales Representative', 'Scrum Master',
        'Software Engineer', 'System Administrator', 'Team Leader', 'Teacher', 'Technical Writer', 'UX Researcher',
    ];

    /** @var list<string> */
    protected const array INDUSTRY = [
        'Technology', 'Healthcare', 'Financial Services', 'Manufacturing',
        'Retail', 'Telecommunications', 'Education', 'Energy',
        'Transportation', 'Media & Entertainment', 'Real Estate', 'Agriculture',
        'Hospitality', 'Construction', 'Consulting',
    ];

    /** @var list<list<string>> */
    protected const array CATCH_PHRASE_WORDS = [
        ['Adaptive', 'Advanced', 'Automated', 'Balanced', 'Business-focused', 'Centralized', 'Compatible', 'Configurable', 'Cross-platform', 'Customer-focused', 'Customizable', 'Decentralized', 'Digitized', 'Distributed', 'Diverse', 'Dynamic', 'Empowered', 'Enhanced', 'Enterprise-wide', 'Ergonomic', 'Exclusive', 'Expanded', 'Extended', 'Focused', 'Front-line', 'Fully-configurable', 'Fundamental', 'Future-proofed', 'Horizontal', 'Innovative', 'Integrated', 'Intuitive', 'Managed', 'Monitored', 'Multi-channel', 'Multi-lateral', 'Multi-layered', 'Multi-tiered', 'Networked', 'Object-based', 'Open-architected', 'Open-source', 'Operative', 'Optimized', 'Organic', 'Organized', 'Proactive', 'Profit-focused', 'Programmable', 'Progressive', 'Quality-focused', 'Reactive', 'Realigned', 'Re-engineered', 'Robust', 'Seamless', 'Secured', 'Self-enabling', 'Sharable', 'Stand-alone', 'Streamlined', 'Switchable', 'Synchronized', 'Synergistic', 'Team-oriented', 'Total', 'Universal', 'Upgradable', 'User-centric', 'User-friendly', 'Versatile', 'Virtual', 'Visionary'],
        ['24hour', '24/7', 'actuating', 'analyzing', 'asynchronous', 'client-driven', 'client-server', 'coherent', 'cohesive', 'composite', 'context-sensitive', 'content-based', 'dedicated', 'demand-driven', 'directional', 'discrete', 'dynamic', 'eco-centric', 'empowering', 'encompassing', 'executive', 'explicit', 'fault-tolerant', 'fresh-thinking', 'full-range', 'global', 'grid-enabled', 'heuristic', 'high-level', 'holistic', 'homogeneous', 'hybrid', 'impactful', 'incremental', 'interactive', 'intermediate', 'leading-edge', 'local', 'logistical', 'maximized', 'methodical', 'mission-critical', 'mobile', 'modular', 'motivating', 'multimedia', 'multi-tasking', 'national', 'needs-based', 'neutral', 'next-generation', 'object-oriented', 'optimal', 'optimizing', 'radical', 'real-time', 'reciprocal', 'regional', 'responsive', 'scalable', 'secondary', 'solution-oriented', 'stable', 'systemic', 'systematic', 'tangible', 'transitional', 'uniform', 'upward-trending', 'user-facing', 'value-added', 'web-enabled', 'well-modulated', 'zero-defect'],
        ['ability', 'access', 'adapter', 'algorithm', 'alliance', 'analyzer', 'application', 'approach', 'architecture', 'archive', 'artificial intelligence', 'attitude', 'benchmark', 'blockchain', 'capability', 'capacity', 'challenge', 'circuit', 'collaboration', 'complexity', 'concept', 'conglomeration', 'contingency', 'core', 'customer loyalty', 'database', 'data-warehouse', 'definition', 'emulation', 'encoding', 'encryption', 'extranet', 'firmware', 'flexibility', 'forecast', 'frame', 'framework', 'function', 'functionality', 'groupware', 'hardware', 'help-desk', 'hierarchy', 'hub', 'implementation', 'infrastructure', 'initiative', 'installation', 'instruction set', 'interface', 'internet solution', 'intranet', 'knowledge base', 'leverage', 'local area network', 'matrix', 'methodology', 'middleware', 'migration', 'model', 'moderator', 'monitoring', 'neural-net', 'open architecture', 'open system', 'orchestration', 'paradigm', 'parallelism', 'policy', 'portal', 'pricing structure', 'process improvement', 'product', 'productivity', 'project', 'projection', 'protocol', 'service-desk', 'software', 'solution', 'standardization', 'strategy', 'structure', 'success', 'superstructure', 'support', 'synergy', 'system', 'task-force', 'throughput', 'time-frame', 'toolset', 'website', 'workforce'],
    ];

    public function __construct(
        protected readonly RandomizerInterface $randomizer,
        protected readonly PersonExtensionInterface $person,
    ) {
    }

    public function company(): string
    {
        $format = $this->randomizer->randomElement(static::COMPANY_FORMATS);
        $lastName = $this->companyLastName();
        $replacements = ['{lastName}' => $lastName];

        if (str_contains($format, '{otherLastName}')) {
            // limited attempts - replaced Person could always return the same name
            $attempts = 0;
            do {
                $otherLastName = $this->companyLastName();
            } while ($otherLastName === $lastName && ++$attempts < 10);

            $replacements['{otherLastName}'] = $otherLastName;
        }

        if (str_contains($format, '{word}')) {
            $replacements['{word}'] = $this->randomizer->randomElement(static::NAME_WORD);
        }

        if (str_contains($format, '{suffix}')) {
            $replacements['{suffix}'] = $this->companySuffix();
        }

        return strtr($format, $replacements);
    }

    public function companySuffix(): string
    {
        return $this->randomizer->randomElement(static::SUFFIX);
    }

    public function jobTitle(): string
    {
        return $this->randomizer->randomElement(static::JOB_TITLE);
    }

    public function industry(): string
    {
        return $this->randomizer->randomElement(static::INDUSTRY);
    }

    public function catchPhrase(): string
    {
        return implode(' ', array_map($this->randomizer->randomElement(...), static::CATCH_PHRASE_WORDS));
    }

    /**
     * Surname used in company names - languages with gendered surnames use the base form ("Kowalski i Syn")
     */
    protected function companyLastName(): string
    {
        return $this->person->lastName();
    }
}
