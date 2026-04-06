<?php

declare(strict_types=1);

namespace Horde\Version\Test\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Horde\Version\VersionCollection;
use Horde\Version\RelaxedSemanticVersion;
use InvalidArgumentException;

#[CoversClass(VersionCollection::class)]
class VersionCollectionTest extends TestCase
{
    // Sort tests
    public function testSortAscending(): void
    {
        $versions = ['2.0.0', '1.0.0', '1.5.0', '1.0.1'];
        $sorted = VersionCollection::sort($versions);

        $this->assertEquals(['1.0.0', '1.0.1', '1.5.0', '2.0.0'], $sorted);
    }

    public function testSortDescending(): void
    {
        $versions = ['1.0.0', '2.0.0', '1.5.0'];
        $sorted = VersionCollection::sort($versions, descending: true);

        $this->assertEquals(['2.0.0', '1.5.0', '1.0.0'], $sorted);
    }

    public function testSortWithPrereleases(): void
    {
        $versions = ['1.0.0', '1.0.0-alpha', '1.0.0-beta', '1.0.0-rc'];
        $sorted = VersionCollection::sort($versions);

        $this->assertEquals(['1.0.0-alpha', '1.0.0-beta', '1.0.0-rc', '1.0.0'], $sorted);
    }

    public function testSortEmptyArray(): void
    {
        $sorted = VersionCollection::sort([]);
        $this->assertEquals([], $sorted);
    }

    public function testSortWithVersionObjects(): void
    {
        $versions = [
            new RelaxedSemanticVersion('2.0.0'),
            new RelaxedSemanticVersion('1.0.0'),
        ];
        $sorted = VersionCollection::sort($versions);

        $this->assertCount(2, $sorted);
        $this->assertEquals('1.0.0', (string) $sorted[0]);
        $this->assertEquals('2.0.0', (string) $sorted[1]);
    }

    // Latest tests
    public function testLatest(): void
    {
        $versions = ['1.0.0', '2.0.0', '1.5.0'];
        $latest = VersionCollection::latest($versions);

        $this->assertEquals('2.0.0', $latest);
    }

    public function testLatestWithMinStability(): void
    {
        $versions = ['1.0.0', '2.0.0-alpha', '1.5.0'];
        $latest = VersionCollection::latest($versions, 'stable');

        $this->assertEquals('1.5.0', $latest);
    }

    public function testLatestEmptyArray(): void
    {
        $latest = VersionCollection::latest([]);
        $this->assertNull($latest);
    }

    public function testLatestNoMatchingStability(): void
    {
        $versions = ['1.0.0-alpha', '1.0.0-beta'];
        $latest = VersionCollection::latest($versions, 'stable');

        $this->assertNull($latest);
    }

    // Oldest tests
    public function testOldest(): void
    {
        $versions = ['2.0.0', '1.0.0', '1.5.0'];
        $oldest = VersionCollection::oldest($versions);

        $this->assertEquals('1.0.0', $oldest);
    }

    public function testOldestWithMinStability(): void
    {
        $versions = ['1.0.0-alpha', '1.5.0', '2.0.0'];
        $oldest = VersionCollection::oldest($versions, 'stable');

        $this->assertEquals('1.5.0', $oldest);
    }

    // Filter tests
    public function testFilterWithCallable(): void
    {
        $versions = ['1.0.0', '2.0.0', '3.0.0'];
        $filtered = VersionCollection::filter($versions, function ($v) {
            return $v->major >= 2;
        });

        $this->assertCount(2, $filtered);
        $this->assertContains('2.0.0', $filtered);
        $this->assertContains('3.0.0', $filtered);
    }

    public function testFilterWithStability(): void
    {
        $versions = ['1.0.0', '2.0.0-alpha', '3.0.0-beta', '4.0.0'];
        $filtered = VersionCollection::filter($versions, 'stable');

        $this->assertCount(2, $filtered);
        $this->assertContains('1.0.0', $filtered);
        $this->assertContains('4.0.0', $filtered);
    }

