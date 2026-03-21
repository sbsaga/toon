<?php
declare(strict_types=1);

namespace Sbsaga\Toon\Tests\Edge;

use PHPUnit\Framework\TestCase;
use Sbsaga\Toon\Converters\ToonConverter;
use Sbsaga\Toon\Converters\ToonDecoder;

/**
 * Edge-case coverage for nested structures and compatibility switches.
 */
final class EdgeCasesTest extends TestCase
{
    public function testDeepNesting(): void
    {
        $data = ['a' => ['b' => ['c' => ['d' => 1]]]];

        $converter = new ToonConverter();
        $decoder = new ToonDecoder();

        $toon = $converter->toToon($data);
        $out = $decoder->fromToon($toon);

        $this->assertSame($data, $out);
    }

    public function testEmptyArray(): void
    {
        $converter = new ToonConverter();
        $out = $converter->toToon([]);

        $this->assertSame('', $out);
    }

    public function testModernModeDoesNotTruncateTabularOutput(): void
    {
        $rows = [];
        for ($i = 0; $i < 10; $i++) {
            $rows[] = ['id' => $i];
        }

        $converter = new ToonConverter([
            'min_rows_to_tabular' => 1,
            'max_preview_items' => 3,
            'compatibility_mode' => 'modern',
        ]);

        $out = $converter->toToon($rows);

        $this->assertStringContainsString('items[10]{id}:', $out);
        $this->assertSame(11, count(explode("\n", trim($out))));
    }

    public function testLegacyModeStillHonorsPreviewLimit(): void
    {
        $rows = [];
        for ($i = 0; $i < 10; $i++) {
            $rows[] = ['id' => $i];
        }

        $converter = new ToonConverter([
            'min_rows_to_tabular' => 1,
            'max_preview_items' => 3,
            'compatibility_mode' => 'legacy',
        ]);

        $out = $converter->toToon($rows);

        $this->assertStringContainsString('items[10]{id}:', $out);
        $this->assertSame(4, count(explode("\n", trim($out))));
    }
}
