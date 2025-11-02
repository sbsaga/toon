<?php

use PHPUnit\Framework\TestCase;
use Sbsaga\Toon\Converters\ToonConverter;
use Sbsaga\Toon\Converters\ToonDecoder;

class ToonTest extends TestCase
{
    public function testArrayOfObjectsProducesTabular()
    {
        $conv = new ToonConverter(['min_rows_to_tabular' => 1, 'max_preview_items' => 10]);
        $json = [
            ['id' => 1, 'name' => 'Alice'],
            ['id' => 2, 'name' => 'Bob']
        ];

        $out = $conv->toToon($json);
        $this->assertStringContainsString('items[2]{id,name}:', $out);
        $this->assertStringContainsString('1,Alice', $out);
        $this->assertStringContainsString('2,Bob', $out);

        // decode roundtrip
        $dec = new ToonDecoder();
        $arr = $dec->fromToon($out);
        $this->assertIsArray($arr);
        $this->assertEquals(2, count($arr));
        $this->assertEquals('Alice', $arr[0]['name']);
    }

    public function testInlineEscaping()
    {
        $conv = new ToonConverter();
        $s = ['note' => "Hello, world: OK\nNew"];
        $out = $conv->toToon($s);
        $this->assertStringContainsString('hello', strtolower($out));
        $this->assertStringContainsString('\\,', $out);
        $this->assertStringContainsString('\\:', $out);
        $this->assertStringContainsString('\\n', $out);

        $dec = new ToonDecoder();
        $arr = $dec->fromToon($out);
        $this->assertEquals("Hello, world: OK\nNew", $arr['note']);
    }
}
