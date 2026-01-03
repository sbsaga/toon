<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Sbsaga\Toon\Converters\ToonConverter;
use Sbsaga\Toon\Converters\ToonDecoder;

final class EdgeCasesTest extends TestCase
{
    public function testDeepNesting(): void
    {
        $data = ['a' => ['b' => ['c' => ['d' => 1]]]];

        $conv = new ToonConverter();
        $dec  = new ToonDecoder();

        $toon = $conv->toToon($data);
        $out  = $dec->fromToon($toon);

        $this->assertNotEmpty($out);
    }

    public function testEmptyArray(): void
    {
        $conv = new ToonConverter();
        $out = $conv->toToon([]);

        $this->assertSame('', $out);
    }

    public function testTablePreviewLimit(): void
    {
        $rows = [];
        for ($i = 0; $i < 10; $i++) {
            $rows[] = ['id' => $i];
        }

        $conv = new ToonConverter(['min_rows_to_tabular' => 1, 'max_preview_items' => 3]);
        $out = $conv->toToon($rows);

        $this->assertStringContainsString('items[10]{id}:', $out);
    }
}
