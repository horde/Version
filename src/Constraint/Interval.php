<?php

declare(strict_types=1);

namespace Horde\Version\Constraint;

use Horde\Version\SemVerV2Comparison;
use Horde\Version\SemVerV2Version;

/**
 * A normalized version interval
 *
 * An optional lower bound and an optional upper bound
 *
 * Every non-degenerate version constraint (caret, tilde, wildcard, exact or
 * a single comparison or an AND of these) describes exactly one such
 * interval. {@see \Horde\Version\ConstraintBounds} extracts it.
 *
 * A null bound means "unbounded on that side". The both empty case is
 * {@see self::unbounded()}.
 *
 * @copyright 2013-2026 The Horde Project (http://www.horde.org/)
 * @license   http://www.horde.org/licenses/lgpl21 LGPL-2.1
 */
final class Interval
{
    public function __construct(
        public readonly ?SemVerV2Version $lower,
        public readonly bool $lowerInclusive,
        public readonly ?SemVerV2Version $upper,
        public readonly bool $upperInclusive,
    ) {}

    /**
     * An interval which admits every version unbounded both sides
     */
    public static function unbounded(): self
    {
        return new self(null, false, null, false);
    }

    public function hasLower(): bool
    {
        return $this->lower !== null;
    }

    public function hasUpper(): bool
    {
        return $this->upper !== null;
    }

    /**
     * Intersect this interval with another interval (logical AND)
     *
     * The result takes the higher of the two lower bounds and the lower
     * (tighter) of the two upper bounds. An unbounded side on either
     * operand yields the other operand's bound. When the two bounds on a
     * side are equal. The result is inclusive only if both were.
     */
    public function intersect(self $other): self
    {
        [$lower, $lowerInclusive] = $this->pickLower($other);
        [$upper, $upperInclusive] = $this->pickUpper($other);

        return new self($lower, $lowerInclusive, $upper, $upperInclusive);
    }

    /**
     * Choose the higher (tighter) lower bound between this and $other.
     *
     * @return array{0: ?SemVerV2Version, 1: bool}
     */
    private function pickLower(self $other): array
    {
        if ($this->lower === null) {
            return [$other->lower, $other->lowerInclusive];
        }
        if ($other->lower === null) {
            return [$this->lower, $this->lowerInclusive];
        }

        $cmp = self::compare($this->lower, $other->lower);
        if ($cmp > 0) {
            return [$this->lower, $this->lowerInclusive];
        }
        if ($cmp < 0) {
            return [$other->lower, $other->lowerInclusive];
        }

        // Equal bounds: inclusive only if both are.
        return [$this->lower, $this->lowerInclusive && $other->lowerInclusive];
    }

    /**
     * Choose the lower (tighter) upper bound between this and $other.
     *
     * @return array{0: ?SemVerV2Version, 1: bool}
     */
    private function pickUpper(self $other): array
    {
        if ($this->upper === null) {
            return [$other->upper, $other->upperInclusive];
        }
        if ($other->upper === null) {
            return [$this->upper, $this->upperInclusive];
        }

        $cmp = self::compare($this->upper, $other->upper);
        if ($cmp < 0) {
            return [$this->upper, $this->upperInclusive];
        }
        if ($cmp > 0) {
            return [$other->upper, $other->upperInclusive];
        }

        // Equal bounds: inclusive only if both are.
        return [$this->upper, $this->upperInclusive && $other->upperInclusive];
    }

    private static function compare(SemVerV2Version $a, SemVerV2Version $b): int
    {
        return (new SemVerV2Comparison())->compare($a, $b);
    }
}
