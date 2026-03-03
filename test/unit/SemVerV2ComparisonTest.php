<?php

declare(strict_types=1);

namespace Horde\Version\Test\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Horde\Version\SemVerV2Version;
use Horde\Version\SemVerV2Comparison;

#[CoversClass(SemVerV2Comparison::class)]
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
    public function testStableIsGreaterThanPreRelease(): void
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

    // Test build metadata is ignored per SemVer 2.0 spec
    public function testBuildMetadataIsIgnoredInComparison(): void
    {
        $this->assertEquals(0, (new SemVerV2Comparison())->compare(
            new SemVerV2Version('1.0.0+build1'),
            new SemVerV2Version('1.0.0+build2')
        ));
        $this->assertEquals(0, (new SemVerV2Comparison())->compare(
            new SemVerV2Version('1.0.0'),
            new SemVerV2Version('1.0.0+buildinfo')
        ));
        $this->assertEquals(0, (new SemVerV2Comparison())->compare(
            new SemVerV2Version('1.0.0-alpha+001'),
            new SemVerV2Version('1.0.0-alpha+999')
        ));
    }

    public function testBuildMetadataDoesNotAffectPrecedence(): void
    {
        $this->assertEquals(-1, (new SemVerV2Comparison())->compare(
            new SemVerV2Version('1.0.0+zzz'),
            new SemVerV2Version('1.0.1+aaa')
        ));
        $this->assertEquals(1, (new SemVerV2Comparison())->compare(
            new SemVerV2Version('1.0.0+build'),
            new SemVerV2Version('1.0.0-alpha+build')
        ));
    }

    // Test complex prerelease comparisons
    public function testComplexPrereleaseComparison(): void
    {
        // From SemVer 2.0 spec example
        $this->assertEquals(-1, (new SemVerV2Comparison())->compare(
            new SemVerV2Version('1.0.0-alpha'),
            new SemVerV2Version('1.0.0-alpha.1')
        ));
        $this->assertEquals(-1, (new SemVerV2Comparison())->compare(
            new SemVerV2Version('1.0.0-alpha.1'),
            new SemVerV2Version('1.0.0-alpha.beta')
        ));
        $this->assertEquals(-1, (new SemVerV2Comparison())->compare(
            new SemVerV2Version('1.0.0-alpha.beta'),
            new SemVerV2Version('1.0.0-beta')
        ));
        $this->assertEquals(-1, (new SemVerV2Comparison())->compare(
            new SemVerV2Version('1.0.0-beta'),
            new SemVerV2Version('1.0.0-beta.2')
        ));
        $this->assertEquals(-1, (new SemVerV2Comparison())->compare(
            new SemVerV2Version('1.0.0-beta.2'),
            new SemVerV2Version('1.0.0-beta.11')
        ));
        $this->assertEquals(-1, (new SemVerV2Comparison())->compare(
            new SemVerV2Version('1.0.0-beta.11'),
            new SemVerV2Version('1.0.0-rc.1')
        ));
        $this->assertEquals(-1, (new SemVerV2Comparison())->compare(
            new SemVerV2Version('1.0.0-rc.1'),
            new SemVerV2Version('1.0.0')
        ));
    }

    public function testMixedNumericAndAlphaPrerelease(): void
    {
        // Mixed numeric and alphanumeric identifiers
        $this->assertEquals(-1, (new SemVerV2Comparison())->compare(
            new SemVerV2Version('1.0.0-1.alpha'),
            new SemVerV2Version('1.0.0-1.beta')
        ));
        $this->assertEquals(-1, (new SemVerV2Comparison())->compare(
            new SemVerV2Version('1.0.0-alpha.1'),
            new SemVerV2Version('1.0.0-alpha.2')
        ));
    }

    public function testPrereleaseWithHyphens(): void
    {
        // Hyphens are allowed in prerelease identifiers
        $this->assertEquals(-1, (new SemVerV2Comparison())->compare(
            new SemVerV2Version('1.0.0-alpha-test'),
            new SemVerV2Version('1.0.0-alpha-test.1')
        ));
        $this->assertEquals(0, (new SemVerV2Comparison())->compare(
            new SemVerV2Version('1.0.0-x-y-z'),
            new SemVerV2Version('1.0.0-x-y-z')
        ));
    }

    public function testEmptyPrereleaseIdentifiersHandling(): void
    {
        // Test versions with similar but different prerelease structures
        $this->assertEquals(-1, (new SemVerV2Comparison())->compare(
            new SemVerV2Version('1.0.0-a'),
            new SemVerV2Version('1.0.0-b')
        ));
        $this->assertEquals(-1, (new SemVerV2Comparison())->compare(
            new SemVerV2Version('1.0.0-1'),
            new SemVerV2Version('1.0.0-2')
        ));
    }

    public function testLargeNumbers(): void
    {
        // Test with large version numbers
        $this->assertEquals(-1, (new SemVerV2Comparison())->compare(
            new SemVerV2Version('999.999.999'),
            new SemVerV2Version('1000.0.0')
        ));
        $this->assertEquals(-1, (new SemVerV2Comparison())->compare(
            new SemVerV2Version('1.0.0-99999'),
            new SemVerV2Version('1.0.0-100000')
        ));
    }

    public function testZeroVersions(): void
    {
        $this->assertEquals(0, (new SemVerV2Comparison())->compare(
            new SemVerV2Version('0.0.0'),
            new SemVerV2Version('0.0.0')
        ));
        $this->assertEquals(-1, (new SemVerV2Comparison())->compare(
            new SemVerV2Version('0.0.0'),
            new SemVerV2Version('0.0.1')
        ));
        $this->assertEquals(-1, (new SemVerV2Comparison())->compare(
            new SemVerV2Version('0.0.1'),
            new SemVerV2Version('0.1.0')
        ));
    }
}
