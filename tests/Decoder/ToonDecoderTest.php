<?php
declare(strict_types=1);

namespace Sbsaga\Toon\Tests\Decoder;

use PHPUnit\Framework\TestCase;
use Sbsaga\Toon\Converters\ToonDecoder;
use Sbsaga\Toon\Exceptions\ToonException;

/**
 * Unit tests for TOON decoding behavior.
 */
final class ToonDecoderTest extends TestCase
{
    public function testBasicDecoding(): void
    {
        $decoder = new ToonDecoder();

        $out = $decoder->fromToon(
            "name: Alice\nage: 20\nactive: true"
        );

        $this->assertSame('Alice', $out['name']);
        $this->assertSame(20, $out['age']);
        $this->assertTrue($out['active']);
    }

    public function testLegacyRootTableDecodingStaysWrapped(): void
    {
        $decoder = new ToonDecoder();

        $out = $decoder->fromToon(
            "items[2]{id,name}:\n  1,Alice\n  2,Bob"
        );

        $this->assertIsArray($out);
        $this->assertCount(1, $out);
        $this->assertSame('Alice', $out[0][0]['name']);
        $this->assertSame('Bob', $out[0][1]['name']);
    }

    public function testModernRootTableDecodesToPlainRowList(): void
    {
        $decoder = new ToonDecoder(['compatibility_mode' => 'modern']);

        $out = $decoder->fromToon(
            "items[2]{id,name}:\n  1,Alice\n  2,Bob"
        );

        $this->assertIsArray($out);
        $this->assertCount(2, $out);
        $this->assertSame('Alice', $out[0]['name']);
        $this->assertSame('Bob', $out[1]['name']);
    }

    public function testLegacyModePreservesTableFieldCasingWhenDecoding(): void
    {
        $decoder = new ToonDecoder(['compatibility_mode' => 'legacy']);

        $out = $decoder->fromToon(
            "items[2]{userId,displayName}:\n  1,Alice\n  2,Bob"
        );

        $this->assertSame(1, $out[0][0]['userId']);
        $this->assertSame('Alice', $out[0][0]['displayName']);
    }

    public function testMalformedRootScalarContentFallsBackToListEntry(): void
    {
        $decoder = new ToonDecoder();

        $out = $decoder->fromToon('::: invalid :::');

        $this->assertIsArray($out);
        $this->assertSame('::: invalid :::', $out[0]);
    }

    public function testTypeCoercion(): void
    {
        $decoder = new ToonDecoder();

        $out = $decoder->fromToon("a: true\nb: 10\nc: 1.5");

        $this->assertTrue($out['a']);
        $this->assertSame(10, $out['b']);
        $this->assertSame(1.5, $out['c']);
    }

    public function testPipeDelimitedTableDecoding(): void
    {
        $decoder = new ToonDecoder([
            'delimiter' => 'pipe',
            'compatibility_mode' => 'modern',
        ]);

        $out = $decoder->fromToon(
            "items[2]{id|name}:\n  1|Alice\n  2|Bob"
        );

        $this->assertSame('Alice', $out[0]['name']);
        $this->assertSame('Bob', $out[1]['name']);
    }

    public function testStrictModeValidatesExpectedRowCount(): void
    {
        $decoder = new ToonDecoder(['strict_mode' => true]);

        $this->expectException(ToonException::class);
        $this->expectExceptionMessage('Table row count mismatch');

        $decoder->fromToon("items[2]{id,name}:\n  1,Alice");
    }
}
