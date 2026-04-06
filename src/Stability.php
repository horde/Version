<?php

declare(strict_types=1);

namespace Horde\Version;

use LogicException;

/**
 * Stability is a concept mostly alien to the SemVer 2.0.0 specification.
 *
 * SemVer 2.0.0 knows releases and pre-releases, but not stability.
 * It however acknowledges versions 0.x.y as unstable.
 *
 * In practice, pre-releases are often assigned a meaning.
 * A development build "dev" is normally considered an ephemeral version
 * Publicly visible versions are, in order of stability, "dev", "alpha", "beta", "rc" and "stable".
 * Stable is normally denoted by omitting a pre-release part.
 *
 */
class Stability
{
    public readonly string $stability;
    public readonly int $stabilityRank;
    public function __construct(
        private SemVerV2Version $version,
    ) {
        if ($this->isStable()) {
            $this->stability = 'stable';
        } elseif ($this->isAlpha()) {
            $this->stability = 'alpha';
        } elseif ($this->isBeta()) {
            $this->stability = 'beta';
        } elseif ($this->isReleaseCandidate()) {
            $this->stability = 'rc';
        } elseif ($this->isDevelopment()) {
            $this->stability = 'dev';
        } else {
            throw new LogicException('Unknown stability');
        }
        $this->stabilityRank = self::getStabilityRank($this->stability);
    }

    public function isStable(): bool
    {
        return (!$this->version->isPreRelease) && ($this->version->major > 0);
    }

    public function isAlpha(): bool
    {
        return $this->version->isPreRelease && str_starts_with(mb_strtolower($this->version->preRelease), 'alpha');
    }
    public function isBeta(): bool
    {
        return $this->version->isPreRelease && str_starts_with(mb_strtolower($this->version->preRelease), 'beta');
    }
    public function isReleaseCandidate(): bool
    {
        return $this->version->isPreRelease && str_starts_with(mb_strtolower($this->version->preRelease), 'rc');
    }

    public function isDevelopment(): bool
    {
        if ($this->isStable()) {
            return false;
        }
        if ($this->isAlpha()) {
            return false;
        }
        if ($this->isBeta()) {
            return false;
        }
        if ($this->isReleaseCandidate()) {
            return false;
        }
        return true;
    }

    /**
     * Return an integer for comparing stabilities.
     */
    public static function getStabilityRank(string $stability): int
    {
        switch ($stability) {
            case 'dev':
                return 0;
            case 'alpha':
                return 1;
            case 'beta':
                return 2;
            case 'rc':
                return 3;
            case 'stable':
                return 1000;
            default:
                throw new LogicException('Unknown stability: ' . $stability);
        }
    }

    /**
     * Return the stability revision of a prerelease version.
     *
     * Supports "alphaN" and "alpha.N"
     * Bare "alpha" is considered "alpha1"
     *
     * Currently only supports stability in the first part of the pre-release.
     */
    public function getStabilityRevision(): int
    {
        if ($this->isStable()) {
            return 0;
        }
        $parts = $this->version->getPreReleaseParts();
        $supported = ['alpha', 'beta', 'rc', 'dev'];
        if (count($parts) == 1) {
            if (in_array(mb_strtolower($parts[0]), $supported)) {
                return 1;
            }
            foreach ($supported as $stability) {
                if (str_starts_with(mb_strtolower($parts[0]), $stability)) {
                    return (int) substr($parts[0], strlen($stability));
                }
            }
        }
        if (count($parts) > 1 && is_numeric($parts[1])) {
            return (int) $parts[1];
        }
        // This is neither "alpha" nor "alphaN" nor "alpha.n" - Should we throw an exception here?
        return 1;
    }
}
