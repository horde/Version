<?php

declare(strict_types=1);

namespace Horde\Version;

use Horde\Version\Constraint\Interval;
use InvalidArgumentException;

/**
 * Reconcile a version constraint against optional minimum and maximum bound policies.
 *
 * Given an existing constraint and a policy expressed as a minimum and/or
 * maximum constraint:
 *
 * - Raises the floor to the minimum when the existing floor is missing or
 *   lower. Tolerates a stricter (higher) existing floor
 * - Tightens the ceiling to the maximum when the existing ceiling is
 *   missing or higher. Tolerates a stricter (lower) existing ceiling
 * - Pins the respective bound exactly when the sync flag is set.
 *
 * Bounds are honored literally:
 * - Caret maximum such as "^8.6" has ceiling 9.0.0 i.e. does not cap at 8.6.
 * - A maximum as "<=8.6", "<8.7" or an exact version to cap at a specific minor.
 *
 * The result is formatted as a caret when it is exactly a major-anchored
 * caret range. Otherwise formatted as an explicit ">= / <" range.
 *
 * @copyright 2013-2026 The Horde Project (http://www.horde.org/)
 * @license   http://www.horde.org/licenses/lgpl21 LGPL-2.1
 */
final class ConstraintReconciler
{
    public function __construct(
        private readonly ConstraintParser $parser = new ConstraintParser(),
        private readonly ConstraintBounds $bounds = new ConstraintBounds(),
    ) {}

    /**
     * Reconcile $existing against the min/max policy and return the
     * resulting constraint string.
     *
     * @param string $existing The current constraint. Empty string allowed.
     * @param string|null $min The minimum bound constraint or null.
     * @param bool $syncMin Pin the floor to $min exactly.
     * @param string|null $max The maximum-bound constraint or null.
     * @param bool $syncMax Pin the ceiling to $max exactly.
     * @return string The reconciled constraint or $existing
     * @throws InvalidArgumentException If the policy yields an empty
     *                                  interval (floor >= ceiling).
     */
    public function reconcile(
        string $existing,
        ?string $min,
        bool $syncMin,
        ?string $max,
        bool $syncMax
    ): string {
        // Leave the existing constraint untouched.
        if ($min === null && $max === null) {
            return $existing;
        }

        $current = $this->intervalOf($existing) ?? Interval::unbounded();

        // Resolve the floor
        $lower = $current->lower;
        $lowerInclusive = $current->lowerInclusive;
        if ($min !== null) {
            $minInterval = $this->requireInterval($min, 'min_php_version');
            $minFloor = $minInterval->lower;
            if ($minFloor === null) {
                throw new InvalidArgumentException(
                    'min_php_version "' . $min . '" has no lower bound'
                );
            }
            if ($syncMin || $lower === null || $this->compare($lower, $minFloor) < 0) {
                $lower = $minFloor;
                $lowerInclusive = true;
            }
        }

        // Resolve the ceiling
        $upper = $current->upper;
        $upperInclusive = $current->upperInclusive;
        if ($max !== null) {
            $maxInterval = $this->requireInterval($max, 'max_php_version');
            $maxCeil = $maxInterval->upper;
            if ($maxCeil === null) {
                throw new InvalidArgumentException(
                    'max_php_version "' . $max . '" has no upper bound'
                );
            }
            if ($syncMax || $upper === null || $this->compare($upper, $maxCeil) > 0) {
                $upper = $maxCeil;
                $upperInclusive = $maxInterval->upperInclusive;
            }
        }

        // Contradiction: A non empty policy should describe a
        // non empty interval
        if ($lower !== null && $upper !== null) {
            $cmp = $this->compare($lower, $upper);
            if ($cmp > 0 || ($cmp === 0 && !($lowerInclusive && $upperInclusive))) {
                throw new InvalidArgumentException(sprintf(
                    'Policy yields an empty version interval (floor %s, ceiling %s).',
                    (string) $lower,
                    (string) $upper
                ));
            }
        }

        return $this->format($lower, $lowerInclusive, $upper, $upperInclusive);
    }

    /**
     * Parse a constraint string into its interval or null if constraint
     * is empty or not intervalable (unparseable, OR or !=).
     */
    private function intervalOf(string $constraint): ?Interval
    {
        $constraint = trim($constraint);
        if ($constraint === '') {
            return null;
        }

        try {
            return $this->bounds->tryOf($this->parser->parse($constraint));
        } catch (InvalidVersionException) {
            return null;
        }
    }

    /**
     * Parse a policy bound that must be a valid and intervalable constraint.
     */
    private function requireInterval(string $constraint, string $field): Interval
    {
        try {
            $interval = $this->bounds->tryOf($this->parser->parse($constraint));
        } catch (InvalidVersionException $e) {
            throw new InvalidArgumentException(
                $field . ' "' . $constraint . '" is not a valid constraint: ' . $e->getMessage(),
                0,
                $e
            );
        }

        if ($interval === null) {
            throw new InvalidArgumentException(
                $field . ' "' . $constraint . '" is not a single-interval constraint'
            );
        }

        return $interval;
    }

    private function compare(SemVerV2Version $a, SemVerV2Version $b): int
    {
        return (new SemVerV2Comparison())->compare($a, $b);
    }

    /**
     * Format an interval as a caret when possible or an explicit range.
     */
    private function format(
        ?SemVerV2Version $lower,
        bool $lowerInclusive,
        ?SemVerV2Version $upper,
        bool $upperInclusive
    ): string {
        $caret = $this->asCaret($lower, $lowerInclusive, $upper, $upperInclusive);
        if ($caret !== null) {
            return $caret;
        }

        $parts = [];
        if ($lower !== null) {
            $parts[] = '>=' . $this->renderVersion($lower);
        }
        if ($upper !== null) {
            $parts[] = ($upperInclusive ? '<=' : '<') . $this->renderVersion($upper);
        }

        return implode(' ', $parts);
    }

    /**
     * Return the minimal caret string when the interval is exactly a
     * major anchored caret range or null.
     */
    private function asCaret(
        ?SemVerV2Version $lower,
        bool $lowerInclusive,
        ?SemVerV2Version $upper,
        bool $upperInclusive
    ): ?string {
        if ($lower === null || $upper === null || !$lowerInclusive || $upperInclusive) {
            return null;
        }
        if ($lower->patch !== 0) {
            return null;
        }

        // The caret upper bound implied by the lower bound.
        if ($lower->major > 0) {
            $caretUpper = ($lower->major + 1) . '.0.0';
        } elseif ($lower->minor > 0) {
            $caretUpper = '0.' . ($lower->minor + 1) . '.0';
        } else {
            $caretUpper = '0.0.1';
        }

        if ($this->renderVersion($upper) !== $caretUpper) {
            return null;
        }

        // Minimal caret spelling. The patch is guaranteed to be 0 here.
        if ($lower->minor === 0) {
            return '^' . $lower->major;
        }

        return '^' . $lower->major . '.' . $lower->minor;
    }

    private function renderVersion(SemVerV2Version $v): string
    {
        return $v->major . '.' . $v->minor . '.' . $v->patch;
    }
}
