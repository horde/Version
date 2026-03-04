<?php

declare(strict_types=1);

namespace Horde\Version\Constraint;

use Horde\Version\Version;
use Horde\Version\VersionConstraint;
use Horde\Version\RelaxedSemanticVersion;

/**
 * Caret constraint (^1.2.3)
 *
 * Allows changes that do not modify the left-most non-zero digit:
 * - ^1.2.3 := >=1.2.3 <2.0.0
 * - ^0.2.3 := >=0.2.3 <0.3.0
 * - ^0.0.3 := >=0.0.3 <0.0.4
 *
 * @copyright 2013-2026 The Horde Project (http://www.horde.org/)
 * @license   http://www.horde.org/licenses/lgpl21 LGPL-2.1
 */
class CaretConstraint implements VersionConstraint
{
    private CompositeConstraint $constraint;

    public function __construct(
        private readonly Version $baseVersion
    ) {
        // Parse to get numeric components
        if ($baseVersion instanceof RelaxedSemanticVersion) {
            $major = $baseVersion->major;
            $minor = $baseVersion->minor;
            $patch = $baseVersion->patch;
        } else {
            throw new \InvalidArgumentException('CaretConstraint requires a semantic version');
        }

        // Determine upper bound based on left-most non-zero
        if ($major > 0) {
            // ^1.2.3 := >=1.2.3 <2.0.0
            $upperBound = new RelaxedSemanticVersion(($major + 1) . '.0.0');
        } elseif ($minor > 0) {
            // ^0.2.3 := >=0.2.3 <0.3.0
            $upperBound = new RelaxedSemanticVersion('0.' . ($minor + 1) . '.0');
        } else {
            // ^0.0.3 := >=0.0.3 <0.0.4
            $upperBound = new RelaxedSemanticVersion('0.0.' . ($patch + 1));
        }

        $this->constraint = new CompositeConstraint(
            [
                new ComparisonConstraint('>=', $baseVersion),
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
        return '^' . $this->baseVersion;
    }
}
