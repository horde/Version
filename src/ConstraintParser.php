<?php

declare(strict_types=1);

namespace Horde\Version;

use Horde\Version\Constraint\ExactConstraint;
use Horde\Version\Constraint\ComparisonConstraint;
use Horde\Version\Constraint\CaretConstraint;
use Horde\Version\Constraint\TildeConstraint;
use Horde\Version\Constraint\WildcardConstraint;
use Horde\Version\Constraint\CompositeConstraint;

/**
 * Parse version constraint strings into VersionConstraint objects
 *
 * Supports Composer-style constraint syntax:
 * - Exact: 1.0.0
 * - Comparison: >1.0.0, >=1.0.0, <2.0.0, <=2.0.0, !=1.5.0
 * - Caret: ^1.2.3, ^1.2, ^12 (>=12.0.0 <13.0.0)
 * - Tilde: ~1.2.3, ~1.2, ~12 (>=12.0.0 <13.0.0)
 * - Wildcard: 1.0.*, 1.*
 * - Range: 1.0.0 - 2.0.0
 * - AND: >=1.0 <2.0
 * - OR: ^1.0 || ^2.0
 *
 * @copyright 2013-2026 The Horde Project (http://www.horde.org/)
 * @license   http://www.horde.org/licenses/lgpl21 LGPL-2.1
 */
class ConstraintParser
{
    /**
     * Parse a constraint string
     *
     * @param string $constraintString The constraint to parse
     * @return VersionConstraint
     * @throws InvalidVersionException
     */
    public function parse(string $constraintString): VersionConstraint
    {
        $constraintString = trim($constraintString);

        if (empty($constraintString)) {
            throw new InvalidVersionException('Empty constraint string');
        }

        // Handle OR logic (||)
        if (str_contains($constraintString, '||')) {
            return $this->parseOrConstraint($constraintString);
        }

        // Handle range (1.0.0 - 2.0.0)
        if (preg_match('/^(.+?)\s*-\s*(.+)$/', $constraintString, $matches)) {
            return $this->parseRangeConstraint($matches[1], $matches[2]);
        }

        // Handle AND logic (space-separated)
        $parts = preg_split('/\s+/', $constraintString);
        if (count($parts) > 1) {
            return $this->parseAndConstraint($parts);
        }

        // Single constraint
        return $this->parseSingleConstraint($constraintString);
    }

    /**
     * Parse OR constraint (^1.0 || ^2.0)
     */
    private function parseOrConstraint(string $constraintString): CompositeConstraint
    {
        $parts = array_map('trim', explode('||', $constraintString));
        $constraints = [];
        foreach ($parts as $part) {
            $constraints[] = $this->parse($part);
        }
        return new CompositeConstraint($constraints, 'OR');
    }

    /**
     * Parse AND constraint (>=1.0 <2.0)
     */
    private function parseAndConstraint(array $parts): CompositeConstraint
    {
        $constraints = [];
        foreach ($parts as $part) {
            $constraints[] = $this->parseSingleConstraint($part);
        }
        return new CompositeConstraint($constraints, 'AND');
    }

    /**
     * Parse range constraint (1.0.0 - 2.0.0)
     */
    private function parseRangeConstraint(string $lower, string $upper): CompositeConstraint
    {
        $lowerVersion = new RelaxedSemanticVersion(trim($lower));
        $upperVersion = new RelaxedSemanticVersion(trim($upper));

        return new CompositeConstraint(
            [
                new ComparisonConstraint('>=', $lowerVersion),
                new ComparisonConstraint('<=', $upperVersion),
            ],
            'AND'
        );
    }

    /**
     * Parse a single constraint
     */
    private function parseSingleConstraint(string $constraint): VersionConstraint
    {
        $constraint = trim($constraint);

        // Caret: ^1.2.3, ^1.2, or ^12
        if (str_starts_with($constraint, '^')) {
            $versionString = substr($constraint, 1);
            $version = new RelaxedSemanticVersion($this->normalizeVersionString($versionString));
            return new CaretConstraint($version);
        }

        // Tilde: ~1.2.3, ~1.2, or ~12
        if (str_starts_with($constraint, '~')) {
            $versionString = substr($constraint, 1);
            // Check if patch level was specified before padding
            $hasPatch = substr_count($this->stripVersionSuffix($versionString), '.') >= 2;
            $version = new RelaxedSemanticVersion($this->normalizeVersionString($versionString));
            return new TildeConstraint($version, $hasPatch);
        }

        // Wildcard: 1.0.* or 1.*
        if (str_contains($constraint, '*')) {
            return $this->parseWildcardConstraint($constraint);
        }

        // Comparison operators: >=1.0.0, <2.0.0, etc.
        if (preg_match('/^(>=?|<=?|!=|<>|==?)(.+)$/', $constraint, $matches)) {
            $operator = $matches[1];
            $versionString = trim($matches[2]);
            $version = new RelaxedSemanticVersion($this->normalizeVersionString($versionString));
            return new ComparisonConstraint($operator, $version);
        }

        // Exact: 1.0.0
        $version = new RelaxedSemanticVersion($this->normalizeVersionString($constraint));
        return new ExactConstraint($version);
    }

    /**
     * Pad a version string to at least major.minor.patch.
     *
     * Accepts inputs like "12", "v12", "12-alpha1", "12.5", "12.5.0+build" and
     * pads any missing minor or patch component with zeros. Pre-release and
     * build-metadata suffixes are preserved.
     *
     * "12"          -> "12.0.0"
     * "v12-alpha1"  -> "v12.0.0-alpha1"
     * "12.5"        -> "12.5.0"
     * "12.5.0"      -> "12.5.0" (unchanged)
     */
    private function normalizeVersionString(string $versionString): string
    {
        if (!preg_match(
            '/^(?P<prefix>[a-zA-Z]*)(?P<numeric>\d+(?:\.\d+){0,2})(?P<suffix>.*)$/',
            $versionString,
            $matches
        )) {
            return $versionString;
        }

        $parts = explode('.', $matches['numeric']);
        while (count($parts) < 3) {
            $parts[] = '0';
        }

        return $matches['prefix'] . implode('.', $parts) . $matches['suffix'];
    }

    /**
     * Strip the alphabetic prefix and pre-release/build suffix from a version
     * string, leaving only the dotted numeric core. Used to detect how many
     * components the user originally supplied.
     */
    private function stripVersionSuffix(string $versionString): string
    {
        if (!preg_match(
            '/^(?:[a-zA-Z]*)(?P<numeric>\d+(?:\.\d+){0,2})/',
            $versionString,
            $matches
        )) {
            return $versionString;
        }

        return $matches['numeric'];
    }

    /**
     * Parse wildcard constraint
     */
    private function parseWildcardConstraint(string $constraint): WildcardConstraint
    {
        // Remove any prefix
        $constraint = ltrim($constraint, 'v');

        $parts = explode('.', $constraint);
        $major = null;
        $minor = null;
        $patch = null;

        if (isset($parts[0]) && $parts[0] !== '*') {
            $major = (int) $parts[0];
        } else {
            throw new InvalidVersionException('Wildcard major version not supported: ' . $constraint);
        }

        if (isset($parts[1]) && $parts[1] !== '*') {
            $minor = (int) $parts[1];
        }

        if (isset($parts[2]) && $parts[2] !== '*') {
            $patch = (int) $parts[2];
        }

        return new WildcardConstraint($major, $minor, $patch);
    }
}
