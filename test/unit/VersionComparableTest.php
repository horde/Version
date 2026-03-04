<?php

declare(strict_types=1);

namespace Horde\Version\Test\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Horde\Version\VersionComparable;
use Horde\Version\SemVerV2Version;
use Horde\Version\RelaxedSemanticVersion;

#[CoversClass(VersionComparable::class)]
class VersionComparableTest extends TestCase
{
    public function testCompareMethod(): void
    {
        $v1 = new SemVerV2Version('1.0.0');
        $v2 = new SemVerV2Version('2.0.0');

        $this->assertEquals(-1, $v1->compare($v2));
        $this->assertEquals(1, $v2->compare($v1));
        $this->assertEquals(0, $v1->compare($v1));
    }

    public function testIsGreaterThan(): void
    {
        $v1 = new SemVerV2Version('2.0.0');
        $v2 = new SemVerV2Version('1.0.0');

        $this->assertTrue($v1->isGreaterThan($v2));
        $this->assertFalse($v2->isGreaterThan($v1));
        $this->assertFalse($v1->isGreaterThan($v1));
    }

    public function testIsLessThan(): void
    {
        $v1 = new SemVerV2Version('1.0.0');
        $v2 = new SemVerV2Version('2.0.0');

        $this->assertTrue($v1->isLessThan($v2));
        $this->assertFalse($v2->isLessThan($v1));
        $this->assertFalse($v1->isLessThan($v1));
    }

    public function testEquals(): void
    {
        $v1 = new SemVerV2Version('1.0.0');
        $v2 = new SemVerV2Version('1.0.0');
        $v3 = new SemVerV2Version('1.0.1');

        $this->assertTrue($v1->equals($v2));
        $this->assertFalse($v1->equals($v3));
    }

    public function testWorksWithRelaxedSemanticVersion(): void
    {
        $v1 = new RelaxedSemanticVersion('v1.0.0');
        $v2 = new RelaxedSemanticVersion('v2.0.0');

        $this->assertTrue($v1->isLessThan($v2));
        $this->assertTrue($v2->isGreaterThan($v1));
    }

    public function testCompareDifferentTypes(): void
    {
        $v1 = new SemVerV2Version('1.0.0');
        $v2 = new RelaxedSemanticVersion('2.0.0');

        $this->assertTrue($v1->isLessThan($v2));
        $this->assertTrue($v2->isGreaterThan($v1));
    }

    public function testChainedComparisons(): void
    {
        $v1 = new SemVerV2Version('1.0.0');
        $v2 = new SemVerV2Version('2.0.0');
        $v3 = new SemVerV2Version('3.0.0');

        $this->assertTrue($v1->isLessThan($v2) && $v2->isLessThan($v3));
        $this->assertTrue($v3->isGreaterThan($v2) && $v2->isGreaterThan($v1));
    }

    public function testCompareWithBuildMetadata(): void
    {
        $v1 = new SemVerV2Version('1.0.0+build1');
        $v2 = new SemVerV2Version('1.0.0+build2');

        // Build metadata should be ignored
        $this->assertTrue($v1->equals($v2));
    }

    public function testCompareWithPrerelease(): void
    {
        $stable = new SemVerV2Version('1.0.0');
        $alpha = new SemVerV2Version('1.0.0-alpha');
        $beta = new SemVerV2Version('1.0.0-beta');

        $this->assertTrue($alpha->isLessThan($beta));
        $this->assertTrue($beta->isLessThan($stable));
        $this->assertTrue($alpha->isLessThan($stable));
    }
}