    public function testFilterWithConstraintString(): void
    {
        $versions = ['1.0.0', '1.5.0', '2.0.0', '2.5.0'];
        $filtered = VersionCollection::filter($versions, '>=1.5.0 <2.5.0');

        $this->assertCount(2, $filtered);
        $this->assertContains('1.5.0', $filtered);
        $this->assertContains('2.0.0', $filtered);
    }

    public function testFilterEmptyArray(): void
    {
        $filtered = VersionCollection::filter([], fn($v) => true);
        $this->assertEquals([], $filtered);
    }

    // Group tests
    public function testGroupByMajor(): void
    {
        $versions = ['1.0.0', '1.5.0', '2.0.0', '2.1.0'];
        $grouped = VersionCollection::group($versions, 'major');

        $this->assertArrayHasKey('1.x', $grouped);
        $this->assertArrayHasKey('2.x', $grouped);
        $this->assertCount(2, $grouped['1.x']);
        $this->assertCount(2, $grouped['2.x']);
    }

    public function testGroupByMinor(): void
    {
        $versions = ['1.0.0', '1.0.5', '1.1.0', '2.0.0'];
        $grouped = VersionCollection::group($versions, 'minor');

        $this->assertArrayHasKey('1.0.x', $grouped);
        $this->assertArrayHasKey('1.1.x', $grouped);
        $this->assertArrayHasKey('2.0.x', $grouped);
        $this->assertCount(2, $grouped['1.0.x']);
        $this->assertCount(1, $grouped['1.1.x']);
    }

    public function testGroupEmptyArray(): void
    {
        $grouped = VersionCollection::group([]);
        $this->assertEquals([], $grouped);
    }

    public function testGroupInvalidBy(): void
    {
        $this->expectException(InvalidArgumentException::class);
        VersionCollection::group(['1.0.0'], 'invalid');
    }

    // Integration tests
    public function testRealWorldScenario(): void
    {
        // Simulating versions from a package repository
        $versions = [
            '1.0.0',
            '1.0.1',
            '1.1.0',
            '1.2.0-alpha',
            '1.2.0-beta',
            '1.2.0',
            '2.0.0-rc.1',
            '2.0.0',
            '2.1.0',
        ];

        // Find latest stable
        $latestStable = VersionCollection::latest($versions, 'stable');
        $this->assertEquals('2.1.0', $latestStable);

        // Find all 1.x versions (^1.0 means >=1.0.0 <2.0.0)
        // This includes 2.0.0 prereleases since 2.0.0-rc.1 < 2.0.0
        $v1x = VersionCollection::filter($versions, '^1.0');
        $this->assertCount(7, $v1x);

        // Sort all versions
        $sorted = VersionCollection::sort($versions);
        $this->assertEquals('1.0.0', $sorted[0]);
        $this->assertEquals('2.1.0', $sorted[count($sorted) - 1]);
    }

    public function testFilterBetaAndAbove(): void
    {
        $versions = [
            '1.0.0-alpha',
            '1.0.0-beta',
            '1.0.0-rc',
            '1.0.0',
        ];

        $filtered = VersionCollection::filter($versions, 'beta');
        // Should include beta, rc, and stable
        $this->assertCount(3, $filtered);
        $this->assertContains('1.0.0-beta', $filtered);
        $this->assertContains('1.0.0-rc', $filtered);
        $this->assertContains('1.0.0', $filtered);
    }

    public function testMixedStringAndObjectHandling(): void
    {
        // Test that we handle both strings and objects correctly
        $stringVersions = ['1.0.0', '2.0.0'];
        $objectVersions = [
            new RelaxedSemanticVersion('1.0.0'),
            new RelaxedSemanticVersion('2.0.0'),
        ];

        $sortedStrings = VersionCollection::sort($stringVersions);
        $sortedObjects = VersionCollection::sort($objectVersions);

        // Should preserve input type
        $this->assertIsString($sortedStrings[0]);
        $this->assertInstanceOf(RelaxedSemanticVersion::class, $sortedObjects[0]);
    }
}
