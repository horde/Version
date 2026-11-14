<?php

declare(strict_types=1);

namespace Horde\Version\Test\Unit;

use Horde\Version\ConstraintReconciler;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ConstraintReconciler::class)]
class ConstraintReconcilerTest extends TestCase
{
    private ConstraintReconciler $reconciler;

    protected function setUp(): void
    {
        $this->reconciler = new ConstraintReconciler();
    }

    public function testNoPolicyReturnsExistingUnchanged(): void
    {
        $this->assertSame('^8.1', $this->reconciler->reconcile('^8.1', null, false, null, false));
    }

    public function testFloorAlreadyAtMinIsKept(): void
    {
        $this->assertSame('^8.1', $this->reconciler->reconcile('^8.1', '^8.1', false, '^8.6', false));
    }

    public function testFloorRaisedToMin(): void
    {
        $this->assertSame('^8.1', $this->reconciler->reconcile('^8.0', '^8.1', false, '^8.6', false));
    }

    public function testStricterFloorIsTolerated(): void
    {
        $this->assertSame('^8.2', $this->reconciler->reconcile('^8.2', '^8.1', false, '^8.6', false));
    }

    public function testSyncMinPinsFloorEvenWhenStricter(): void
    {
        $this->assertSame('^8.1', $this->reconciler->reconcile('^8.2', '^8.1', true, '^8.6', false));
    }

    public function testSyncMaxKeepsLowerCeilingWhenCaretMaxIsLooser(): void
    {
        // max ^8.6 has ceiling 9.0.0.
        // Existing ^8.3 ceiling is also 9.0.0,
        // so syncMax pins to 9.0.0 and the result collapses to ^8.3.
        $this->assertSame('^8.3', $this->reconciler->reconcile('^8.3', '^8.1', false, '^8.6', true));
    }

    public function testStricterCeilingIsTolerated(): void
    {
        $this->assertSame(
            '>=8.1.0 <8.4.0',
            $this->reconciler->reconcile('>=8.1.0 <8.4.0', '^8.1', false, '^8.6', false)
        );
    }

    public function testEmptyExistingTakesPolicyFloor(): void
    {
        $this->assertSame('^8.2', $this->reconciler->reconcile('', '^8.2', false, '^8.6', false));
    }

    public function testOrExistingTreatedAsUnboundedAndRendersRange(): void
    {
        $this->assertSame(
            '>=8.2.0 <10.0.0',
            $this->reconciler->reconcile('^8.1 || ^9', '^8.2', false, '^9', false)
        );
    }

    public function testExplicitUpperBoundMaxCapsAtMinor(): void
    {
        // Max <=8.6 keeps PHP 8.7+ out.
        $this->assertSame(
            '>=8.1.0 <=8.6.0',
            $this->reconciler->reconcile('^8.1', '^8.1', false, '<=8.6', false)
        );
    }

    public function testCaretZeroMajorMinimalSpelling(): void
    {
        // Floor 8.0.0 & ceiling 9.0.0 renders as the minimal "^8".
        $this->assertSame('^8', $this->reconciler->reconcile('^8.0.0', '^8', false, null, false));
    }

    public function testMaxOnlyTightensCeiling(): void
    {
        // Existing >=8.1.0 <9.1.0 with max ^8.6 (ceiling 9.0.0) tightens to
        // 9.0.0 and collapses to ^8.1.
        $this->assertSame(
            '^8.1',
            $this->reconciler->reconcile('>=8.1.0 <9.1.0', null, false, '^8.6', false)
        );
    }

    public function testContradictoryPolicyThrows(): void
    {
        // Floor 9.0.0 above ceiling 8.7.0.
        $this->expectException(InvalidArgumentException::class);
        $this->reconciler->reconcile('', '^9', false, '<=8.6', false);
    }

    public function testInvalidMinThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->reconciler->reconcile('^8.1', 'not-a-version', false, null, false);
    }
}
