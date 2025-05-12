<?php

declare(strict_types=1);

namespace Horde\Version\Test\Unit;

use PHPUnit\Framework\TestCase;
use Stringable;
use PHPUnit\Framework\Attributes\CoversNothing;
use Horde\Version\SemVerV2Version;
use Horde\Version\RelaxedSemanticVersion;
use Horde\Version\Stability;
use Horde\Version\NextVersion;

#[CoversNothing]
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
}