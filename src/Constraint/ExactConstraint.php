<?php

declare(strict_types=1);

namespace Horde\Version\Constraint;

use Horde\Version\Version;
use Horde\Version\VersionConstraint;
use Horde\Version\SemVerV2Comparison;

/**
 * Exact version constraint (=1.0.0 or just 1.0.0)
 *
 * @copyright 2013-2026 The Horde Project (http://www.horde.org/)
 * @license   http://www.horde.org/licenses/lgpl21 LGPL-2.1
 */
class ExactConstraint implements VersionConstraint
{
    private SemVerV2Comparison $comparison;

    public function __construct(
        private readonly Version $targetVersion
    ) {
        $this->comparison = new SemVerV2Comparison();
    }

    public function isSatisfiedBy(Version $version): bool
    {
        return $this->comparison->compare($version, $this->targetVersion) === 0;
    }

    public function __toString(): string
    {
        return (string) $this->targetVersion;
    }
}
