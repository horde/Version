<?php

declare(strict_types=1);

namespace Horde\Version\Test\Unit;

use PHPUnit\Framework\TestCase;
use Stringable;
use PHPUnit\Framework\Attributes\CoversNothing;
use Horde\Version\SemVerV2Version;

#[CoversNothing]
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
        $this->expectException(\Horde\Version\InvalidVersionException::class);
        $this->expectExceptionMessage('Version did not parse to expected format');
        new SemVerV2Version('invalid-version');
    }
    public function testPrefixedVersionIsInvalidVersion(): void
    {
        $this->expectException(\Horde\Version\InvalidVersionException::class);
        $this->expectExceptionMessage('Version did not parse to expected format');
        new SemVerV2Version('v1.0.0');
    }
    public function testPrereleaseVersionWithoutHyphenIsInvalidVersion(): void
    {
        $this->expectException(\Horde\Version\InvalidVersionException::class);
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
}
