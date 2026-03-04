<?php

declare(strict_types=1);

namespace Horde\Version\Test\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Horde\Version\SemVerV2Version;
use Horde\Version\InvalidVersionException;

#[CoversClass(SemVerV2Version::class)]
class SemVerV2VersionTest extends TestCase
{
    public function testBasicConstruction(): void
    {
        $version = new SemVerV2Version('1.0.0');
        $this->assertInstanceOf(SemVerV2Version::class, $version);
        $this->assertEquals('1.0.0', (string) $version);

        $version = new SemVerV2Version('1.0.0-alpha');
        $this->assertInstanceOf(SemVerV2Version::class, $version);
        $this->assertEquals('1.0.0-alpha', (string) $version);
    }

    public function testInvalidVersion(): void
    {
        $this->expectException(InvalidVersionException::class);
        $this->expectExceptionMessage('Version did not parse to expected format');
        new SemVerV2Version('invalid-version');
    }
    public function testPrefixedVersionIsInvalidVersion(): void
    {
        $this->expectException(InvalidVersionException::class);
        $this->expectExceptionMessage('Version did not parse to expected format');
        new SemVerV2Version('v1.0.0');
    }
    public function testPrereleaseVersionWithoutHyphenIsInvalidVersion(): void
    {
        $this->expectException(InvalidVersionException::class);
        $this->expectExceptionMessage('Version did not parse to expected format');
        new SemVerV2Version('v1.0.0alpha1');
    }
    public function testVersionWithPrereleaseAndBuildInfo(): void
    {
        $version = new SemVerV2Version('1.0.0-alpha.1+foobar');
        $this->assertEquals('1.0.0-alpha.1+foobar', (string) $version);
        $this->assertEquals('alpha.1', $version->preRelease);
        $this->assertTrue($version->isPreRelease);
        $this->assertEquals('foobar', $version->buildInfo);
        $this->assertTrue($version->hasBuildInfo);
    }

    public function testVersionWithUndottedPrerelease(): void
    {
        $version = new SemVerV2Version('1.0.0-alpha1');
        $this->assertEquals('1.0.0-alpha1', (string) $version);
        $this->assertEquals('alpha1', $version->preRelease);
        $this->assertTrue($version->isPreRelease);
        $this->assertEquals('', $version->buildInfo);
        $this->assertFalse($version->hasBuildInfo);
    }

    // Test leading zeros are rejected (SemVer 2.0 requirement)
    public function testLeadingZeroInMajorIsInvalid(): void
    {
        $this->expectException(InvalidVersionException::class);
        new SemVerV2Version('01.0.0');
    }

    public function testLeadingZeroInMinorIsInvalid(): void
    {
        $this->expectException(InvalidVersionException::class);
        new SemVerV2Version('1.01.0');
    }

    public function testLeadingZeroInPatchIsInvalid(): void
    {
        $this->expectException(InvalidVersionException::class);
        new SemVerV2Version('1.0.01');
    }

    public function testLeadingZeroInPrereleaseNumericIdentifier(): void
    {
        // Per SemVer 2.0: numeric identifiers MUST NOT include leading zeroes
        // However, alphanumeric identifiers CAN start with zero
        $this->expectException(InvalidVersionException::class);
        new SemVerV2Version('1.0.0-01');
    }

    public function testZeroWithoutLeadingZeroIsValid(): void
    {
        $version = new SemVerV2Version('0.0.0');
        $this->assertEquals(0, $version->major);
        $this->assertEquals(0, $version->minor);
        $this->assertEquals(0, $version->patch);
    }

    // Test build metadata
    public function testVersionWithOnlyBuildMetadata(): void
    {
        $version = new SemVerV2Version('1.0.0+build123');
        $this->assertEquals('1.0.0+build123', (string) $version);
        $this->assertFalse($version->isPreRelease);
        $this->assertEquals('', $version->preRelease);
        $this->assertTrue($version->hasBuildInfo);
        $this->assertEquals('build123', $version->buildInfo);
    }

    public function testBuildMetadataWithMultipleDots(): void
    {
        $version = new SemVerV2Version('1.0.0+build.123.abc');
        $this->assertEquals('build.123.abc', $version->buildInfo);
        $this->assertTrue($version->hasBuildInfo);
    }

