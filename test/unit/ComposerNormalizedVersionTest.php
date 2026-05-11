<?php

declare(strict_types=1);

namespace Horde\Version\Test\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Horde\Version\ComposerNormalizedVersion;
use Horde\Version\InvalidVersionException;
use Horde\Version\Version;

#[CoversClass(ComposerNormalizedVersion::class)]
class ComposerNormalizedVersionTest extends TestCase
{
    public function testImplementsVersionInterface(): void
    {
        $version = new ComposerNormalizedVersion('3.1.0.0');
        $this->assertInstanceOf(Version::class, $version);
    }

    public function testBasicFourComponentVersion(): void
    {
        $version = new ComposerNormalizedVersion('3.1.0.0');
        $this->assertEquals(3, $version->major);
        $this->assertEquals(1, $version->minor);
        $this->assertEquals(0, $version->patch);
        $this->assertEquals(0, $version->subpatch);
        $this->assertFalse($version->isPreRelease);
        $this->assertEquals('stable', $version->stability);
        $this->assertEquals(0, $version->stabilityRevision);
    }

    public function testZeroVersion(): void
    {
        $version = new ComposerNormalizedVersion('0.0.0.0');
        $this->assertEquals(0, $version->major);
        $this->assertEquals(0, $version->minor);
        $this->assertEquals(0, $version->patch);
        $this->assertEquals(0, $version->subpatch);
    }

    public function testNonZeroSubpatch(): void
    {
        $version = new ComposerNormalizedVersion('3.1.0.5');
        $this->assertEquals(5, $version->subpatch);
    }

    public function testAlphaStability(): void
    {
        $version = new ComposerNormalizedVersion('3.1.0.0-alpha1');
        $this->assertTrue($version->isPreRelease);
        $this->assertEquals('alpha', $version->stability);
        $this->assertEquals(1, $version->stabilityRevision);
    }

    public function testBetaStability(): void
    {
        $version = new ComposerNormalizedVersion('2.0.0.0-beta2');
        $this->assertTrue($version->isPreRelease);
        $this->assertEquals('beta', $version->stability);
        $this->assertEquals(2, $version->stabilityRevision);
    }

    public function testRCStability(): void
    {
        $version = new ComposerNormalizedVersion('1.0.0.0-RC3');
        $this->assertTrue($version->isPreRelease);
        $this->assertEquals('RC', $version->stability);
        $this->assertEquals(3, $version->stabilityRevision);
    }

    public function testRCStabilityCaseInsensitive(): void
    {
        $version = new ComposerNormalizedVersion('1.0.0.0-rc1');
        $this->assertTrue($version->isPreRelease);
        $this->assertEquals('RC', $version->stability);
        $this->assertEquals(1, $version->stabilityRevision);
    }

    public function testDevStability(): void
    {
        $version = new ComposerNormalizedVersion('1.0.0.0-dev');
        $this->assertTrue($version->isPreRelease);
        $this->assertEquals('dev', $version->stability);
        $this->assertEquals(0, $version->stabilityRevision);
    }

    public function testPatchStability(): void
    {
        $version = new ComposerNormalizedVersion('1.0.0.0-patch1');
        $this->assertFalse($version->isPreRelease);
        $this->assertEquals('patch', $version->stability);
        $this->assertEquals(1, $version->stabilityRevision);
    }

    public function testPlStabilityNormalizesToPatch(): void
    {
        $version = new ComposerNormalizedVersion('1.0.0.0-pl1');
        $this->assertEquals('patch', $version->stability);
    }

    public function testPStabilityNormalizesToPatch(): void
    {
        $version = new ComposerNormalizedVersion('1.0.0.0-p1');
        $this->assertEquals('patch', $version->stability);
    }

    public function testStableExplicitSuffix(): void
    {
        $version = new ComposerNormalizedVersion('1.0.0.0-stable');
        $this->assertFalse($version->isPreRelease);
        $this->assertEquals('stable', $version->stability);
    }

