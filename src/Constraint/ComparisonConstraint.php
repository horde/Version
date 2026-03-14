<?php

declare(strict_types=1);

namespace Horde\Version\Constraint;

use Horde\Version\Version;
use Horde\Version\VersionConstraint;
use Horde\Version\SemVerV2Comparison;

/**
 * Comparison constraint (>=1.0.0, <2.0.0, etc.)
 *
 * @copyright 2013-2026 The Horde Project (http://www.horde.org/)
 * @license   http://www.horde.org/licenses/lgpl21 LGPL-2.1
 */
class ComparisonConstraint implements VersionConstraint
{
    private SemVerV2Comparison $comparison;

    public function __construct(
        private readonly string $operator,
        private readonly Version $targetVersion
    ) {
        if (!in_array($operator, ['>', '>=', '<', '<=', '=', '==', '!=', '<>'])) {
            throw new \InvalidArgumentException("Invalid comparison operator: {$operator}");
        }
        $this->comparison = new SemVerV2Comparison();
    }

    /**
     * Get the comparison operator
     *
     * @return string
     */
    public function getOperator(): string
    {
        return $this->operator;
    }

    /**
     * Get the target version
     *
     * @return Version
     */
    public function getTargetVersion(): Version
    {
        return $this->targetVersion;
    }

    public function isSatisfiedBy(Version $version): bool
    {
        $result = $this->comparison->compare($version, $this->targetVersion);

        return match($this->operator) {
            '>' => $result > 0,
            '>=' => $result >= 0,
            '<' => $result < 0,
            '<=' => $result <= 0,
            '=', '==' => $result === 0,
            '!=', '<>' => $result !== 0,
            default => false,
        };
    }

    public function __toString(): string
    {
        return $this->operator . $this->targetVersion;
    }
}
