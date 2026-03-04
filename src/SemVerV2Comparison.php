<?php

declare(strict_types=1);

namespace Horde\Version;

/**
 * A strict interpretation of the SemVer 2.0.0 specification.
 * Notably, this puts "dev" between "beta" and "rc" rather than before "alpha".
 */
class SemVerV2Comparison
{
    public function __invoke(SemVerV2Version $a, SemVerV2Version $b): int
    {
        return $this->compare($a, $b);
    }

    public function compare(SemVerV2Version $a, SemVerV2Version $b): int
    {
        $major = $a->major <=> $b->major;
        if ($major !== 0) {
            return $major;
        }
        $minor = $a->minor <=> $b->minor;
        if ($minor !== 0) {
            return $minor;
        }
        $patch = $a->patch <=> $b->patch;
        if ($patch !== 0) {
            return $patch;
        }
        if ($a->isPreRelease && !$b->isPreRelease) {
            return -1;
        }

        if (!$a->isPreRelease && $b->isPreRelease) {
            return 1;
        }
        if (!$a->isPreRelease && !$b->isPreRelease) {
            return 0;
        }
        // Prerelease comparison
        $aPreReleaseParts = explode('.', $a->preRelease);
        $bPreReleaseParts = explode('.', $b->preRelease);
        // Make the test loop continue until the longest prerelease part or the first non-equal part
        if (count($aPreReleaseParts) <= count($bPreReleaseParts)) {
            $count = count($bPreReleaseParts);
        } else {
            $count = count($aPreReleaseParts);
        }
        for ($i = 0; $i < $count; $i++) {
            // prerelease with more parts is greater if all else is equal
            if (!isset($aPreReleaseParts[$i])) {
                return -1;
            }
            if (!isset($bPreReleaseParts[$i])) {
                return 1;
            }
            // If both parts are numbers, compare them as numbers
            if (is_numeric($aPreReleaseParts[$i]) && is_numeric($bPreReleaseParts[$i])) {
                $result = (int) $aPreReleaseParts[$i] <=> (int) $bPreReleaseParts[$i];
                if ($result !== 0) {
                    return $result;
                }
            } elseif (is_numeric($aPreReleaseParts[$i])) {
                return -1;
            } elseif (is_numeric($bPreReleaseParts[$i])) {
                return 1;
            } // If both parts are strings, compare them as strings

            else {
                $result = strcmp($aPreReleaseParts[$i], $bPreReleaseParts[$i]);
                if ($result !== 0) {
                    return $result <=> 0; // Normalize to -1, 0, or 1
                }
            }
        }
        return 0;
    }
}
