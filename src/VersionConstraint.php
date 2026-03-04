<?php

declare(strict_types=1);

namespace Horde\Version;

/**
 * Interface for version constraints
 *
 * Version constraints define rules for matching versions,
 * commonly used in dependency management systems.
 *
 * @copyright 2013-2026 The Horde Project (http://www.horde.org/)
 * @license   http://www.horde.org/licenses/lgpl21 LGPL-2.1
 */
interface VersionConstraint
{
    /**
     * Check if a version satisfies this constraint
     *
     * @param Version $version The version to check
     * @return bool True if the version satisfies the constraint
     */
    public function isSatisfiedBy(Version $version): bool;

    /**
     * Get the string representation of this constraint
     *
     * @return string
     */
    public function __toString(): string;
}
