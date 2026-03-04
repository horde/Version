<?php

declare(strict_types=1);

namespace Horde\Version\Constraint;

use Horde\Version\Version;
use Horde\Version\VersionConstraint;

/**
 * Composite constraint for AND/OR logic
 *
 * Combines multiple constraints with logical operators.
 *
 * @copyright 2013-2026 The Horde Project (http://www.horde.org/)
 * @license   http://www.horde.org/licenses/lgpl21 LGPL-2.1
 */
class CompositeConstraint implements VersionConstraint
{
    /**
     * @param array<VersionConstraint> $constraints
     * @param string $operator Either 'AND' or 'OR'
     */
    public function __construct(
        private readonly array $constraints,
        private readonly string $operator = 'AND'
    ) {
        if (!in_array($operator, ['AND', 'OR'])) {
            throw new \InvalidArgumentException("Invalid logical operator: {$operator}");
        }
        if (empty($constraints)) {
            throw new \InvalidArgumentException('CompositeConstraint requires at least one constraint');
        }
        foreach ($constraints as $constraint) {
            if (!$constraint instanceof VersionConstraint) {
                throw new \InvalidArgumentException('All constraints must implement VersionConstraint');
            }
        }
    }

    public function isSatisfiedBy(Version $version): bool
    {
        if ($this->operator === 'AND') {
            foreach ($this->constraints as $constraint) {
                if (!$constraint->isSatisfiedBy($version)) {
                    return false;
                }
            }
            return true;
        } else { // OR
            foreach ($this->constraints as $constraint) {
                if ($constraint->isSatisfiedBy($version)) {
                    return true;
                }
            }
            return false;
        }
    }

    public function __toString(): string
    {
        $separator = $this->operator === 'AND' ? ' ' : ' || ';
        return implode($separator, array_map('strval', $this->constraints));
    }
}