    public function testStabilityWithoutRevision(): void
    {
        $version = new ComposerNormalizedVersion('1.0.0.0-alpha');
        $this->assertTrue($version->isPreRelease);
        $this->assertEquals('alpha', $version->stability);
        $this->assertEquals(0, $version->stabilityRevision);
    }

    public function testToStringPreservesInput(): void
    {
        $input = '3.1.0.0-alpha1';
        $version = new ComposerNormalizedVersion($input);
        $this->assertEquals($input, (string) $version);
    }

    public function testVersionStringProperty(): void
    {
        $input = '1.2.3.4-beta5';
        $version = new ComposerNormalizedVersion($input);
        $this->assertEquals($input, $version->versionString);
    }

    // Invalid version tests

    public function testThreeComponentsAreInvalid(): void
    {
        $this->expectException(InvalidVersionException::class);
        new ComposerNormalizedVersion('3.1.0');
    }

    public function testTwoComponentsAreInvalid(): void
    {
        $this->expectException(InvalidVersionException::class);
        new ComposerNormalizedVersion('3.1');
    }

    public function testPrefixIsInvalid(): void
    {
        $this->expectException(InvalidVersionException::class);
        new ComposerNormalizedVersion('v3.1.0.0');
    }

    public function testInvalidStability(): void
    {
        $this->expectException(InvalidVersionException::class);
        new ComposerNormalizedVersion('3.1.0.0-invalid');
    }

    public function testEmptyStringIsInvalid(): void
    {
        $this->expectException(InvalidVersionException::class);
        new ComposerNormalizedVersion('');
    }

    public function testNonNumericComponentsAreInvalid(): void
    {
        $this->expectException(InvalidVersionException::class);
        new ComposerNormalizedVersion('x.y.z.w');
    }

    public function testFiveComponentsAreInvalid(): void
    {
        $this->expectException(InvalidVersionException::class);
        new ComposerNormalizedVersion('1.0.0.0.0');
    }

    // formatSemVerV2 tests

    public function testFormatSemVerV2Stable(): void
    {
        $version = new ComposerNormalizedVersion('3.1.0.0');
        $this->assertEquals('3.1.0', $version->formatSemVerV2());
    }

    public function testFormatSemVerV2WithAlpha(): void
    {
        $version = new ComposerNormalizedVersion('3.1.0.0-alpha1');
        $this->assertEquals('3.1.0-alpha.1', $version->formatSemVerV2());
    }

    public function testFormatSemVerV2WithBeta(): void
    {
        $version = new ComposerNormalizedVersion('2.0.0.0-beta2');
        $this->assertEquals('2.0.0-beta.2', $version->formatSemVerV2());
    }

    public function testFormatSemVerV2WithRC(): void
    {
        $version = new ComposerNormalizedVersion('1.0.0.0-RC3');
        $this->assertEquals('1.0.0-rc.3', $version->formatSemVerV2());
    }

    public function testFormatSemVerV2WithDev(): void
    {
        $version = new ComposerNormalizedVersion('1.0.0.0-dev');
        $this->assertEquals('1.0.0-dev', $version->formatSemVerV2());
    }

    public function testFormatSemVerV2WithPatchStability(): void
    {
        $version = new ComposerNormalizedVersion('1.0.0.0-patch1');
        // patch stability is post-release, not pre-release
        $this->assertEquals('1.0.0', $version->formatSemVerV2());
    }

    // Comparison tests

    public function testEqualVersions(): void
    {
        $a = new ComposerNormalizedVersion('3.1.0.0');
        $b = new ComposerNormalizedVersion('3.1.0.0');
        $this->assertTrue($a->equals($b));
        $this->assertEquals(0, $a->compare($b));
    }

    public function testMajorComparison(): void
    {
        $a = new ComposerNormalizedVersion('2.0.0.0');
        $b = new ComposerNormalizedVersion('3.0.0.0');
        $this->assertTrue($a->isLessThan($b));
        $this->assertTrue($b->isGreaterThan($a));
    }

    public function testMinorComparison(): void
    {
        $a = new ComposerNormalizedVersion('3.0.0.0');
        $b = new ComposerNormalizedVersion('3.1.0.0');
        $this->assertTrue($a->isLessThan($b));
    }

