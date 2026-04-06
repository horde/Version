<?php

declare(strict_types=1);

namespace Horde\Version\Constraint;

use Horde\Version\Version;
use Horde\Version\VersionConstraint;
use Horde\Version\RelaxedSemanticVersion;
use InvalidArgumentException;

/**
 * Tilde constraint (~1.2.3)
 *
 * Allows patch-level changes if minor version is specified:
 * - ~1.2.3 := >=1.2.3 <1.3.0
 * - ~1.2 := >=1.2.0 <2.0.0
 *
 * @copyright 2013-2026 The Horde Project (http://www.horde.org/)
 * @license   http://www.horde.org/licenses/lgpl21 LGPL-2.1
 */
class TildeConstraint implements VersionConstraint
{
    private CompositeConstraint $constraint;

    public function __construct(
        private readonly Version $baseVersion,
        private readonly bool $hasPatch = true
    ) {
        if ($baseVersion instanceof RelaxedSemanticVersion) {
            $major = $baseVersion->major;
            $minor = $baseVersion->minor;
        } else {
            throw new InvalidArgumentException('TildeConstraint requires a semantic version');
        }

        if ($this->hasPatch) {
            // ~1.2.3 := >=1.2.3 <1.3.0
            $upperBound = new RelaxedSemanticVersion($major . '.' . ($minor + 1) . '.0');
        } else {
            // ~1.2 := >=1.2.0 <2.0.0
            $upperBound = new RelaxedSemanticVersion(($major + 1) . '.0.0');
        }

        $this->constraint = new CompositeConstraint(
            [
                new ComparisonConstraint('>=', $baseVersion),
                new ComparisonConstraint('<', $upperBound),
            ],
            'AND'
        );
    }

    /**
     * Get the base version for this constraint
     *
     * @return Version
     */
    public function getBaseVersion(): Version
    {
        return $this->baseVersion;
    }

    public function isSatisfiedBy(Version $version): bool
    {
        return $this->constraint->isSatisfiedBy($version);
    }

    public function __toString(): string
    {
        return '~' . $this->baseVersion;
    }
}
