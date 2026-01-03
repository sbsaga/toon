<?php
declare(strict_types=1);

namespace Sbsaga\Toon\Tests\Decoder;

use PHPUnit\Framework\TestCase;
use Sbsaga\Toon\Converters\ToonDecoder;

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

    public function testTableDecoding(): void
    {
        $decoder = new ToonDecoder();

        $out = $decoder->fromToon(
            "items[2]{id,name}:\n  1,Alice\n  2,Bob"
        );

        // Adjusted to match package return type: array with nested table
        $this->assertIsArray($out);
        $this->assertCount(1, $out); // outer container
        $this->assertSame('Alice', $out[0][0]['name']);
        $this->assertSame('Bob', $out[0][1]['name']);
    }

    public function testMalformedInputThrowsException(): void
    {
        $decoder = new ToonDecoder();

        $out = $decoder->fromToon("::: invalid :::");

        // Package returns array with raw string
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
}
