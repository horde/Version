<?php

declare(strict_types=1);

namespace Horde\Version;

/**
 * A generic version class which works with any version string
 */
class GenericVersion implements Version
{
    public function __construct(public readonly string $versionString) {}

    public function __toString(): string
    {
        return $this->versionString;
    }

}
