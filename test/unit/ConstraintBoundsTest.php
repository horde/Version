<?php

declare(strict_types=1);

namespace Horde\Version\Test\Unit;

use Horde\Version\ConstraintBounds;
use Horde\Version\ConstraintParser;
use Horde\Version\Constraint\Interval;
use Horde\Version\NotIntervalableException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ConstraintBounds::class)]
#[CoversClass(Interval::class)]
class ConstraintBoundsTest extends TestCase
{
    private ConstraintParser $parser;
    private ConstraintBounds $bounds;

    protected function setUp(): void
    {
        $this->parser = new ConstraintParser();
        $this->bounds = new ConstraintBounds();
    }

    private function intervalOf(string $constraint): Interval
    {
        return $this->bounds->of($this->parser->parse($constraint));
    }

    /**
     * @param array{0: ?string, 1: bool, 2: ?string, 3: bool} $expected
     *        [lower, lowerInclusive, upper, upperInclusive or null]
     *   null bound means unbounded.
     */
    private function assertInterval(array $expected, Interval $i): void
    {
        [$lower, $lowerInc, $upper, $upperInc] = $expected;
        $this->assertSame($lower, $i->lower === null ? null : (string) $i->lower, 'lower');
        $this->assertSame($lowerInc, $i->lowerInclusive, 'lowerInclusive');
        $this->assertSame($upper, $i->upper === null ? null : (string) $i->upper, 'upper');
        $this->assertSame($upperInc, $i->upperInclusive, 'upperInclusive');
    }

    public function testExactIsAClosedPoint(): void
    {
        $this->assertInterval(['1.0.0', true, '1.0.0', true], $this->intervalOf('1.0.0'));
    }

    public function testGreaterOrEqualIsInclusiveRayUp(): void
    {
        $this->assertInterval(['8.1.0', true, null, false], $this->intervalOf('>=8.1'));
    }

    public function testGreaterThanIsExclusiveRayUp(): void
    {
        $this->assertInterval(['8.1.0', false, null, false], $this->intervalOf('>8.1'));
    }

    public function testLessThanIsExclusiveRayDown(): void
    {
        $this->assertInterval([null, false, '8.7.0', false], $this->intervalOf('<8.7'));
    }

    public function testLessOrEqualIsInclusiveRayDown(): void
    {
        $this->assertInterval([null, false, '8.6.0', true], $this->intervalOf('<=8.6'));
    }

    public function testCaretMajorAnchored(): void
    {
        $this->assertInterval(['8.1.0', true, '9.0.0', false], $this->intervalOf('^8.1'));
    }

    public function testCaretZeroMajor(): void
    {
        $this->assertInterval(['0.2.3', true, '0.3.0', false], $this->intervalOf('^0.2.3'));
    }

    public function testCaretZeroMajorZeroMinor(): void
    {
        $this->assertInterval(['0.0.3', true, '0.0.4', false], $this->intervalOf('^0.0.3'));
    }

    public function testTildeWithPatch(): void
    {
        $this->assertInterval(['1.2.3', true, '1.3.0', false], $this->intervalOf('~1.2.3'));
    }

    public function testTildeWithoutPatch(): void
    {
        $this->assertInterval(['1.2.0', true, '2.0.0', false], $this->intervalOf('~1.2'));
    }

    public function testWildcardMinor(): void
    {
        $this->assertInterval(['1.0.0', true, '1.1.0', false], $this->intervalOf('1.0.*'));
    }

    public function testWildcardMajor(): void
    {
        $this->assertInterval(['1.0.0', true, '2.0.0', false], $this->intervalOf('1.*'));
    }

    public function testRangeIsClosedBothEnds(): void
    {
        $this->assertInterval(['1.0.0', true, '2.0.0', true], $this->intervalOf('1.0.0 - 2.0.0'));
    }

    public function testAndCompositeIntersects(): void
    {
        $this->assertInterval(['8.2.0', true, '8.7.0', false], $this->intervalOf('>=8.2 <8.7'));
    }

    public function testNotEqualIsNotIntervalable(): void
    {
        $this->expectException(NotIntervalableException::class);
        $this->bounds->of($this->parser->parse('!=1.5.0'));
    }

    public function testOrCompositeIsNotIntervalable(): void
    {
        $this->expectException(NotIntervalableException::class);
        $this->bounds->of($this->parser->parse('^1.0 || ^2.0'));
    }

    public function testTryOfReturnsNullForNotEqual(): void
    {
        $this->assertNull($this->bounds->tryOf($this->parser->parse('!=1.5.0')));
    }

    public function testTryOfReturnsNullForOr(): void
    {
        $this->assertNull($this->bounds->tryOf($this->parser->parse('^1.0 || ^2.0')));
    }

    public function testTryOfReturnsIntervalForBoundedConstraint(): void
    {
        $this->assertInstanceOf(Interval::class, $this->bounds->tryOf($this->parser->parse('^8.1')));
    }
}
