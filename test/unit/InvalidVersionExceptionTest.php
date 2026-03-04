<?php

declare(strict_types=1);

namespace Horde\Version\Test\Unit;

use Horde\Version\InvalidVersionException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[CoversClass(InvalidVersionException::class)]
class InvalidVersionExceptionTest extends TestCase
{
    public function testExceptionExtendsRuntimeException(): void
    {
        $exception = new InvalidVersionException();
        $this->assertInstanceOf(RuntimeException::class, $exception);
    }

    public function testDefaultConstructor(): void
    {
        $exception = new InvalidVersionException();
        $this->assertEquals('Version did not parse to expected format', $exception->getMessage());
        $this->assertEquals(0, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }

    public function testCustomMessage(): void
    {
        $exception = new InvalidVersionException('Custom error message');
        $this->assertEquals('Custom error message', $exception->getMessage());
        $this->assertEquals(0, $exception->getCode());
    }

    public function testCustomMessageAndCode(): void
    {
        $exception = new InvalidVersionException('Custom error message', 42);
        $this->assertEquals('Custom error message', $exception->getMessage());
        $this->assertEquals(42, $exception->getCode());
    }

    public function testPreviousExceptionChaining(): void
    {
        $previous = new \RuntimeException('Previous exception');
        $exception = new InvalidVersionException('Wrapped exception', 0, $previous);

        $this->assertEquals('Wrapped exception', $exception->getMessage());
        $this->assertSame($previous, $exception->getPrevious());
        $this->assertEquals('Previous exception', $exception->getPrevious()->getMessage());
    }

    public function testFullConstructor(): void
    {
        $previous = new \RuntimeException('Root cause');
        $exception = new InvalidVersionException('Version parsing failed', 123, $previous);

        $this->assertEquals('Version parsing failed', $exception->getMessage());
        $this->assertEquals(123, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }
}
