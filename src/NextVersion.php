<?php

declare(strict_types=1);

namespace Horde\Version;
use Stringable;
/**
 * Generate the next version based on the current version and the severity of the change.
 */
class NextVersion
{
    public readonly RelaxedSemanticVersion $original;
    public function __construct(
        string|Stringable $original,
    ) {
        $this->original = new RelaxedSemanticVersion((string) $original);
    }


    public function __invoke(string $severity = 'patch', string $stability = 'unchanged'): RelaxedSemanticVersion
    {
        $originalStability = new Stability($this->original);
        if ($stability === 'unchanged') {
            $nextStability = $originalStability->stability;
        } else {
            $nextStability = $stability;
        }
        // Is the change direction up or down?
        $changeDirection = Stability::getStabilityRank($nextStability) <=> $originalStability->stabilityRank;
        // If we are going more unstable, we need to increment the version as a prerelease of the same version might already exist
        if ($changeDirection < 0) {
            $versionString =
                $major = ($severity == 'major') ? $this->original->major + 1 : $this->original->major;
                $minor = ($severity == 'minor') ? $this->original->minor + 1 : $this->original->minor;
                $patch = ($severity == 'patch') ? $this->original->patch + 1 : $this->original->patch;
                $versionString =
                $this->original->prefix . $major . '.' . $minor . '.' . $patch;

            if ($nextStability !== 'stable') {
                $versionString .= '-' . $nextStability . '.1';
            }
            return new RelaxedSemanticVersion($versionString);
        } elseif ($changeDirection > 0) {
            // If upgrading stability, the version number does not change except upgrading to stable from 0.x
            // The next pre-release level starts at revision 1
            if ($this->original->major === 0 && $nextStability === 'stable') {
                // The first stable version is 1.0.0
                $versionString =
                    $this->original->prefix .
                    '1.0.0';

            } else {

                // If the major version is not 0, we keep the same version number
                $versionString =
                $this->original->prefix .
                $this->original->major . '.' .
                $this->original->minor . '.' .
                $this->original->patch;
                if ($nextStability !== 'stable') {
                    $versionString .= '-' . $nextStability . '.1';
                }
            }
            return new RelaxedSemanticVersion($versionString);
        } else {
            // No change in stability
            if ($this->original->major === 0) {
                // If the major version is 0, we are still in development.
                // New non-patch releases are feature releases
                $versionString =                 $this->original->prefix .

                $this->original->major . '.' .
                ($severity != 'patch' ? $this->original->minor + 1 : $this->original->minor) . '.' .
                ($severity != 'patch' ? $this->original->patch : $this->original->patch) + 1;
            } else {
                if ($nextStability === 'stable') {
                    // If the major version is not 0, we are stable
                    $major = ($severity == 'major') ? $this->original->major + 1 : $this->original->major;
                    $minor = ($severity == 'minor') ? $this->original->minor + 1 : $this->original->minor;
                    $patch = ($severity == 'patch') ? $this->original->patch + 1 : $this->original->patch;
                    $versionString =                 $this->original->prefix .
                        $major . '.' .
                        $minor . '.' .
                        $patch;
                } else {
                    // Unstable target versions > 0.x.y
                   $versionString = $this->original->prefix .
                    $this->original->major .
                    $this->original->minor .
                    $this->original->patch .
                    '-' . $nextStability . '.' .
                    ($originalStability->getStabilityRevision() + 1);
                }
            }
            return new RelaxedSemanticVersion($versionString);
        }
    }
}
