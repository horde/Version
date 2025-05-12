<?php

declare(strict_types=1);

namespace Horde\Version\Test\Unit;

use PHPUnit\Framework\TestCase;
use Stringable;
use PHPUnit\Framework\Attributes\CoversNothing;
use Horde\Version\RelaxedSemanticVersion;
use Horde\Version\SemVerV2Version;

#[CoversNothing]
class RelaxedSemanticVersionTest extends TestCase
{
    public function testBasicConstruction(): void
    {
        $version = new RelaxedSemanticVersion('1.0.0');
        $this->assertInstanceOf(RelaxedSemanticVersion::class, $version);
        $this->assertEquals('1.0.0', (string) $version);

        $version = new RelaxedSemanticVersion('1.0.0-alpha');
        $this->assertInstanceOf(RelaxedSemanticVersion::class, $version);
        $this->assertEquals('1.0.0-alpha', (string) $version);
    }

    public function testInvalidVersion(): void
    {
        $this->expectException(\Horde\Version\InvalidVersionException::class);
        $this->expectExceptionMessage('Version did not parse to expected format');
        new RelaxedSemanticVersion('invalid-version');
    }
    public function testPrefixedVersionIsValidVersion(): void
    {
        $version = new RelaxedSemanticVersion('v1.0.0');
        $this->assertInstanceOf(RelaxedSemanticVersion::class, $version);
        $this->assertEquals('v', $version->prefix);
    }
    public function testPrereleaseVersionWithoutHyphenIsValidVersion(): void
    {
        $version = new RelaxedSemanticVersion('v1.0.0alpha1');
        $this->assertEquals('alpha1', $version->preRelease);
        $this->assertTrue($version->isPreRelease);
        $this->assertEquals('', $version->buildInfo);
        $this->assertFalse($version->hasBuildInfo);
    }

    public function testVersionWithoutPatchLevel(): void
    {
        $version = new RelaxedSemanticVersion('1.0');
        $this->assertEquals('0', $version->patch);
        $this->assertFalse($version->isPreRelease);
        $this->assertEquals('', $version->preRelease);
        $this->assertEquals('', $version->buildInfo);
        $this->assertFalse($version->hasBuildInfo);
    }

    public function testPrereleaseVersionWithoutPatchLevel(): void
    {
        $version = new RelaxedSemanticVersion('v1.0alpha1');
        $this->assertEquals('v', $version->prefix);
        $this->assertEquals('0', $version->patch);
        $this->assertTrue($version->isPreRelease);
        $this->assertEquals('alpha1', $version->preRelease);
        $this->assertEquals('', $version->buildInfo);
        $this->assertFalse($version->hasBuildInfo);
    }

    public function testFormattingToSemverV2YieldsValidSemver(): void
    {
        $version = new RelaxedSemanticVersion('v1.0.0alpha.1+foobar');
        $this->assertEquals('1.0.0-alpha.1+foobar', $version->formatSemVerV2());
        $this->assertInstanceOf(SemVerV2Version::class, new SemVerV2Version($version->formatSemVerV2()));
    }
}
