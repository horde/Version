<?php

declare(strict_types=1);

namespace Horde\Version\Test\Unit;

use PHPUnit\Framework\TestCase;
use Stringable;
use PHPUnit\Framework\Attributes\CoversNothing;
use Horde\Version\GenericVersion;

#[CoversNothing]
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
}
