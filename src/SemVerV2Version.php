<?php

declare(strict_types=1);

namespace Horde\Version;

/**
 * A strict interpretation of the SemVer 2.0.0 specification.
 * For a more relaxed version consider using the RelaxedSemantic class.
 */
class SemVerV2Version implements Version
{
    use VersionComparable;

    public readonly int $major;
    public readonly int $minor;
    public readonly int $patch;
    public readonly bool $isPreRelease;
    public readonly string $preRelease;
    public readonly bool $hasBuildInfo;
    public readonly string $buildInfo;

    public function __construct(
        public readonly string $versionString
    ) {
        /**
         * This is the officially suggested semver parsing regex from the https://semver.org/ site as of 2025-05-11
         */
        $regex = '/^(?P<major>0|[1-9]\d*)\.(?P<minor>0|[1-9]\d*)\.(?P<patch>0|[1-9]\d*)(?:-(?P<prerelease>(?:0|[1-9]\d*|\d*[a-zA-Z-][0-9a-zA-Z-]*)(?:\.(?:0|[1-9]\d*|\d*[a-zA-Z-][0-9a-zA-Z-]*))*))?(?:\+(?P<buildmetadata>[0-9a-zA-Z-]+(?:\.[0-9a-zA-Z-]+)*))?$/';
        preg_match($regex, $this->versionString, $matches);
        if (empty($matches)) {
            throw new InvalidVersionException('Version did not parse to expected format Semantic Versioning 2.0.0:' . $this->versionString);
        }
        $this->major = (int) $matches['major'];
        $this->minor = (int) $matches['minor'];
        $this->patch = (int) $matches['patch'];
        $this->isPreRelease = isset($matches['prerelease']) && $matches['prerelease'] !== '';
        $this->preRelease = $matches['prerelease'] ?? '';
        $this->hasBuildInfo = isset($matches['buildmetadata']) && $matches['buildmetadata'] !== '';
        $this->buildInfo = $matches['buildmetadata'] ?? '';
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
