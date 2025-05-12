<?php

declare(strict_types=1);

namespace Horde\Version\Test\Unit;

use PHPUnit\Framework\TestCase;
use Stringable;
use PHPUnit\Framework\Attributes\CoversNothing;
use Horde\Version\SemVerV2Version;
use Horde\Version\RelaxedSemanticVersion;
use Horde\Version\Stability;

#[CoversNothing]
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
}
