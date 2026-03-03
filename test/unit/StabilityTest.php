<?php

declare(strict_types=1);

namespace Horde\Version\Test\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Horde\Version\SemVerV2Version;
use Horde\Version\RelaxedSemanticVersion;
use Horde\Version\Stability;
use LogicException;

#[CoversClass(Stability::class)]
class StabilityTest extends TestCase
{
    public function testZeroVersionIsNotStable(): void
    {
        $version = new SemVerV2Version('0.1.0');
        $stability = new Stability($version);
        $this->assertFalse($stability->isStable(), 'Version: ' . $version . ' is not stable');
        $this->assertFalse($stability->isAlpha());
        $this->assertFalse($stability->isBeta());
        $this->assertFalse($stability->isReleaseCandidate());
        $this->assertTrue($stability->isDevelopment());
    }

    public function testRecognizeAlpha(): void
    {
        $testSemVers = ['1.0.0-alpha1', '2.0.0-alpha', '1.2.3-alpha.5' ];
        $testRelaxedSemVers = ['v1.0.0alpha', 'v2.0.0-alpha', '1.2-alpha+any' ];
        $versions = [];
        foreach ($testSemVers as $versionString) {
            $versions[] = new SemVerV2Version($versionString);
        }
        foreach ($testRelaxedSemVers as $versionString) {
            $versions[] = new RelaxedSemanticVersion($versionString);
        }
        foreach ($versions as $version) {
            $stability = new Stability($version);
            $this->assertFalse($stability->isStable(), 'Version: ' . $version);
            $this->assertTrue($stability->isAlpha());
            $this->assertFalse($stability->isBeta());
            $this->assertFalse($stability->isReleaseCandidate());
            $this->assertFalse($stability->isDevelopment());
        }
    }

    public function testRecognizeBeta(): void
    {
        $testVersions = ['1.0.0-beta', '1.0.0-beta1', '1.0.0-beta.2', 'v1.0.0beta3'];
        foreach ($testVersions as $versionString) {
            $version = new RelaxedSemanticVersion($versionString);
            $stability = new Stability($version);
            $this->assertFalse($stability->isStable(), 'Version: ' . $version);
            $this->assertFalse($stability->isAlpha());
            $this->assertTrue($stability->isBeta());
            $this->assertFalse($stability->isReleaseCandidate());
            $this->assertFalse($stability->isDevelopment());
        }
    }

    public function testRecognizeReleaseCandidate(): void
    {
        $testVersions = ['1.0.0-rc', '1.0.0-rc1', '1.0.0-rc.2', 'v1.0.0rc3'];
        foreach ($testVersions as $versionString) {
            $version = new RelaxedSemanticVersion($versionString);
            $stability = new Stability($version);
            $this->assertFalse($stability->isStable(), 'Version: ' . $version);
            $this->assertFalse($stability->isAlpha());
            $this->assertFalse($stability->isBeta());
            $this->assertTrue($stability->isReleaseCandidate());
            $this->assertFalse($stability->isDevelopment());
        }
    }

    public function testRecognizeDevelopment(): void
    {
        $testVersions = ['0.1.0', '1.0.0-dev', '1.0.0-dev.1', '1.0.0-snapshot'];
        foreach ($testVersions as $versionString) {
            $version = new RelaxedSemanticVersion($versionString);
            $stability = new Stability($version);
            $this->assertFalse($stability->isStable(), 'Version: ' . $version);
            $this->assertTrue($stability->isDevelopment());
        }
    }

    public function testRecognizeStable(): void
    {
        $testVersions = ['1.0.0', '2.5.3', '10.20.30'];
        foreach ($testVersions as $versionString) {
            $version = new SemVerV2Version($versionString);
            $stability = new Stability($version);
            $this->assertTrue($stability->isStable(), 'Version: ' . $version);
            $this->assertFalse($stability->isAlpha());
            $this->assertFalse($stability->isBeta());
            $this->assertFalse($stability->isReleaseCandidate());
            $this->assertFalse($stability->isDevelopment());
        }
    }

    public function testStabilityPropertyIsSet(): void
    {
        $stable = new Stability(new SemVerV2Version('1.0.0'));
        $this->assertEquals('stable', $stable->stability);

        $alpha = new Stability(new SemVerV2Version('1.0.0-alpha'));
        $this->assertEquals('alpha', $alpha->stability);

        $beta = new Stability(new SemVerV2Version('1.0.0-beta'));
        $this->assertEquals('beta', $beta->stability);

        $rc = new Stability(new SemVerV2Version('1.0.0-rc'));
        $this->assertEquals('rc', $rc->stability);

        $dev = new Stability(new SemVerV2Version('0.1.0'));
        $this->assertEquals('dev', $dev->stability);
    }

