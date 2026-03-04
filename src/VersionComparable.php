<?php

declare(strict_types=1);

namespace Horde\Version;

/**
 * Trait providing comparison helper methods for Version implementations
 *
 * @copyright 2013-2026 The Horde Project (http://www.horde.org/)
 * @license   http://www.horde.org/licenses/lgpl21 LGPL-2.1
 */
trait VersionComparable
{
    private static ?SemVerV2Comparison $comparisonInstance = null;

    /**
     * Compare this version to another
     *
     * @param Version $other The version to compare to
     * @return int -1 if this < other, 0 if equal, 1 if this > other
     */
    public function compare(Version $other): int
    {
        return self::getComparison()->compare($this, $other);
    }

    /**
     * Check if this version is greater than another
     *
     * @param Version $other The version to compare to
     * @return bool True if this version is greater
     */
    public function isGreaterThan(Version $other): bool
    {
        return $this->compare($other) > 0;
    }

    /**
     * Check if this version is less than another
     *
     * @param Version $other The version to compare to
     * @return bool True if this version is less
     */
    public function isLessThan(Version $other): bool
    {
        return $this->compare($other) < 0;
    }

    /**
     * Check if this version equals another
     *
     * @param Version $other The version to compare to
     * @return bool True if versions are equal
     */
    public function equals(Version $other): bool
    {
        return $this->compare($other) === 0;
    }

    /**
     * Get comparison instance (singleton)
     */
    private static function getComparison(): SemVerV2Comparison
    {
        if (self::$comparisonInstance === null) {
            self::$comparisonInstance = new SemVerV2Comparison();
        }
        return self::$comparisonInstance;
    }
}
