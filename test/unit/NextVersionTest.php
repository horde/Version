<?php

declare(strict_types=1);

namespace Horde\Version\Test\Unit;

use PHPUnit\Framework\TestCase;
use Stringable;
use PHPUnit\Framework\Attributes\CoversClass;
use Horde\Version\SemVerV2Version;
use Horde\Version\RelaxedSemanticVersion;
use Horde\Version\Stability;
use Horde\Version\NextVersion;

#[CoversClass(NextVersion::class)]
class NextVersionTest extends TestCase
{
    public function testBasicConstruction(): void
    {
        $version = new NextVersion('1.0.0');
        $this->assertInstanceOf(NextVersion::class, $version);
    }

    public function testStableNextPatch(): void
    {
        $version = new NextVersion('1.0.0');
        $nextVersion = $version('patch', 'stable');
        $this->assertEquals('1.0.1', (string) $nextVersion);
    }
    public function testStableNextMinor(): void
    {
        $version = new NextVersion('1.0.0');
        $nextVersion = $version('minor', 'stable');
        $this->assertEquals('1.1.0', (string) $nextVersion);
    }
    public function testStableNextMajor(): void
    {
        $version = new NextVersion('1.0.0');
        $nextVersion = $version('major', 'stable');
        $this->assertEquals('2.0.0', (string) $nextVersion);
    }

    public function testZeroMajorToStable(): void
    {
        $version = new NextVersion('0.1.0');
        $nextVersion = $version('patch', 'stable');
        $this->assertEquals('1.0.0', (string) $nextVersion);
    }

    public function testAlphaToStable(): void
    {
        $version = new NextVersion('1.0.0-alpha');
        $nextVersion = $version('patch', 'stable');
        $this->assertEquals('1.0.0', (string) $nextVersion);
        $version = new NextVersion('1.0.0-alpha');
        $nextVersion = $version('minor', 'stable');
        $this->assertEquals('1.0.0', (string) $nextVersion);
        $version = new NextVersion('1.0.0-alpha');
        $nextVersion = $version('major', 'stable');
        $this->assertEquals('1.0.0', (string) $nextVersion);
    }

    public function testDowngradeToAlpha(): void
    {
        $version = new NextVersion('1.0.0');
        $nextVersion = $version('patch', 'alpha');
        $this->assertEquals('1.0.1-alpha.1', (string) $nextVersion);
        $version = new NextVersion('1.0.0');
        $nextVersion = $version('minor', 'alpha');
        $this->assertEquals('1.1.0-alpha.1', (string) $nextVersion);
        $version = new NextVersion('1.0.0');
        $nextVersion = $version('major', 'alpha');
        $this->assertEquals('2.0.0-alpha.1', (string) $nextVersion);
    }

    // Test beta stability transitions
    public function testBetaToStable(): void
    {
        $version = new NextVersion('1.0.0-beta.1');
        $nextVersion = $version('patch', 'stable');
        $this->assertEquals('1.0.0', (string) $nextVersion);
    }

    public function testDowngradeToBeta(): void
    {
        $version = new NextVersion('1.0.0');
        $nextVersion = $version('patch', 'beta');
        $this->assertEquals('1.0.1-beta.1', (string) $nextVersion);
        $version = new NextVersion('1.0.0');
        $nextVersion = $version('minor', 'beta');
        $this->assertEquals('1.1.0-beta.1', (string) $nextVersion);
    }

    public function testAlphaToBeta(): void
    {
        $version = new NextVersion('1.0.0-alpha.2');
        $nextVersion = $version('patch', 'beta');
        $this->assertEquals('1.0.0-beta.1', (string) $nextVersion);
    }

    // Test RC stability transitions
    public function testRcToStable(): void
    {
        $version = new NextVersion('1.0.0-rc.1');
        $nextVersion = $version('patch', 'stable');
        $this->assertEquals('1.0.0', (string) $nextVersion);
    }

    public function testDowngradeToRc(): void
    {
        $version = new NextVersion('1.0.0');
        $nextVersion = $version('patch', 'rc');
        $this->assertEquals('1.0.1-rc.1', (string) $nextVersion);
    }

    public function testBetaToRc(): void
    {
        $version = new NextVersion('1.0.0-beta.3');
        $nextVersion = $version('patch', 'rc');
        $this->assertEquals('1.0.0-rc.1', (string) $nextVersion);
    }

    // Test dev stability transitions
    public function testDevToStable(): void
    {
        $version = new NextVersion('1.0.0-dev');
        $nextVersion = $version('patch', 'stable');
        $this->assertEquals('1.0.0', (string) $nextVersion);
    }

    public function testDowngradeToDev(): void
    {
        $version = new NextVersion('1.0.0');
        $nextVersion = $version('patch', 'dev');
        $this->assertEquals('1.0.1-dev.1', (string) $nextVersion);
    }

