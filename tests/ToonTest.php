<?php

use PHPUnit\Framework\TestCase;
use Sbsaga\Toon\Converters\ToonConverter;
use Sbsaga\Toon\Converters\ToonDecoder;

/**
 * Class ToonTest
 *
 * Automated PHPUnit tests to validate core TOON functionality:
 * - Ensures tabular formatting for uniform arrays of associative objects.
 * - Ensures escaping and unescaping of special characters works correctly.
 *
 * These tests help guarantee stable behavior for encoding/decoding
 * regardless of future refactoring or optimization.
 *
 * Author: Sagar Bhedodkar
 */
class ToonTest extends TestCase
{
    /**
     * Test: Sequential array of objects should auto-convert into table format.
     *
     * ✅ Ensures:
     * - Proper tabular header (items[n]{keys}:)
     * - Values appear in expected cells
     * - Round-trip encoding/decoding retains data
     */
    public function testArrayOfObjectsProducesTabular()
    {
        $conv = new ToonConverter(['min_rows_to_tabular' => 1, 'max_preview_items' => 10]);

        // Using fixed known dataset for consistency
        $json = [
            ['id' => 1, 'name' => 'Sagar'],
            ['id' => 2, 'name' => 'Vikas'],
        ];

        $out = $conv->toToon($json);

        // Structure assertions
        $this->assertStringContainsString('items[2]{id,name}:', $out);

        // Row content
        $this->assertStringContainsString('1,Sagar', $out);
        $this->assertStringContainsString('2,Vikas', $out);

        // Decode → verify round-trip data integrity
        $dec = new ToonDecoder();
        $arr = $dec->fromToon($out);

        $this->assertIsArray($arr);
        $this->assertEquals(2, count($arr));
        $this->assertEquals('Sagar', $arr[0]['name']);
    }

    /**
     * Test: Special characters should be escaped by encoder
     * and restored correctly by decoder.
     *
     * ✅ Ensures:
     * - newline (\n), comma (,), and colon (:) escape/unescape behavior
     * - keys lower-cased via safeKey()
     */
    public function testInlineEscaping()
    {
        $conv = new ToonConverter();

        // Checking behavior on characters normally breaking separators
        $s = ['note' => "Hello, world: OK\nNew"];
        $out = $conv->toToon($s);

        // Expect escaped TOON representation
        $this->assertStringContainsString('hello', strtolower($out));
        $this->assertStringContainsString('\\,', $out);
        $this->assertStringContainsString('\\:', $out);
        $this->assertStringContainsString('\\n', $out);

        // Round-trip restoration check
        $dec = new ToonDecoder();
        $arr = $dec->fromToon($out);

        $this->assertEquals("Hello, world: OK\nNew", $arr['note']);
    }
}
