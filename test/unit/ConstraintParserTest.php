<?php

declare(strict_types=1);

namespace Horde\Version\Test\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Horde\Version\ConstraintParser;
use Horde\Version\RelaxedSemanticVersion;
use Horde\Version\InvalidVersionException;

#[CoversClass(ConstraintParser::class)]
class ConstraintParserTest extends TestCase
{
    private ConstraintParser $parser;

    protected function setUp(): void
    {
        $this->parser = new ConstraintParser();
    }

    // Exact constraints
    public function testParseExactConstraint(): void
    {
        $constraint = $this->parser->parse('1.0.0');
        $this->assertTrue($constraint->isSatisfiedBy(new RelaxedSemanticVersion('1.0.0')));
        $this->assertFalse($constraint->isSatisfiedBy(new RelaxedSemanticVersion('1.0.1')));
    }

    // Comparison constraints
    public function testParseGreaterThan(): void
    {
        $constraint = $this->parser->parse('>1.0.0');
        $this->assertFalse($constraint->isSatisfiedBy(new RelaxedSemanticVersion('1.0.0')));
        $this->assertTrue($constraint->isSatisfiedBy(new RelaxedSemanticVersion('1.0.1')));
    }

    public function testParseGreaterOrEqual(): void
    {
        $constraint = $this->parser->parse('>=1.0.0');
        $this->assertTrue($constraint->isSatisfiedBy(new RelaxedSemanticVersion('1.0.0')));
        $this->assertTrue($constraint->isSatisfiedBy(new RelaxedSemanticVersion('1.0.1')));
        $this->assertFalse($constraint->isSatisfiedBy(new RelaxedSemanticVersion('0.9.0')));
    }

    public function testParseLessThan(): void
    {
        $constraint = $this->parser->parse('<2.0.0');
        $this->assertTrue($constraint->isSatisfiedBy(new RelaxedSemanticVersion('1.0.0')));
        $this->assertFalse($constraint->isSatisfiedBy(new RelaxedSemanticVersion('2.0.0')));
    }

    public function testParseLessOrEqual(): void
    {
        $constraint = $this->parser->parse('<=2.0.0');
        $this->assertTrue($constraint->isSatisfiedBy(new RelaxedSemanticVersion('2.0.0')));
        $this->assertTrue($constraint->isSatisfiedBy(new RelaxedSemanticVersion('1.0.0')));
        $this->assertFalse($constraint->isSatisfiedBy(new RelaxedSemanticVersion('2.0.1')));
    }

    public function testParseNotEqual(): void
    {
        $constraint = $this->parser->parse('!=1.0.0');
        $this->assertFalse($constraint->isSatisfiedBy(new RelaxedSemanticVersion('1.0.0')));
        $this->assertTrue($constraint->isSatisfiedBy(new RelaxedSemanticVersion('1.0.1')));
    }

    // Caret constraints
    public function testParseCaretMajorNonZero(): void
    {
        $constraint = $this->parser->parse('^1.2.3');
        $this->assertTrue($constraint->isSatisfiedBy(new RelaxedSemanticVersion('1.2.3')));
        $this->assertTrue($constraint->isSatisfiedBy(new RelaxedSemanticVersion('1.2.4')));
        $this->assertTrue($constraint->isSatisfiedBy(new RelaxedSemanticVersion('1.3.0')));
        $this->assertFalse($constraint->isSatisfiedBy(new RelaxedSemanticVersion('2.0.0')));
        $this->assertFalse($constraint->isSatisfiedBy(new RelaxedSemanticVersion('1.2.2')));
    }

    public function testParseCaretMinorNonZero(): void
    {
        $constraint = $this->parser->parse('^0.2.3');
        $this->assertTrue($constraint->isSatisfiedBy(new RelaxedSemanticVersion('0.2.3')));
        $this->assertTrue($constraint->isSatisfiedBy(new RelaxedSemanticVersion('0.2.4')));
        $this->assertFalse($constraint->isSatisfiedBy(new RelaxedSemanticVersion('0.3.0')));
        $this->assertFalse($constraint->isSatisfiedBy(new RelaxedSemanticVersion('0.2.2')));
    }

    public function testParseCaretPatchOnly(): void
    {
        $constraint = $this->parser->parse('^0.0.3');
        $this->assertTrue($constraint->isSatisfiedBy(new RelaxedSemanticVersion('0.0.3')));
        $this->assertFalse($constraint->isSatisfiedBy(new RelaxedSemanticVersion('0.0.4')));
        $this->assertFalse($constraint->isSatisfiedBy(new RelaxedSemanticVersion('0.0.2')));
    }