    public function testPatchComparison(): void
    {
        $a = new ComposerNormalizedVersion('3.1.0.0');
        $b = new ComposerNormalizedVersion('3.1.1.0');
        $this->assertTrue($a->isLessThan($b));
    }

    public function testSubpatchComparison(): void
    {
        $a = new ComposerNormalizedVersion('3.1.0.0');
        $b = new ComposerNormalizedVersion('3.1.0.1');
        $this->assertTrue($a->isLessThan($b));
    }

    public function testStabilityComparison(): void
    {
        $dev = new ComposerNormalizedVersion('1.0.0.0-dev');
        $alpha = new ComposerNormalizedVersion('1.0.0.0-alpha1');
        $beta = new ComposerNormalizedVersion('1.0.0.0-beta1');
        $rc = new ComposerNormalizedVersion('1.0.0.0-RC1');
        $stable = new ComposerNormalizedVersion('1.0.0.0');

        $this->assertTrue($dev->isLessThan($alpha));
        $this->assertTrue($alpha->isLessThan($beta));
        $this->assertTrue($beta->isLessThan($rc));
        $this->assertTrue($rc->isLessThan($stable));
    }

    public function testStabilityRevisionComparison(): void
    {
        $a = new ComposerNormalizedVersion('1.0.0.0-alpha1');
        $b = new ComposerNormalizedVersion('1.0.0.0-alpha2');
        $this->assertTrue($a->isLessThan($b));
    }

    public function testHigherVersionAlwaysGreaterThanLowerPrerelease(): void
    {
        $prerelease = new ComposerNormalizedVersion('2.0.0.0-alpha1');
        $stable = new ComposerNormalizedVersion('1.0.0.0');
        $this->assertTrue($prerelease->isGreaterThan($stable));
    }

    public function testLargeVersionNumbers(): void
    {
        $version = new ComposerNormalizedVersion('999.999.999.999');
        $this->assertEquals(999, $version->major);
        $this->assertEquals(999, $version->minor);
        $this->assertEquals(999, $version->patch);
        $this->assertEquals(999, $version->subpatch);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function validVersionsProvider(): array
    {
        return [
            'simple stable' => ['1.0.0.0'],
            'all zeros' => ['0.0.0.0'],
            'non-zero subpatch' => ['1.2.3.4'],
            'alpha without revision' => ['1.0.0.0-alpha'],
            'alpha with revision' => ['1.0.0.0-alpha1'],
            'beta' => ['2.0.0.0-beta3'],
            'RC uppercase' => ['3.0.0.0-RC1'],
            'rc lowercase' => ['3.0.0.0-rc1'],
            'dev' => ['1.0.0.0-dev'],
            'patch stability' => ['1.0.0.0-patch1'],
            'pl stability' => ['1.0.0.0-pl2'],
            'p stability' => ['1.0.0.0-p3'],
            'stable explicit' => ['1.0.0.0-stable'],
            'large numbers' => ['100.200.300.400'],
        ];
    }

    #[DataProvider('validVersionsProvider')]
    public function testValidVersionsAreParsed(string $versionString): void
    {
        $version = new ComposerNormalizedVersion($versionString);
        $this->assertInstanceOf(ComposerNormalizedVersion::class, $version);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidVersionsProvider(): array
    {
        return [
            'three components' => ['1.0.0'],
            'two components' => ['1.0'],
            'one component' => ['1'],
            'five components' => ['1.0.0.0.0'],
            'v prefix' => ['v1.0.0.0'],
            'invalid stability' => ['1.0.0.0-gamma'],
            'empty' => [''],
            'text' => ['invalid'],
            'semver prerelease' => ['1.0.0.0-alpha.1'],
            'build metadata' => ['1.0.0.0+build'],
        ];
    }

    #[DataProvider('invalidVersionsProvider')]
    public function testInvalidVersionsAreRejected(string $versionString): void
    {
        $this->expectException(InvalidVersionException::class);
        new ComposerNormalizedVersion($versionString);
    }
}