    public function testBuildMetadataWithHyphens(): void
    {
        $version = new SemVerV2Version('1.0.0+build-123');
        $this->assertEquals('build-123', $version->buildInfo);
    }

    // Test getPreReleaseParts
    public function testGetPreReleasePartsWithMultipleParts(): void
    {
        $version = new SemVerV2Version('1.0.0-alpha.1.beta');
        $parts = $version->getPreReleaseParts();
        $this->assertIsIterable($parts);
        $this->assertEquals(['alpha', '1', 'beta'], iterator_to_array($parts));
    }

    public function testGetPreReleasePartsWithSinglePart(): void
    {
        $version = new SemVerV2Version('1.0.0-alpha');
        $parts = $version->getPreReleaseParts();
        $this->assertEquals(['alpha'], iterator_to_array($parts));
    }

    public function testGetPreReleasePartsWhenNoPrerelease(): void
    {
        $version = new SemVerV2Version('1.0.0');
        $parts = $version->getPreReleaseParts();
        $this->assertEquals([], iterator_to_array($parts));
    }

    public function testGetPreReleasePartsWithOnlyBuildMetadata(): void
    {
        $version = new SemVerV2Version('1.0.0+build');
        $parts = $version->getPreReleaseParts();
        $this->assertEquals([], iterator_to_array($parts));
    }

    // Test property access
    public function testMajorMinorPatchProperties(): void
    {
        $version = new SemVerV2Version('2.5.13');
        $this->assertEquals(2, $version->major);
        $this->assertEquals(5, $version->minor);
        $this->assertEquals(13, $version->patch);
    }

    public function testVersionStringProperty(): void
    {
        $version = new SemVerV2Version('1.2.3-alpha.4+build.5');
        $this->assertEquals('1.2.3-alpha.4+build.5', $version->versionString);
    }

    // Test complex valid versions
    public function testComplexValidVersions(): void
    {
        $validVersions = [
            '0.0.0',
            '1.0.0',
            '1.0.0-0',
            '1.0.0-alpha',
            '1.0.0-alpha.1',
            '1.0.0-0.3.7',
            '1.0.0-x.7.z.92',
            '1.0.0-x-y-z',
            '1.0.0+20130313144700',
            '1.0.0-beta+exp.sha.5114f85',
            '1.0.0+21AF26D3----117B344092BD',
        ];

        foreach ($validVersions as $versionString) {
            $version = new SemVerV2Version($versionString);
            $this->assertInstanceOf(SemVerV2Version::class, $version, "Failed to parse: $versionString");
        }
    }

    // Test invalid versions
    public function testInvalidVersionsAreRejected(): void
    {
        $invalidVersions = [
            '1',              // Missing minor and patch
            '1.0',            // Missing patch
            '1.0.0-',         // Empty prerelease
            '1.0.0+',         // Empty build metadata
            '01.0.0',         // Leading zero in major
            '1.01.0',         // Leading zero in minor
            '1.0.01',         // Leading zero in patch
            'v1.0.0',         // Prefix not allowed in strict SemVer
            '1.0.0alpha',     // Missing hyphen before prerelease
        ];

        foreach ($invalidVersions as $versionString) {
            try {
                new SemVerV2Version($versionString);
                $this->fail("Expected InvalidVersionException for: $versionString");
            } catch (InvalidVersionException $e) {
                $this->assertInstanceOf(InvalidVersionException::class, $e);
            }
        }
    }

    // Test numeric prerelease identifiers
    public function testNumericPrereleaseIdentifiers(): void
    {
        $version = new SemVerV2Version('1.0.0-1.2.3');
        $parts = $version->getPreReleaseParts();
        $this->assertEquals(['1', '2', '3'], iterator_to_array($parts));
    }

    // Test alphanumeric prerelease identifiers with hyphens
    public function testAlphanumericPrereleaseWithHyphens(): void
    {
        $version = new SemVerV2Version('1.0.0-alpha-beta-gamma');
        $this->assertTrue($version->isPreRelease);
        $this->assertEquals('alpha-beta-gamma', $version->preRelease);
    }

    // Test maximum numeric values
    public function testLargeNumericValues(): void
    {
        $version = new SemVerV2Version('999999.999999.999999');
        $this->assertEquals(999999, $version->major);
        $this->assertEquals(999999, $version->minor);
        $this->assertEquals(999999, $version->patch);
    }
}
