<?php
declare(strict_types=1);

namespace Sbsaga\Toon\Tests\Converter;

use PHPUnit\Framework\TestCase;
use Sbsaga\Toon\Converters\ToonConverter;

final class ToonConverterTest extends TestCase
{
    public function testAssociativeArrayEncoding(): void
    {
        $conv = new ToonConverter();

        $out = $conv->toToon([
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
        $conv = new ToonConverter();

        $out = $conv->toToon(['a', 'b', 'c']);

        $this->assertSame("a\nb\nc", trim($out));
    }

    public function testUniformArrayBecomesTable(): void
    {
        $conv = new ToonConverter(['min_rows_to_tabular' => 1]);

        $out = $conv->toToon([
            ['id' => 1, 'name' => 'A'],
            ['id' => 2, 'name' => 'B'],
        ]);

        $this->assertStringContainsString('items[2]{id,name}:', $out);
        $this->assertStringContainsString('1,A', $out);
        $this->assertStringContainsString('2,B', $out);
    }

    public function testEscapingIsApplied(): void
    {
        $conv = new ToonConverter();

        $out = $conv->toToon(['x' => "A,B:C\nD"]);

        $this->assertStringContainsString('\\,', $out);
        $this->assertStringContainsString('\\:', $out);
        $this->assertStringContainsString('D', $out);
    }

    public function testNullAndBooleanHandling(): void
    {
        $conv = new ToonConverter();

        $out = $conv->toToon([
            'a' => null,
            'b' => false,
        ]);

        $this->assertStringContainsString('a:', $out);
        $this->assertStringContainsString('b: false', $out);
    }
}