    // Tilde constraints
    public function testParseTildeWithPatch(): void
    {
        $constraint = $this->parser->parse('~1.2.3');
        $this->assertTrue($constraint->isSatisfiedBy(new RelaxedSemanticVersion('1.2.3')));
        $this->assertTrue($constraint->isSatisfiedBy(new RelaxedSemanticVersion('1.2.4')));
        $this->assertFalse($constraint->isSatisfiedBy(new RelaxedSemanticVersion('1.3.0')));
        $this->assertFalse($constraint->isSatisfiedBy(new RelaxedSemanticVersion('1.2.2')));
    }

    public function testParseTildeWithoutPatch(): void
    {
        $constraint = $this->parser->parse('~1.2');
        $this->assertTrue($constraint->isSatisfiedBy(new RelaxedSemanticVersion('1.2.0')));
        $this->assertTrue($constraint->isSatisfiedBy(new RelaxedSemanticVersion('1.9.0')));
        $this->assertFalse($constraint->isSatisfiedBy(new RelaxedSemanticVersion('2.0.0')));
    }

    // Wildcard constraints
    public function testParseWildcardPatch(): void
    {
        $constraint = $this->parser->parse('1.0.*');
        $this->assertTrue($constraint->isSatisfiedBy(new RelaxedSemanticVersion('1.0.0')));
        $this->assertTrue($constraint->isSatisfiedBy(new RelaxedSemanticVersion('1.0.9')));
        $this->assertFalse($constraint->isSatisfiedBy(new RelaxedSemanticVersion('1.1.0')));
    }

    public function testParseWildcardMinor(): void
    {
        $constraint = $this->parser->parse('1.*');
        $this->assertTrue($constraint->isSatisfiedBy(new RelaxedSemanticVersion('1.0.0')));
        $this->assertTrue($constraint->isSatisfiedBy(new RelaxedSemanticVersion('1.9.0')));
        $this->assertFalse($constraint->isSatisfiedBy(new RelaxedSemanticVersion('2.0.0')));
    }

    // Range constraints
    public function testParseRange(): void
    {
        $constraint = $this->parser->parse('1.0.0 - 2.0.0');
        $this->assertTrue($constraint->isSatisfiedBy(new RelaxedSemanticVersion('1.0.0')));
        $this->assertTrue($constraint->isSatisfiedBy(new RelaxedSemanticVersion('1.5.0')));
        $this->assertTrue($constraint->isSatisfiedBy(new RelaxedSemanticVersion('2.0.0')));
        $this->assertFalse($constraint->isSatisfiedBy(new RelaxedSemanticVersion('0.9.0')));
        $this->assertFalse($constraint->isSatisfiedBy(new RelaxedSemanticVersion('2.0.1')));
    }

    // AND constraints (space-separated)
    public function testParseAndConstraint(): void
    {
        $constraint = $this->parser->parse('>=1.0.0 <2.0.0');
        $this->assertTrue($constraint->isSatisfiedBy(new RelaxedSemanticVersion('1.0.0')));
        $this->assertTrue($constraint->isSatisfiedBy(new RelaxedSemanticVersion('1.5.0')));
        $this->assertFalse($constraint->isSatisfiedBy(new RelaxedSemanticVersion('2.0.0')));
        $this->assertFalse($constraint->isSatisfiedBy(new RelaxedSemanticVersion('0.9.0')));
    }

    // OR constraints (||)
    public function testParseOrConstraint(): void
    {
        $constraint = $this->parser->parse('^1.0 || ^2.0');
        $this->assertTrue($constraint->isSatisfiedBy(new RelaxedSemanticVersion('1.0.0')));
        $this->assertTrue($constraint->isSatisfiedBy(new RelaxedSemanticVersion('1.5.0')));
        $this->assertTrue($constraint->isSatisfiedBy(new RelaxedSemanticVersion('2.0.0')));
        $this->assertTrue($constraint->isSatisfiedBy(new RelaxedSemanticVersion('2.5.0')));
        $this->assertFalse($constraint->isSatisfiedBy(new RelaxedSemanticVersion('3.0.0')));
        $this->assertFalse($constraint->isSatisfiedBy(new RelaxedSemanticVersion('0.9.0')));
    }

    // Complex constraints
    public function testParseComplexConstraint(): void
    {
        $constraint = $this->parser->parse('>=1.0.0 <2.0.0 || >=3.0.0');
        $this->assertTrue($constraint->isSatisfiedBy(new RelaxedSemanticVersion('1.5.0')));
        $this->assertFalse($constraint->isSatisfiedBy(new RelaxedSemanticVersion('2.5.0')));
        $this->assertTrue($constraint->isSatisfiedBy(new RelaxedSemanticVersion('3.0.0')));
    }

