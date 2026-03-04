<?php

declare(strict_types=1);

namespace Horde\Version\Test\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Horde\Version\RelaxedSemanticVersion;
use Horde\Version\SemVerV2Version;
use Horde\Version\InvalidVersionException;

#[CoversClass(RelaxedSemanticVersion::class)]
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
        $this->expectException(InvalidVersionException::class);
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
        $this->assertEquals(0, $version->patch);
        $this->assertFalse($version->isPreRelease);
        $this->assertEquals('', $version->preRelease);
        $this->assertEquals('', $version->buildInfo);
        $this->assertFalse($version->hasBuildInfo);
    }

    public function testPrereleaseVersionWithoutPatchLevel(): void
    {
        $version = new RelaxedSemanticVersion('v1.0alpha1');
        $this->assertEquals('v', $version->prefix);
        $this->assertEquals(0, $version->patch);
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

    // Test prefix variations
    public function testVariousPrefixes(): void
    {
        $prefixes = ['v', 'V', 'ver', 'version'];
        foreach ($prefixes as $prefix) {
            $version = new RelaxedSemanticVersion($prefix . '1.0.0');
            $this->assertEquals($prefix, $version->prefix);
            $this->assertEquals(1, $version->major);
            $this->assertEquals(0, $version->minor);
            $this->assertEquals(0, $version->patch);
        }
    }

    // Test getPreReleaseParts
    public function testGetPreReleasePartsWithMultipleParts(): void
    {
        $version = new RelaxedSemanticVersion('1.0.0-alpha.1.beta');
        $parts = $version->getPreReleaseParts();
        $this->assertIsIterable($parts);
        $this->assertEquals(['alpha', '1', 'beta'], iterator_to_array($parts));
    }

    public function testGetPreReleasePartsWhenNoPrerelease(): void
    {
        $version = new RelaxedSemanticVersion('1.0.0');
        $parts = $version->getPreReleaseParts();
        $this->assertEquals([], iterator_to_array($parts));
    }

    public function testGetPreReleasePartsWithNoPatchAndNoPrerelease(): void
    {
        $version = new RelaxedSemanticVersion('1.0');
        $parts = $version->getPreReleaseParts();
        $this->assertEquals([], iterator_to_array($parts));
    }

    // Test build metadata in relaxed format
    public function testBuildMetadataWithPrefix(): void
    {
        $version = new RelaxedSemanticVersion('v1.0.0+build123');
        $this->assertEquals('v', $version->prefix);
        $this->assertEquals('build123', $version->buildInfo);
        $this->assertTrue($version->hasBuildInfo);
        $this->assertFalse($version->isPreRelease);
    }

    public function testBuildMetadataWithoutPatch(): void
    {
        $version = new RelaxedSemanticVersion('1.0+buildinfo');
        $this->assertEquals(0, $version->patch);
        $this->assertEquals('buildinfo', $version->buildInfo);
        $this->assertTrue($version->hasBuildInfo);
    }

    public function testBuildMetadataWithPrereleaseNoHyphen(): void
    {
        $version = new RelaxedSemanticVersion('v1.0.0alpha1+build');
        $this->assertEquals('v', $version->prefix);
        $this->assertTrue($version->isPreRelease);
        $this->assertEquals('alpha1', $version->preRelease);
        $this->assertTrue($version->hasBuildInfo);
        $this->assertEquals('build', $version->buildInfo);
    }

    // Test complex prerelease patterns
    public function testComplexPrereleaseWithDots(): void
    {
        $version = new RelaxedSemanticVersion('1.0.0-alpha.beta.gamma.1.2.3');
        $this->assertTrue($version->isPreRelease);
        $parts = $version->getPreReleaseParts();
        $this->assertEquals(['alpha', 'beta', 'gamma', '1', '2', '3'], iterator_to_array($parts));
    }

    public function testPrereleaseWithHyphensNoSeparatorHyphen(): void
    {
        $version = new RelaxedSemanticVersion('1.0.0alpha-beta-gamma');
        $this->assertTrue($version->isPreRelease);
        $this->assertEquals('alpha-beta-gamma', $version->preRelease);
    }

    // Test formatSemVerV2
    public function testFormatSemVerV2RemovesPrefix(): void
    {
        $version = new RelaxedSemanticVersion('v1.2.3');
        $formatted = $version->formatSemVerV2();
        $this->assertEquals('1.2.3', $formatted);
        $this->assertStringNotContainsString('v', $formatted);
    }

    public function testFormatSemVerV2AddsHyphenToPrerelease(): void
    {
        $version = new RelaxedSemanticVersion('v1.0.0alpha1');
        $formatted = $version->formatSemVerV2();
        $this->assertEquals('1.0.0-alpha1', $formatted);
    }

    public function testFormatSemVerV2AddsPatchLevel(): void
    {
        $version = new RelaxedSemanticVersion('1.0');
        $formatted = $version->formatSemVerV2();
        $this->assertEquals('1.0.0', $formatted);
    }

    public function testFormatSemVerV2PreservesBuildMetadata(): void
    {
        $version = new RelaxedSemanticVersion('v1.0+build');
        $formatted = $version->formatSemVerV2();
        $this->assertEquals('1.0.0+build', $formatted);
    }

    public function testFormatSemVerV2CompleteConversion(): void
    {
        $version = new RelaxedSemanticVersion('v1.0alpha.1+build.123');
        $formatted = $version->formatSemVerV2();
        $this->assertEquals('1.0.0-alpha.1+build.123', $formatted);
    }

    // Test edge cases
    public function testEmptyPrefixIsHandled(): void
    {
        $version = new RelaxedSemanticVersion('1.0.0');
        $this->assertEquals('', $version->prefix);
    }

    public function testVersionStringPropertyPreservesOriginal(): void
    {
        $original = 'v1.0alpha1+build';
        $version = new RelaxedSemanticVersion($original);
        $this->assertEquals($original, $version->versionString);
        $this->assertEquals($original, (string) $version);
    }

    // Test property access
    public function testAllPropertiesAccessible(): void
    {
        $version = new RelaxedSemanticVersion('v2.5.13-beta.2+build.456');
        $this->assertEquals('v', $version->prefix);
        $this->assertEquals(2, $version->major);
        $this->assertEquals(5, $version->minor);
        $this->assertEquals(13, $version->patch);
        $this->assertTrue($version->isPreRelease);
        $this->assertEquals('beta.2', $version->preRelease);
        $this->assertTrue($version->hasBuildInfo);
        $this->assertEquals('build.456', $version->buildInfo);
    }

    // Test valid relaxed versions that would be invalid in strict SemVer
    public function testRelaxedVersionsNotInStrictSemVer(): void
    {
        $relaxedVersions = [
            'v1.0.0',           // Prefix
            '1.0',              // No patch
            'v1.0',             // Prefix + no patch
            '1.0.0alpha',       // No hyphen before prerelease
            'v1.0alpha1',       // Prefix + no patch + no hyphen
            'V1.0.0',           // Uppercase prefix
        ];

        foreach ($relaxedVersions as $versionString) {
            $version = new RelaxedSemanticVersion($versionString);
            $this->assertInstanceOf(RelaxedSemanticVersion::class, $version, "Failed to parse: $versionString");
        }
    }

    // Test that truly invalid versions still fail
    public function testInvalidVersionsStillRejected(): void
    {
        $invalidVersions = [
            'invalid',
            '1',
            'x.y.z',
            '1.x.0',
            '',
        ];

        foreach ($invalidVersions as $versionString) {
            try {
                new RelaxedSemanticVersion($versionString);
                $this->fail("Expected InvalidVersionException for: $versionString");
            } catch (InvalidVersionException $e) {
                $this->assertInstanceOf(InvalidVersionException::class, $e);
            }
        }
    }

    // Test numeric parsing
    public function testNumericValuesAreParsedCorrectly(): void
    {
        $version = new RelaxedSemanticVersion('10.20.30');
        $this->assertEquals(10, $version->major);
        $this->assertEquals(20, $version->minor);
        $this->assertEquals(30, $version->patch);
    }

    public function testZeroVersionsWork(): void
    {
        $version = new RelaxedSemanticVersion('0.0.0');
        $this->assertEquals(0, $version->major);
        $this->assertEquals(0, $version->minor);
        $this->assertEquals(0, $version->patch);
    }

    public function testZeroVersionWithoutPatch(): void
    {
        $version = new RelaxedSemanticVersion('0.1');
        $this->assertEquals(0, $version->major);
        $this->assertEquals(1, $version->minor);
        $this->assertEquals(0, $version->patch);
    }
}
