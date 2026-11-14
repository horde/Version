<?php

declare(strict_types=1);

namespace Horde\Version;

use Horde\Version\Constraint\CaretConstraint;
use Horde\Version\Constraint\ComparisonConstraint;
use Horde\Version\Constraint\CompositeConstraint;
use Horde\Version\Constraint\ExactConstraint;
use Horde\Version\Constraint\Interval;
use Horde\Version\Constraint\TildeConstraint;
use Horde\Version\Constraint\WildcardConstraint;

/**
 * Extract the version {@see Interval} described by a constraint.
 *
 * Every bounded constraint shape maps to a single contiguous interval:
 * - Exact is a point
 * - A comparison is a ray
 * - Caret/tilde/wildcard are  major-/minor-anchored ranges
 * - An AND composite is the intersectioncof its children.
 * - OR composites (a union)
 * - A bare inequality constraint (!=) is not intervalable and raises
 * {@see NotIntervalableException}.
 * TODO: Strictly a not is an interval of point length, but as an exclusion
 *
 * The caret/tilde/wildcard upper bounds are recomputed here with the same
 * formulas their constructors use.
 * The constraint classes need not expose a precomputed upper bound.
 *
 * @copyright 2013-2026 The Horde Project (http://www.horde.org/)
 * @license   http://www.horde.org/licenses/lgpl21 LGPL-2.1
 */
final class ConstraintBounds
{
    /**
     * Extract the interval for a constraint.
     *
     * @throws NotIntervalableException If the constraint is an OR
     *                                  composite or a bare != constraint.
     */
    public function of(VersionConstraint $constraint): Interval
    {
        if ($constraint instanceof ExactConstraint) {
            $v = $this->relaxed($constraint->getTargetVersion());
            return new Interval($v, true, $v, true);
        }

        if ($constraint instanceof ComparisonConstraint) {
            return $this->fromComparison($constraint);
        }

        if ($constraint instanceof CaretConstraint) {
            return $this->fromCaret($constraint);
        }

        if ($constraint instanceof TildeConstraint) {
            return $this->fromTilde($constraint);
        }

        if ($constraint instanceof WildcardConstraint) {
            return $this->fromWildcard($constraint);
        }

        if ($constraint instanceof CompositeConstraint) {
            return $this->fromComposite($constraint);
        }

        throw new NotIntervalableException(
            'Unsupported constraint type: ' . $constraint::class
        );
    }

    /**
     * Extract the interval or null when constraint is not intervalable.
     */
    public function tryOf(VersionConstraint $constraint): ?Interval
    {
        try {
            return $this->of($constraint);
        } catch (NotIntervalableException) {
            return null;
        }
    }

    private function fromComparison(ComparisonConstraint $c): Interval
    {
        $v = $this->relaxed($c->getTargetVersion());

        return match ($c->getOperator()) {
            '>=' => new Interval($v, true, null, false),
            '>' => new Interval($v, false, null, false),
            '<' => new Interval(null, false, $v, false),
            '<=' => new Interval(null, false, $v, true),
            '=', '==' => new Interval($v, true, $v, true),
            default => throw new NotIntervalableException(
                'Comparison operator "' . $c->getOperator() . '" has no interval'
            ),
        };
    }

    private function fromCaret(CaretConstraint $c): Interval
    {
        $base = $this->relaxed($c->getBaseVersion());
        $major = $base->major;
        $minor = $base->minor;
        $patch = $base->patch;

        if ($major > 0) {
            $upper = ($major + 1) . '.0.0';
        } elseif ($minor > 0) {
            $upper = '0.' . ($minor + 1) . '.0';
        } else {
            $upper = '0.0.' . ($patch + 1);
        }

        return new Interval($base, true, new RelaxedSemanticVersion($upper), false);
    }

    private function fromTilde(TildeConstraint $c): Interval
    {
        $base = $this->relaxed($c->getBaseVersion());

        if ($c->hasPatch()) {
            $upper = $base->major . '.' . ($base->minor + 1) . '.0';
        } else {
            $upper = ($base->major + 1) . '.0.0';
        }

        return new Interval($base, true, new RelaxedSemanticVersion($upper), false);
    }

    private function fromWildcard(WildcardConstraint $c): Interval
    {
        $major = $c->getMajor();
        $minor = $c->getMinor();
        $patch = $c->getPatch();

        $lower = new RelaxedSemanticVersion(
            $major . '.' . ($minor ?? 0) . '.' . ($patch ?? 0)
        );

        if ($patch !== null) {
            $upper = $major . '.' . $minor . '.' . ($patch + 1);
        } elseif ($minor !== null) {
            $upper = $major . '.' . ($minor + 1) . '.0';
        } else {
            $upper = ($major + 1) . '.0.0';
        }

        return new Interval($lower, true, new RelaxedSemanticVersion($upper), false);
    }

    private function fromComposite(CompositeConstraint $c): Interval
    {
        if ($c->getOperator() !== 'AND') {
            throw new NotIntervalableException(
                'OR composite constraints describe a union, not a single interval'
            );
        }

        $interval = Interval::unbounded();
        foreach ($c->getConstraints() as $child) {
            $interval = $interval->intersect($this->of($child));
        }

        return $interval;
    }

    /**
     * Normalize any Version to a RelaxedSemanticVersion
     */
    private function relaxed(Version $version): RelaxedSemanticVersion
    {
        if ($version instanceof RelaxedSemanticVersion) {
            return $version;
        }

        return new RelaxedSemanticVersion((string) $version);
    }
}