    // Major-only constraints
    public function testParseCaretMajorOnly(): void
    {
        $constraint = $this->parser->parse('^12');
        $this->assertTrue($constraint->isSatisfiedBy(new RelaxedSemanticVersion('12.0.0')));
        $this->assertTrue($constraint->isSatisfiedBy(new RelaxedSemanticVersion('12.5.7')));
        $this->assertFalse($constraint->isSatisfiedBy(new RelaxedSemanticVersion('11.5.0')));
        $this->assertFalse($constraint->isSatisfiedBy(new RelaxedSemanticVersion('13.0.0')));
    }

    public function testParseCaretMajorMinor(): void
    {
        $constraint = $this->parser->parse('^1.2');
        $this->assertTrue($constraint->isSatisfiedBy(new RelaxedSemanticVersion('1.2.0')));
        $this->assertTrue($constraint->isSatisfiedBy(new RelaxedSemanticVersion('1.5.0')));
        $this->assertFalse($constraint->isSatisfiedBy(new RelaxedSemanticVersion('1.1.9')));
        $this->assertFalse($constraint->isSatisfiedBy(new RelaxedSemanticVersion('2.0.0')));
    }

    public function testParseTildeMajorOnly(): void
    {
        $constraint = $this->parser->parse('~12');
        $this->assertTrue($constraint->isSatisfiedBy(new RelaxedSemanticVersion('12.0.0')));
        $this->assertTrue($constraint->isSatisfiedBy(new RelaxedSemanticVersion('12.9.9')));
        $this->assertFalse($constraint->isSatisfiedBy(new RelaxedSemanticVersion('11.5.0')));
        $this->assertFalse($constraint->isSatisfiedBy(new RelaxedSemanticVersion('13.0.0')));
    }

    public function testParseExactMajorOnly(): void
    {
        $constraint = $this->parser->parse('12');
        $this->assertTrue($constraint->isSatisfiedBy(new RelaxedSemanticVersion('12.0.0')));
        $this->assertFalse($constraint->isSatisfiedBy(new RelaxedSemanticVersion('12.0.1')));
        $this->assertFalse($constraint->isSatisfiedBy(new RelaxedSemanticVersion('11.0.0')));
    }

    public function testParseComparisonMajorOnly(): void
    {
        $constraint = $this->parser->parse('>=12');
        $this->assertTrue($constraint->isSatisfiedBy(new RelaxedSemanticVersion('12.0.0')));
        $this->assertTrue($constraint->isSatisfiedBy(new RelaxedSemanticVersion('12.5.0')));
        $this->assertTrue($constraint->isSatisfiedBy(new RelaxedSemanticVersion('20.0.0')));
        $this->assertFalse($constraint->isSatisfiedBy(new RelaxedSemanticVersion('11.9.9')));
    }

    public function testParseOrConstraintWithMajorOnly(): void
    {
        $constraint = $this->parser->parse('^11 || ^12');
        $this->assertTrue($constraint->isSatisfiedBy(new RelaxedSemanticVersion('11.0.0')));
        $this->assertTrue($constraint->isSatisfiedBy(new RelaxedSemanticVersion('11.9.9')));
        $this->assertTrue($constraint->isSatisfiedBy(new RelaxedSemanticVersion('12.0.0')));
        $this->assertTrue($constraint->isSatisfiedBy(new RelaxedSemanticVersion('12.5.0')));
        $this->assertFalse($constraint->isSatisfiedBy(new RelaxedSemanticVersion('10.9.9')));
        $this->assertFalse($constraint->isSatisfiedBy(new RelaxedSemanticVersion('13.0.0')));
    }

    public function testParseCaretWithPrefixedMajorOnly(): void
    {
        $constraint = $this->parser->parse('^v12');
        $this->assertTrue($constraint->isSatisfiedBy(new RelaxedSemanticVersion('12.0.0')));
        $this->assertTrue($constraint->isSatisfiedBy(new RelaxedSemanticVersion('12.7.3')));
        $this->assertFalse($constraint->isSatisfiedBy(new RelaxedSemanticVersion('13.0.0')));
    }

    // Error cases
    public function testParseEmptyString(): void
    {
        $this->expectException(InvalidVersionException::class);
        $this->parser->parse('');
    }

    public function testParseInvalidVersion(): void
    {
        $this->expectException(InvalidVersionException::class);
        $this->parser->parse('>invalid');
    }

    // String representation
    public function testConstraintToString(): void
    {
        $constraint = $this->parser->parse('^1.2.3');
        $this->assertEquals('^1.2.3', (string) $constraint);
    }

    public function testCompositeConstraintToString(): void
    {
        $constraint = $this->parser->parse('>=1.0.0 <2.0.0');
        $this->assertStringContainsString('>=', (string) $constraint);
        $this->assertStringContainsString('<', (string) $constraint);
    }

    public function testOrConstraintToString(): void
    {
        $constraint = $this->parser->parse('^1.0 || ^2.0');
        $this->assertStringContainsString('||', (string) $constraint);
    }
}
