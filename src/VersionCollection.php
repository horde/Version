<?php

declare(strict_types=1);

namespace Horde\Version;

/**
 * Utilities for working with collections of versions
 *
 * Provides sorting, filtering, and selection operations
 * on arrays of version strings or Version objects.
 *
 * @copyright 2013-2026 The Horde Project (http://www.horde.org/)
 * @license   http://www.horde.org/licenses/lgpl21 LGPL-2.1
 */
class VersionCollection
{
    private static SemVerV2Comparison $comparison;

    /**
     * Sort versions in ascending order (oldest first)
     *
     * @param array<string|Version> $versions Array of version strings or Version objects
     * @param bool $descending Sort in descending order (newest first)
     * @return array<string|Version> Sorted array in same format as input
     */
    public static function sort(array $versions, bool $descending = false): array
    {
        if (empty($versions)) {
            return [];
        }

        $comparison = self::getComparison();
        $isString = is_string($versions[array_key_first($versions)]);

        // Convert strings to versions for sorting
        $items = [];
        foreach ($versions as $key => $version) {
            $versionObj = $isString
                ? new RelaxedSemanticVersion($version)
                : $version;
            $items[$key] = ['original' => $version, 'version' => $versionObj];
        }

        // Sort using comparison
        uasort($items, function ($a, $b) use ($comparison, $descending) {
            $result = $comparison->compare($a['version'], $b['version']);
            return $descending ? -$result : $result;
        });

        // Extract original values
        return array_values(array_map(fn($item) => $item['original'], $items));
    }

    /**
     * Find the latest (newest) version
     *
     * @param array<string|Version> $versions Array of version strings or Version objects
     * @param string|null $minStability Minimum stability level (dev, alpha, beta, rc, stable)
     * @return string|Version|null Latest version or null if none found
     */
    public static function latest(array $versions, ?string $minStability = null): string|Version|null
    {
        if (empty($versions)) {
            return null;
        }

        // Filter by stability if requested
        if ($minStability !== null) {
            $versions = self::filter($versions, fn($v) => self::meetsStability($v, $minStability));
            if (empty($versions)) {
                return null;
            }
        }

        $sorted = self::sort($versions, descending: true);
        return $sorted[0] ?? null;
    }

    /**
     * Find the oldest version
     *
     * @param array<string|Version> $versions Array of version strings or Version objects
     * @param string|null $minStability Minimum stability level
     * @return string|Version|null Oldest version or null if none found
     */
    public static function oldest(array $versions, ?string $minStability = null): string|Version|null
    {
        if (empty($versions)) {
            return null;
        }

        // Filter by stability if requested
        if ($minStability !== null) {
            $versions = self::filter($versions, fn($v) => self::meetsStability($v, $minStability));
            if (empty($versions)) {
                return null;
            }
        }

        $sorted = self::sort($versions, descending: false);
        return $sorted[0] ?? null;
    }

    /**
     * Filter versions by criteria
     *
     * @param array<string|Version> $versions Array of version strings or Version objects
     * @param callable|VersionConstraint|string $criteria Filter criteria:
     *   - callable: function(Version): bool
     *   - VersionConstraint: constraint to satisfy
     *   - string: stability level or constraint string
     * @return array<string|Version> Filtered array in same format as input
     */
    public static function filter(array $versions, callable|VersionConstraint|string $criteria): array
    {
        if (empty($versions)) {
            return [];
        }

        $isString = is_string($versions[array_key_first($versions)]);

        // Build filter function
        $filterFn = self::buildFilterFunction($criteria);

        $result = [];
        foreach ($versions as $version) {
            $versionObj = $isString
                ? new RelaxedSemanticVersion($version)
                : $version;

            if ($filterFn($versionObj)) {
                $result[] = $version;
            }
        }

        return $result;
    }

    /**
     * Group versions by major or minor version
     *
     * @param array<string|Version> $versions Array of version strings or Version objects
     * @param string $by Group by 'major' or 'minor'
     * @return array<string, array> Grouped versions keyed by version series (e.g., '1.x', '1.2.x')
     */
    public static function group(array $versions, string $by = 'major'): array
    {
        if (!in_array($by, ['major', 'minor'])) {
            throw new \InvalidArgumentException("Group by must be 'major' or 'minor'");
        }

        if (empty($versions)) {
            return [];
        }

        $isString = is_string($versions[array_key_first($versions)]);
        $groups = [];

        foreach ($versions as $version) {
            $versionObj = $isString
                ? new RelaxedSemanticVersion($version)
                : $version;

            if (!($versionObj instanceof RelaxedSemanticVersion)) {
                continue; // Skip non-semantic versions
            }

            $key = $by === 'major'
                ? $versionObj->major . '.x'
                : $versionObj->major . '.' . $versionObj->minor . '.x';

            if (!isset($groups[$key])) {
                $groups[$key] = [];
            }
            $groups[$key][] = $version;
        }

        return $groups;
    }

    /**
     * Get comparison instance
     */
    private static function getComparison(): SemVerV2Comparison
    {
        if (!isset(self::$comparison)) {
            self::$comparison = new SemVerV2Comparison();
        }
        return self::$comparison;
    }

    /**
     * Build filter function from criteria
     */
    private static function buildFilterFunction(callable|VersionConstraint|string $criteria): callable
    {
        if (is_callable($criteria)) {
            return $criteria;
        }

        if ($criteria instanceof VersionConstraint) {
            return fn(Version $v) => $criteria->isSatisfiedBy($v);
        }

        // String: either stability level or constraint string
        $stabilityLevels = ['dev', 'alpha', 'beta', 'rc', 'stable'];
        if (in_array($criteria, $stabilityLevels)) {
            return fn(Version $v) => self::meetsStability($v, $criteria);
        }

        // Try parsing as constraint
        $parser = new ConstraintParser();
        try {
            $constraint = $parser->parse($criteria);
            return fn(Version $v) => $constraint->isSatisfiedBy($v);
        } catch (InvalidVersionException $e) {
            throw new \InvalidArgumentException("Invalid filter criteria: {$criteria}", 0, $e);
        }
    }

    /**
     * Check if version meets minimum stability requirement
     */
    private static function meetsStability(string|Version $version, string $minStability): bool
    {
        $versionObj = is_string($version)
            ? new RelaxedSemanticVersion($version)
            : $version;

        if (!($versionObj instanceof RelaxedSemanticVersion)) {
            return false;
        }

        $stability = new Stability($versionObj);
        $minRank = Stability::getStabilityRank($minStability);

        return $stability->stabilityRank >= $minRank;
    }
}
