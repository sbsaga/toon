<?php
declare(strict_types=1);

namespace Sbsaga\Toon\Tests\Converter;

use PHPUnit\Framework\TestCase;
use Sbsaga\Toon\Converters\ToonConverter;

/**
 * Unit tests for TOON encoding behavior.
 */
final class ToonConverterTest extends TestCase
{
    public function testAssociativeArrayEncoding(): void
    {
        $converter = new ToonConverter();

        $out = $converter->toToon([
            'name' => 'Alice',
            'age' => 30,
            'active' => true,
        ]);

        $this->assertStringContainsString('name: Alice', $out);
        $this->assertStringContainsString('age: 30', $out);
        $this->assertStringContainsString('active: true', $out);
    }

    public function testSequentialArrayEncoding(): void
    {
        $converter = new ToonConverter();

        $out = $converter->toToon(['a', 'b', 'c']);

        $this->assertSame("a\nb\nc", trim($out));
    }

    public function testUniformScalarRowsBecomeTable(): void
    {
        $converter = new ToonConverter(['min_rows_to_tabular' => 1]);

        $out = $converter->toToon([
            ['id' => 1, 'name' => 'A'],
            ['id' => 2, 'name' => 'B'],
        ]);

        $this->assertStringContainsString('items[2]{id,name}:', $out);
        $this->assertStringContainsString('1,A', $out);
        $this->assertStringContainsString('2,B', $out);
    }

    public function testComplexRowsStayExpandedForRoundTripSafety(): void
    {
        $converter = new ToonConverter([
            'min_rows_to_tabular' => 1,
            'compatibility_mode' => 'modern',
        ]);

        $out = $converter->toToon([
            ['id' => 1, 'meta' => ['x' => 1]],
            ['id' => 2, 'meta' => ['x' => 2]],
        ]);

        $this->assertStringNotContainsString('items[2]{', $out);
        $this->assertStringContainsString('-', $out);
        $this->assertStringContainsString('meta:', $out);
    }

    public function testLegacyModePreservesTabularFlatteningForNestedCells(): void
    {
        $converter = new ToonConverter([
            'min_rows_to_tabular' => 1,
            'compatibility_mode' => 'legacy',
        ]);

        $out = $converter->toToon([
            ['id' => 1, 'meta' => ['x' => 1]],
            ['id' => 2, 'meta' => ['x' => 2]],
        ]);

        $this->assertStringContainsString('items[2]{id,meta}:', $out);
        $this->assertStringContainsString('1,x:1', $out);
    }

    public function testLegacyModePreservesOriginalTableFieldCasing(): void
    {
        $converter = new ToonConverter([
            'min_rows_to_tabular' => 1,
            'compatibility_mode' => 'legacy',
        ]);

        $out = $converter->toToon([
            ['userId' => 1, 'displayName' => 'Alice'],
            ['userId' => 2, 'displayName' => 'Bob'],
        ]);

        $this->assertStringContainsString('items[2]{userId,displayName}:', $out);
    }

    public function testLegacyModePreservesNestedInlineArrayKeyCasing(): void
    {
        $converter = new ToonConverter([
            'min_rows_to_tabular' => 1,
            'compatibility_mode' => 'legacy',
        ]);

        $out = $converter->toToon([
            ['id' => 1, 'meta' => ['createdAt' => '2026-03-21']],
            ['id' => 2, 'meta' => ['createdAt' => '2026-03-22']],
        ]);

        $this->assertStringContainsString('createdAt:2026-03-21', $out);
    }

    public function testPipeDelimiterCanBeUsedForTabularOutput(): void
    {
        $converter = new ToonConverter([
            'min_rows_to_tabular' => 1,
            'delimiter' => 'pipe',
            'compatibility_mode' => 'modern',
        ]);

        $out = $converter->toToon([
            ['id' => 1, 'name' => 'Alice'],
            ['id' => 2, 'name' => 'Bob'],
        ]);

        $this->assertStringContainsString('items[2]{id|name}:', $out);
        $this->assertStringContainsString('1|Alice', $out);
    }

    public function testEscapingIsApplied(): void
    {
        $converter = new ToonConverter(['compatibility_mode' => 'modern']);

        $out = $converter->toToon(['x' => "A,B:C\nD"]);

        $this->assertStringContainsString('\\,', $out);
        $this->assertStringContainsString('\\:', $out);
        $this->assertStringContainsString('\\n', $out);
    }

    public function testNullAndBooleanHandling(): void
    {
        $converter = new ToonConverter();

        $out = $converter->toToon([
            'a' => null,
            'b' => false,
        ]);

        $this->assertStringContainsString('a:', $out);
        $this->assertStringContainsString('b: false', $out);
    }
}
