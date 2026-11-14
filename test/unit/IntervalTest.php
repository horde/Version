<?php

declare(strict_types=1);

namespace Horde\Version\Test\Unit;

use Horde\Version\Constraint\Interval;
use Horde\Version\RelaxedSemanticVersion;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Interval::class)]
class IntervalTest extends TestCase
{
    private function v(string $s): RelaxedSemanticVersion
    {
        return new RelaxedSemanticVersion($s);
    }

    public function testUnboundedHasNoBounds(): void
    {
        $i = Interval::unbounded();
        $this->assertNull($i->lower);
        $this->assertNull($i->upper);
        $this->assertFalse($i->hasLower());
        $this->assertFalse($i->hasUpper());
    }

    public function testIntersectPicksHigherLower(): void
    {
        $a = new Interval($this->v('8.1.0'), true, null, false);
        $b = new Interval($this->v('8.3.0'), true, null, false);

        $r = $a->intersect($b);
        $this->assertSame('8.3.0', (string) $r->lower);
        $this->assertTrue($r->lowerInclusive);
    }

    public function testIntersectPicksLowerUpper(): void
    {
        $a = new Interval(null, false, $this->v('9.0.0'), false);
        $b = new Interval(null, false, $this->v('8.7.0'), false);

        $r = $a->intersect($b);
        $this->assertSame('8.7.0', (string) $r->upper);
        $this->assertFalse($r->upperInclusive);
    }

    public function testIntersectWithUnboundedIsIdentity(): void
    {
        $bounded = new Interval($this->v('8.1.0'), true, $this->v('9.0.0'), false);

        $r = Interval::unbounded()->intersect($bounded);
        $this->assertSame('8.1.0', (string) $r->lower);
        $this->assertSame('9.0.0', (string) $r->upper);
        $this->assertTrue($r->lowerInclusive);
        $this->assertFalse($r->upperInclusive);
    }

    public function testIntersectCombinesLowerInclusivityOnEqualBounds(): void
    {
        $inclusive = new Interval($this->v('8.1.0'), true, null, false);
        $exclusive = new Interval($this->v('8.1.0'), false, null, false);

        $r = $inclusive->intersect($exclusive);
        $this->assertSame('8.1.0', (string) $r->lower);
        // Tighter of inclusive and exclusive at the same point is exclusive.
        $this->assertFalse($r->lowerInclusive);
    }

    public function testIntersectCombinesUpperInclusivityOnEqualBounds(): void
    {
        $inclusive = new Interval(null, false, $this->v('8.6.0'), true);
        $exclusive = new Interval(null, false, $this->v('8.6.0'), false);

        $r = $inclusive->intersect($exclusive);
        $this->assertSame('8.6.0', (string) $r->upper);
        $this->assertFalse($r->upperInclusive);
    }
}
