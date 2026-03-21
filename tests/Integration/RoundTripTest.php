<?php
declare(strict_types=1);

namespace Sbsaga\Toon\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Sbsaga\Toon\Converters\ToonConverter;
use Sbsaga\Toon\Converters\ToonDecoder;

/**
 * Integration tests covering encoder and decoder interoperability.
 */
final class RoundTripTest extends TestCase
{
    public function testEncodeDecodePreservesNestedCollections(): void
    {
        $data = [
            [
                'id' => 1,
                'name' => 'Alice',
                'meta' => ['x' => 1, 'y' => true],
                'roles' => ['admin', 'editor'],
            ],
            [
                'id' => 2,
                'name' => 'Bob',
                'meta' => ['x' => 2, 'y' => false],
                'roles' => ['author'],
            ],
        ];

        $converter = new ToonConverter([
            'min_rows_to_tabular' => 1,
            'compatibility_mode' => 'modern',
        ]);
        $decoder = new ToonDecoder(['compatibility_mode' => 'modern']);

        $toon = $converter->toToon($data);
        $out = $decoder->fromToon($toon);

        $this->assertSame($data, $out);
    }

    public function testLegacyDefaultKeepsPreviousTableDecodingShape(): void
    {
        $data = [
            ['id' => 1, 'name' => 'Alice'],
            ['id' => 2, 'name' => 'Bob'],
        ];

        $converter = new ToonConverter(['min_rows_to_tabular' => 1]);
        $decoder = new ToonDecoder();

        $toon = $converter->toToon($data);
        $out = $decoder->fromToon($toon);

        $this->assertSame('Alice', $out[0][0]['name']);
        $this->assertSame('Bob', $out[0][1]['name']);
    }
}
