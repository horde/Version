<?php

declare(strict_types=1);

namespace Horde\Version\Constraint;

use Horde\Version\Version;
use Horde\Version\VersionConstraint;
use Horde\Version\RelaxedSemanticVersion;

/**
 * Wildcard constraint (1.0.*, 1.*)
 *
 * Matches any version where the non-wildcard parts match:
 * - 1.0.* := >=1.0.0 <1.1.0
 * - 1.* := >=1.0.0 <2.0.0
 *
 * @copyright 2013-2026 The Horde Project (http://www.horde.org/)
 * @license   http://www.horde.org/licenses/lgpl21 LGPL-2.1
 */
class WildcardConstraint implements VersionConstraint
{
    private CompositeConstraint $constraint;
    private string $pattern;

    public function __construct(
        private readonly int $major,
        private readonly ?int $minor = null,
        private readonly ?int $patch = null
    ) {
        // Build pattern for string representation
        $this->pattern = (string) $major;
        if ($minor !== null) {
            $this->pattern .= '.' . $minor;
            if ($patch !== null) {
                $this->pattern .= '.' . $patch . '.*';
            } else {
                $this->pattern .= '.*';
            }
        } else {
            $this->pattern .= '.*';
        }

        // Build constraint
        $lowerBound = new RelaxedSemanticVersion(
            $major . '.' . ($minor ?? 0) . '.' . ($patch ?? 0)
        );

        if ($patch !== null) {
            // 1.0.0.* - not really valid in SemVer, treat as exact
            $upperBound = new RelaxedSemanticVersion(
                $major . '.' . $minor . '.' . ($patch + 1)
            );
        } elseif ($minor !== null) {
            // 1.0.* := >=1.0.0 <1.1.0
            $upperBound = new RelaxedSemanticVersion(
                $major . '.' . ($minor + 1) . '.0'
            );
        } else {
            // 1.* := >=1.0.0 <2.0.0
            $upperBound = new RelaxedSemanticVersion(
                ($major + 1) . '.0.0'
            );
        }

        $this->constraint = new CompositeConstraint(
            [
                new ComparisonConstraint('>=', $lowerBound),
                new ComparisonConstraint('<', $upperBound),
            ],
            'AND'
        );
    }

    public function isSatisfiedBy(Version $version): bool
    {
        return $this->constraint->isSatisfiedBy($version);
    }

    public function __toString(): string
    {
        return $this->pattern;
    }
}
