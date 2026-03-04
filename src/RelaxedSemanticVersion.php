<?php

declare(strict_types=1);

namespace Horde\Version;

/**
 * A more relaxed interpretation of the SemVer 2.0.0 specification.
 * Suport Major.Minor without Patch (1.0, v1.0, v1.0-alpha1)
 * Support Prefixed Semantic Versions (v1.0.0)
 * Support Pre Releases without Hyphens if the first part is not a number
 */
class RelaxedSemanticVersion extends SemVerV2Version
{
    public readonly int $major;
    public readonly int $minor;
    public readonly int $patch;
    public readonly bool $isPreRelease;
    public readonly string $preRelease;
    public readonly bool $hasBuildInfo;
    public readonly string $buildInfo;
    public readonly string $prefix;

    public function __construct(
        public readonly string $versionString
    ) {
        /**
         * This is the officially suggested semver parsing regex from the https://semver.org/ site as of 2025-05-11
         */
        $regex =                        '/^(?P<prefix>[a-zA-Z]*)(?P<major>0|[1-9]\d*)\.(?P<minor>0|[1-9]\d*)\.(?P<patch>0|[1-9]\d*)(?:-(?P<prerelease>(?:0|[1-9]\d*|\d*[a-zA-Z-][0-9a-zA-Z-]*)(?:\.(?:0|[1-9]\d*|\d*[a-zA-Z-][0-9a-zA-Z-]*))*))?(?:\+(?P<buildmetadata>[0-9a-zA-Z-]+(?:\.[0-9a-zA-Z-]+)*))?$/';
        $regexPreReleaseWithoutHyphen = '/^(?P<prefix>[a-zA-Z]*)(?P<major>0|[1-9]\d*)\.(?P<minor>0|[1-9]\d*)(?:\.(?P<patch>0|[1-9]\d*))?(?:-?(?P<prerelease>(?:0|[1-9]\d*|\d*[a-zA-Z-][0-9a-zA-Z-]*)(?:\.(?:0|[1-9]\d*|\d*[a-zA-Z-][0-9a-zA-Z-]*))*))?(?:\+(?P<buildmetadata>[0-9a-zA-Z-]+(?:\.[0-9a-zA-Z-]+)*))?$/';

        preg_match($regex, $this->versionString, $matches);
        if (empty($matches)) {
            preg_match($regexPreReleaseWithoutHyphen, $this->versionString, $matches);
            if (empty($matches)) {
                throw new InvalidVersionException('Version did not parse to expected format Relaxed Semantic Versioning:' . $this->versionString);
            }
        }
        $this->prefix = $matches['prefix'] ?? '';
        $this->major = (int) $matches['major'];
        $this->minor = (int) $matches['minor'];
        $this->patch = (int) ($matches['patch'] ?? 0);
        $this->isPreRelease = isset($matches['prerelease']) && $matches['prerelease'] !== '';
        $this->preRelease = $matches['prerelease'] ?? '';
        $this->hasBuildInfo = isset($matches['buildmetadata']) && $matches['buildmetadata'] !== '';
        $this->buildInfo = $matches['buildmetadata'] ?? '';
    }

    public function formatSemVerV2(): string
    {
        return "{$this->major}.{$this->minor}.{$this->patch}" .
            ($this->isPreRelease ? "-{$this->preRelease}" : '') .
            ($this->hasBuildInfo ? "+{$this->buildInfo}" : '');
    }

    public function __toString(): string
    {
        return $this->versionString;
    }

    public function getPreReleaseParts(): iterable
    {
        if ($this->isPreRelease) {
            return explode('.', $this->preRelease);
        }
        return [];
    }
}
