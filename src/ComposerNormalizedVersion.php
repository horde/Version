<?php

declare(strict_types=1);

/**
 * Copyright 2013-2026 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 */

namespace Horde\Version;

/**
 * Composer's normalized four-component version format.
 *
 * Format: major.minor.patch.subpatch[-stability[revision]]
 *
 * Valid stabilities: dev, alpha, beta, RC, patch, pl, p, stable
 * Examples: 3.1.0.0, 1.0.0.0-alpha1, 2.5.3.0-RC2, 1.0.0.0-dev
 */
class ComposerNormalizedVersion implements Version
{
    public readonly int $major;
    public readonly int $minor;
    public readonly int $patch;
    public readonly int $subpatch;
    public readonly bool $isPreRelease;
    public readonly string $stability;
    public readonly int $stabilityRevision;

    private const STABILITY_RANKS = [
        'dev' => 0,
        'alpha' => 1,
        'beta' => 2,
        'RC' => 3,
        'patch' => 4,
        'stable' => 5,
    ];

    public function __construct(
        public readonly string $versionString,
    ) {
        $regex = '/^(?P<major>\d+)\.(?P<minor>\d+)\.(?P<patch>\d+)\.(?P<subpatch>\d+)(?:-(?P<stability>dev|alpha|beta|RC|patch|pl|p|stable)(?P<revision>\d*))?$/i';
        if (!preg_match($regex, $this->versionString, $matches)) {
            throw new InvalidVersionException(
                'Version did not parse to expected Composer normalized format: ' . $this->versionString
            );
        }

        $this->major = (int) $matches['major'];
        $this->minor = (int) $matches['minor'];
        $this->patch = (int) $matches['patch'];
        $this->subpatch = (int) $matches['subpatch'];

        if (isset($matches['stability']) && $matches['stability'] !== '') {
            $this->stability = $this->normalizeStability($matches['stability']);
            $this->stabilityRevision = $matches['revision'] !== '' ? (int) $matches['revision'] : 0;
            $this->isPreRelease = $this->stability !== 'stable' && $this->stability !== 'patch';
        } else {
            $this->stability = 'stable';
            $this->stabilityRevision = 0;
            $this->isPreRelease = false;
        }
    }

    public function __toString(): string
    {
        return $this->versionString;
    }

    /**
     * Convert to a SemVer 2.0.0 compatible string representation.
     *
     * Drops the subpatch component and maps Composer stability to SemVer prerelease.
     */
    public function formatSemVerV2(): string
    {
        $version = "{$this->major}.{$this->minor}.{$this->patch}";
        if ($this->isPreRelease) {
            $revision = $this->stabilityRevision > 0 ? ".{$this->stabilityRevision}" : '';
            $version .= '-' . strtolower($this->stability) . $revision;
        }
        return $version;
    }

    /**
     * Compare this version to another ComposerNormalizedVersion.
     *
     * @return int -1 if this < other, 0 if equal, 1 if this > other
     */
    public function compare(self $other): int
    {
        $result = $this->major <=> $other->major;
        if ($result !== 0) {
            return $result;
        }

        $result = $this->minor <=> $other->minor;
        if ($result !== 0) {
            return $result;
        }

        $result = $this->patch <=> $other->patch;
        if ($result !== 0) {
            return $result;
        }

        $result = $this->subpatch <=> $other->subpatch;
        if ($result !== 0) {
            return $result;
        }

        $result = self::STABILITY_RANKS[$this->stability] <=> self::STABILITY_RANKS[$other->stability];
        if ($result !== 0) {
            return $result;
        }

        return $this->stabilityRevision <=> $other->stabilityRevision;
    }

    public function isGreaterThan(self $other): bool
    {
        return $this->compare($other) > 0;
    }

    public function isLessThan(self $other): bool
    {
        return $this->compare($other) < 0;
    }

    public function equals(self $other): bool
    {
        return $this->compare($other) === 0;
    }

    /**
     * Normalize shorthand stability identifiers to their canonical form.
     */
    private function normalizeStability(string $stability): string
    {
        return match (strtolower($stability)) {
            'dev' => 'dev',
            'alpha', 'a' => 'alpha',
            'beta', 'b' => 'beta',
            'rc' => 'RC',
            'patch', 'pl', 'p' => 'patch',
            'stable' => 'stable',
        };
    }
}
