<?php

declare(strict_types=1);

namespace Horde\Version\Test\Unit;

use PHPUnit\Framework\TestCase;
use Stringable;
use PHPUnit\Framework\Attributes\CoversNothing;
use Horde\Version\SemVerV2Version;
use Horde\Version\SemVerV2Comparison;

#[CoversNothing]
class SemVerV2ComparisonTest extends TestCase
{
    public function testSemVerCoreEquals(): void
    {
        $this->assertEquals(0, (new SemVerV2Comparison())->compare(new SemVerV2Version('1.0.0'), new SemVerV2Version('1.0.0')));
        // Invoke Syntax
        $this->assertEquals(0, (new SemVerV2Comparison())(new SemVerV2Version('1.0.0'), new SemVerV2Version('1.0.0')));
    }
    public function testSemVerCoreLessThan(): void
    {
        $this->assertEquals(-1, (new SemVerV2Comparison())->compare(new SemVerV2Version('1.0.0'), new SemVerV2Version('1.0.1')));
        // Invoke Syntax
        $this->assertEquals(-1, (new SemVerV2Comparison())(new SemVerV2Version('1.0.0'), new SemVerV2Version('1.0.1')));
        $this->assertEquals(-1, (new SemVerV2Comparison())->compare(new SemVerV2Version('1.0.0'), new SemVerV2Version('1.1.0')));
        $this->assertEquals(-1, (new SemVerV2Comparison())->compare(new SemVerV2Version('1.1.0'), new SemVerV2Version('2.0.0')));
        $this->assertEquals(-1, (new SemVerV2Comparison())->compare(new SemVerV2Version('1.1.100'), new SemVerV2Version('2.0.0')));
    }
    public function testSemVerCoreGreaterThan(): void
    {
        $this->assertEquals(1, (new SemVerV2Comparison())->compare(new SemVerV2Version('1.0.1'), new SemVerV2Version('1.0.0')));
        // Invoke Syntax
        $this->assertEquals(1, (new SemVerV2Comparison())(new SemVerV2Version('1.0.1'), new SemVerV2Version('1.0.0')));
        $this->assertEquals(1, (new SemVerV2Comparison())->compare(new SemVerV2Version('1.1.0'), new SemVerV2Version('1.0.0')));
        $this->assertEquals(1, (new SemVerV2Comparison())->compare(new SemVerV2Version('2.0.0'), new SemVerV2Version('1.1.0')));
        $this->assertEquals(1, (new SemVerV2Comparison())->compare(new SemVerV2Version('2.0.0'), new SemVerV2Version('1.1.100')));
    }
    public function stableIsGreaterThanPreRelease(): void
    {
        $this->assertEquals(1, (new SemVerV2Comparison())->compare(new SemVerV2Version('1.0.0'), new SemVerV2Version('1.0.0-alpha')));
        // Invoke Syntax
        $this->assertEquals(1, (new SemVerV2Comparison())(new SemVerV2Version('1.0.0'), new SemVerV2Version('1.0.0-alpha')));
        $this->assertEquals(1, (new SemVerV2Comparison())->compare(new SemVerV2Version('1.0.0'), new SemVerV2Version('1.0.0-alpha.1')));
    }
    public function testBetaIsBetterThanAlpha(): void
    {
        $this->assertEquals(-1, (new SemVerV2Comparison())->compare(new SemVerV2Version('1.0.0-alpha'), new SemVerV2Version('1.0.0-beta')));
        // Invoke Syntax
        $this->assertEquals(-1, (new SemVerV2Comparison())(new SemVerV2Version('1.0.0-alpha'), new SemVerV2Version('1.0.0-beta')));
        $this->assertEquals(-1, (new SemVerV2Comparison())->compare(new SemVerV2Version('1.0.0-alpha'), new SemVerV2Version('1.0.0-beta.1')));
    }
    public function testNumbersAreInferior(): void
    {
        $this->assertEquals(1, (new SemVerV2Comparison())->compare(new SemVerV2Version('1.0.0-alpha'), new SemVerV2Version('1.0.0-20')));
        // Invoke Syntax
        $this->assertEquals(-1, (new SemVerV2Comparison())(new SemVerV2Version('1.0.0-2020204'), new SemVerV2Version('1.0.0-beta')));
    }
    public function testNumericComparison(): void
    {
        $this->assertEquals(-1, (new SemVerV2Comparison())->compare(new SemVerV2Version('1.0.0-31'), new SemVerV2Version('1.0.0-200')));
        // Invoke Syntax
        $this->assertEquals(1, (new SemVerV2Comparison())(new SemVerV2Version('1.0.0-100'), new SemVerV2Version('1.0.0-99')));
        $this->assertEquals(0, (new SemVerV2Comparison())(new SemVerV2Version('1.0.0-43'), new SemVerV2Version('1.0.0-43')));
    }
    public function testMorePartsIsGreater(): void
    {
        $this->assertEquals(-1, (new SemVerV2Comparison())->compare(new SemVerV2Version('1.0.0-alpha'), new SemVerV2Version('1.0.0-alpha.beta')));
        // Invoke Syntax
        $this->assertEquals(1, (new SemVerV2Comparison())(new SemVerV2Version('1.0.0-alpha.1'), new SemVerV2Version('1.0.0-alpha')));
    }
}