    public function testStabilityRankPropertyIsSet(): void
    {
        $stable = new Stability(new SemVerV2Version('1.0.0'));
        $this->assertEquals(1000, $stable->stabilityRank);

        $alpha = new Stability(new SemVerV2Version('1.0.0-alpha'));
        $this->assertEquals(1, $alpha->stabilityRank);

        $beta = new Stability(new SemVerV2Version('1.0.0-beta'));
        $this->assertEquals(2, $beta->stabilityRank);

        $rc = new Stability(new SemVerV2Version('1.0.0-rc'));
        $this->assertEquals(3, $rc->stabilityRank);

        $dev = new Stability(new SemVerV2Version('0.1.0'));
        $this->assertEquals(0, $dev->stabilityRank);
    }

    public function testGetStabilityRankStatic(): void
    {
        $this->assertEquals(0, Stability::getStabilityRank('dev'));
        $this->assertEquals(1, Stability::getStabilityRank('alpha'));
        $this->assertEquals(2, Stability::getStabilityRank('beta'));
        $this->assertEquals(3, Stability::getStabilityRank('rc'));
        $this->assertEquals(1000, Stability::getStabilityRank('stable'));
    }

    public function testGetStabilityRankThrowsForInvalidStability(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Unknown stability: invalid');
        Stability::getStabilityRank('invalid');
    }

    public function testGetStabilityRevision(): void
    {
        $expected = [
            '1.0.0-alpha1' => 1,
            '2.0.0-alpha' => 1,
            '3.0.0' => 0,
            '1.2.3-alpha.5' => 5,
            '1.2-alpha+any' => 1,
            '1.2alpha6' => 6,
        ];
        foreach ($expected as $versionString => $revision) {
            $version = new RelaxedSemanticVersion($versionString);
            $stability = new Stability($version);
            $this->assertEquals($revision, $stability->getStabilityRevision(), 'Version: ' . $version);
        }
    }

    public function testGetStabilityRevisionForBeta(): void
    {
        $expected = [
            '1.0.0-beta' => 1,
            '1.0.0-beta1' => 1,
            '1.0.0-beta.2' => 2,
            '1.0beta5' => 5,
        ];
        foreach ($expected as $versionString => $revision) {
            $version = new RelaxedSemanticVersion($versionString);
            $stability = new Stability($version);
            $this->assertEquals($revision, $stability->getStabilityRevision(), 'Version: ' . $version);
        }
    }

    public function testGetStabilityRevisionForRc(): void
    {
        $expected = [
            '1.0.0-rc' => 1,
            '1.0.0-rc1' => 1,
            '1.0.0-rc.3' => 3,
            '1.0rc7' => 7,
        ];
        foreach ($expected as $versionString => $revision) {
            $version = new RelaxedSemanticVersion($versionString);
            $stability = new Stability($version);
            $this->assertEquals($revision, $stability->getStabilityRevision(), 'Version: ' . $version);
        }
    }

    public function testGetStabilityRevisionForDev(): void
    {
        $expected = [
            '1.0.0-dev' => 1,
            '1.0.0-dev.2' => 2,
        ];
        foreach ($expected as $versionString => $revision) {
            $version = new RelaxedSemanticVersion($versionString);
            $stability = new Stability($version);
            $this->assertEquals($revision, $stability->getStabilityRevision(), 'Version: ' . $version);
        }
    }

    public function testGetStabilityRevisionWithMultipleNonNumericParts(): void
    {
        // Test case where we have multiple parts but second part is not numeric
        $version = new RelaxedSemanticVersion('1.0.0-alpha.beta');
        $stability = new Stability($version);
        // Should return 1 as fallback when format doesn't match expected patterns
        $this->assertEquals(1, $stability->getStabilityRevision());
    }

    public function testCaseInsensitivity(): void
    {
        // Stability detection should be case-insensitive
        $alphaUpper = new Stability(new RelaxedSemanticVersion('1.0.0-ALPHA'));
        $this->assertTrue($alphaUpper->isAlpha());
        $this->assertEquals('alpha', $alphaUpper->stability);

        $betaUpper = new Stability(new RelaxedSemanticVersion('1.0.0-BETA'));
        $this->assertTrue($betaUpper->isBeta());
        $this->assertEquals('beta', $betaUpper->stability);

        $rcUpper = new Stability(new RelaxedSemanticVersion('1.0.0-RC'));
        $this->assertTrue($rcUpper->isReleaseCandidate());
        $this->assertEquals('rc', $rcUpper->stability);
    }
}
