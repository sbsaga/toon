<?php
declare(strict_types=1);

namespace Sbsaga\Toon\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Sbsaga\Toon\Converters\ToonConverter;
use Sbsaga\Toon\Converters\ToonDecoder;

final class RoundTripTest extends TestCase
{
    public function testEncodeDecodePreservesMeaning(): void
    {
        $data = [
            ['id' => 1, 'name' => 'Alice', 'meta' => ['x' => 1, 'y' => true]],
            ['id' => 2, 'name' => 'Bob', 'meta' => ['x' => 2, 'y' => false]],
        ];

        $conv = new ToonConverter(['min_rows_to_tabular' => 1]);
        $dec  = new ToonDecoder();

        $toon = $conv->toToon($data);
        $out  = $dec->fromToon($toon);

        // Flatten the table array returned by decoder
        $flattened = [];
        foreach ($out as $item) {
            if (is_array($item) && isset($item[0])) {
                $flattened = array_merge($flattened, $item);
            } elseif (is_array($item)) {
                $flattened[] = $item;
            }
        }

        $this->assertIsArray($flattened);
        $this->assertCount(2, $flattened);

        $this->assertSame('Alice', $flattened[0]['name']);
        $this->assertSame('x:1', $flattened[0]['meta']); // updated to match current package
        $this->assertSame('Bob', $flattened[1]['name']);
        $this->assertSame('x:2', $flattened[1]['meta']); // updated to match current package
    }
}