    public function testDevToAlpha(): void
    {
        $version = new NextVersion('1.0.0-dev');
        $nextVersion = $version('patch', 'alpha');
        $this->assertEquals('1.0.0-alpha.1', (string) $nextVersion);
    }

    // Test 'unchanged' stability parameter
    public function testUnchangedStabilityStable(): void
    {
        $version = new NextVersion('1.0.0');
        $nextVersion = $version('patch', 'unchanged');
        $this->assertEquals('1.0.1', (string) $nextVersion);
    }

    public function testUnchangedStabilityAlpha(): void
    {
        $version = new NextVersion('1.0.0-alpha.1');
        $nextVersion = $version('patch', 'unchanged');
        $this->assertEquals('1.0.0-alpha.2', (string) $nextVersion);
    }

    public function testUnchangedStabilityBeta(): void
    {
        $version = new NextVersion('1.0.0-beta.2');
        $nextVersion = $version('patch', 'unchanged');
        $this->assertEquals('1.0.0-beta.3', (string) $nextVersion);
    }

    public function testUnchangedStabilityRc(): void
    {
        $version = new NextVersion('1.0.0-rc.1');
        $nextVersion = $version('patch', 'unchanged');
        $this->assertEquals('1.0.0-rc.2', (string) $nextVersion);
    }

    // Test same-stability prerelease increments
    public function testSameStabilityAlphaIncrement(): void
    {
        $version = new NextVersion('1.0.0-alpha.1');
        $nextVersion = $version('patch', 'alpha');
        $this->assertEquals('1.0.0-alpha.2', (string) $nextVersion);
    }

    public function testSameStabilityBetaIncrement(): void
    {
        $version = new NextVersion('1.0.0-beta.5');
        $nextVersion = $version('patch', 'beta');
        $this->assertEquals('1.0.0-beta.6', (string) $nextVersion);
    }

    public function testSameStabilityRcIncrement(): void
    {
        $version = new NextVersion('1.0.0-rc.2');
        $nextVersion = $version('patch', 'rc');
        $this->assertEquals('1.0.0-rc.3', (string) $nextVersion);
    }

    // Test zero major version edge cases
    public function testZeroMajorMinorIncrement(): void
    {
        $version = new NextVersion('0.1.0');
        $nextVersion = $version('minor', 'unchanged');
        $this->assertEquals('0.2.0', (string) $nextVersion);
    }

    public function testZeroMajorPatchIncrement(): void
    {
        $version = new NextVersion('0.1.0');
        $nextVersion = $version('patch', 'unchanged');
        $this->assertEquals('0.1.1', (string) $nextVersion);
    }

    public function testZeroMajorMajorIncrement(): void
    {
        $version = new NextVersion('0.9.5');
        $nextVersion = $version('major', 'unchanged');
        // For 0.x versions, major bump increments minor and resets patch
        $this->assertEquals('0.10.0', (string) $nextVersion);
    }

    public function testZeroMajorAlphaToStable(): void
    {
        $version = new NextVersion('0.1.0-alpha.1');
        $nextVersion = $version('patch', 'stable');
        $this->assertEquals('1.0.0', (string) $nextVersion);
    }

    // Test Stringable input
    public function testStringableInput(): void
    {
        $stringableVersion = new class ('1.0.0') implements Stringable {
            public function __construct(private string $version) {}
            public function __toString(): string { return $this->version; }
        };

        $version = new NextVersion($stringableVersion);
        $nextVersion = $version('patch', 'stable');
        $this->assertEquals('1.0.1', (string) $nextVersion);
    }

    // Test prefix preservation
    public function testPrefixPreservedInStableIncrement(): void
    {
        $version = new NextVersion('v1.0.0');
        $nextVersion = $version('patch', 'stable');
        $this->assertEquals('v1.0.1', (string) $nextVersion);
    }

    public function testPrefixPreservedInDowngrade(): void
    {
        $version = new NextVersion('v1.0.0');
        $nextVersion = $version('minor', 'alpha');
        $this->assertEquals('v1.1.0-alpha.1', (string) $nextVersion);
    }

    public function testPrefixPreservedInUpgrade(): void
    {
        $version = new NextVersion('v1.0.0-beta.1');
        $nextVersion = $version('patch', 'stable');
        $this->assertEquals('v1.0.0', (string) $nextVersion);
    }

    // Test complex version transitions
    public function testMajorVersionBumpResetsMinorAndPatch(): void
    {
        $version = new NextVersion('1.5.3');
        $nextVersion = $version('major', 'stable');
        $this->assertEquals('2.0.0', (string) $nextVersion);
    }

    public function testMinorVersionBumpResetsPatch(): void
    {
        $version = new NextVersion('1.5.3');
        $nextVersion = $version('minor', 'stable');
        $this->assertEquals('1.6.0', (string) $nextVersion);
    }
}
