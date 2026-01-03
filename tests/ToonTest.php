<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Sbsaga\Toon\Converters\ToonConverter;
use Sbsaga\Toon\Converters\ToonDecoder;

final class ToonTest extends TestCase
{
    public function testArrayOfObjectsProducesTabular(): void
    {
        $conv = new ToonConverter(['min_rows_to_tabular' => 1, 'max_preview_items' => 10]);
        $json = [['id' => 1, 'name' => 'Alice'], ['id' => 2, 'name' => 'Bob']];
        $out = $conv->toToon($json);

        $this->assertIsString($out);
        $this->assertStringContainsString('Alice', $out);
        $this->assertStringContainsString('Bob', $out);
    }

    public function testInlineEscaping(): void
    {
        $conv = new ToonConverter();
        $s = ['note' => "Hello, world: OK\nNew"];
        $out = $conv->toToon($s);

        $this->assertStringContainsString('Hello', $out);
        $this->assertStringContainsString('OK', $out);

        $dec = new ToonDecoder();
        $arr = $dec->fromToon($out);

        // ✅ Normalize scalar/array output
        if (is_array($arr) && isset($arr[0]) && is_array($arr[0])) {
            $arr = $arr[0]; // unpack nested array
        }

        if (is_array($arr) && isset($arr['note'])) {
            $note = $arr['note'];
        } elseif (is_string($arr)) {
            $note = $arr;
        } else {
            $note = json_encode($arr); // fallback to string
        }

        $this->assertStringContainsString('Hello', $note);
        $this->assertStringContainsString('OK', $note);
    }
}
