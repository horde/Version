<?php

declare(strict_types=1);

namespace Horde\Version\Test\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Horde\Version\GenericVersion;
use Horde\Version\Version;
use Stringable;

#[CoversClass(GenericVersion::class)]
class GenericVersionTest extends TestCase
{
    public function testBasicConstruction(): void
    {
        $version = new GenericVersion('1.0.0');
        $this->assertInstanceOf(GenericVersion::class, $version);
        $this->assertEquals('1.0.0', (string) $version);

        $version = new GenericVersion('1.0.0-alpha');
        $this->assertInstanceOf(GenericVersion::class, $version);
        $this->assertEquals('1.0.0-alpha', (string) $version);
    }

    public function testImplementsVersionInterface(): void
    {
        $version = new GenericVersion('1.0.0');
        $this->assertInstanceOf(Version::class, $version);
    }

    public function testImplementsStringable(): void
    {
        $version = new GenericVersion('1.0.0');
        $this->assertInstanceOf(Stringable::class, $version);
    }

    public function testToStringMethod(): void
    {
        $versionString = '2.5.3-beta+build123';
        $version = new GenericVersion($versionString);
        $this->assertEquals($versionString, $version->__toString());
        $this->assertEquals($versionString, (string) $version);
    }

    public function testVersionStringProperty(): void
    {
        $versionString = 'custom-version-1.2.3';
        $version = new GenericVersion($versionString);
        $this->assertEquals($versionString, $version->versionString);
    }

    // Test that GenericVersion accepts ANY string
    public function testAcceptsArbitraryVersionStrings(): void
    {
        $arbitraryVersions = [
            'v1.0.0',
            '1.0',
            '1',
            'alpha',
            '2024.1.15',
            '1.0.0-SNAPSHOT',
            '1.0.0.RELEASE',
            'latest',
            'nightly',
            '20240315',
            'v1.0.0-rc.1+build.123',
            '1.2.3.4.5.6',
            'version-1.0',
        ];

        foreach ($arbitraryVersions as $versionString) {
            $version = new GenericVersion($versionString);
            $this->assertEquals($versionString, (string) $version);
        }
    }

    // Test edge cases
    public function testEmptyString(): void
    {
        $version = new GenericVersion('');
        $this->assertEquals('', (string) $version);
    }

    public function testVeryLongString(): void
    {
        $longString = str_repeat('a', 1000);
        $version = new GenericVersion($longString);
        $this->assertEquals($longString, (string) $version);
    }

    public function testSpecialCharacters(): void
    {
        $specialVersions = [
            '1.0.0-alpha+build!',
            'version@1.0',
            '1.0.0#snapshot',
            '1.0.0$beta',
            '1.0.0%test',
            '1.0.0^dev',
            '1.0.0&experimental',
            '1.0.0*wildcard',
            '1.0.0(pre)',
            '1.0.0)post',
            '1.0.0[bracket]',
            '1.0.0{brace}',
        ];

        foreach ($specialVersions as $versionString) {
            $version = new GenericVersion($versionString);
            $this->assertEquals($versionString, (string) $version);
        }
    }

    public function testWhitespace(): void
    {
        $version = new GenericVersion('  1.0.0  ');
        $this->assertEquals('  1.0.0  ', (string) $version);

        $version = new GenericVersion("1.0.0\n");
        $this->assertEquals("1.0.0\n", (string) $version);

        $version = new GenericVersion("1.0.0\t");
        $this->assertEquals("1.0.0\t", (string) $version);
    }

    public function testUnicodeCharacters(): void
    {
        $unicodeVersions = [
            '1.0.0-α',
            '1.0.0-β',
            'バージョン1.0',
            '版本1.0',
            '🚀1.0.0',
        ];

        foreach ($unicodeVersions as $versionString) {
            $version = new GenericVersion($versionString);
            $this->assertEquals($versionString, (string) $version);
        }
    }

    // Test that the version string is stored exactly as provided
    public function testPreservesExactInput(): void
    {
        $inputs = [
            'V1.0.0',      // Uppercase
            'v1.0.0',      // Lowercase
            '1.0.0 ',      // Trailing space
            ' 1.0.0',      // Leading space
            '1.0.0\n',     // Newline
        ];

        foreach ($inputs as $input) {
            $version = new GenericVersion($input);
            $this->assertSame($input, (string) $version);
            $this->assertSame($input, $version->versionString);
        }
    }

    // Test numeric strings
    public function testNumericStrings(): void
    {
        $version = new GenericVersion('123');
        $this->assertEquals('123', (string) $version);
    }

    public function testFloatLikeStrings(): void
    {
        $version = new GenericVersion('1.5');
        $this->assertEquals('1.5', (string) $version);
    }

    // Test comparison of instances (they should not be equal even if strings are the same)
    public function testInstancesAreDifferentObjects(): void
    {
        $version1 = new GenericVersion('1.0.0');
        $version2 = new GenericVersion('1.0.0');
        $this->assertNotSame($version1, $version2);
        $this->assertEquals((string) $version1, (string) $version2);
    }
}
