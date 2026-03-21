<?php
declare(strict_types=1);

namespace Sbsaga\Toon\Tests;

use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;
use Sbsaga\Toon\Concerns\Toonable;
use Sbsaga\Toon\Converters\ToonConverter;
use Sbsaga\Toon\Converters\ToonDecoder;
use Sbsaga\Toon\Toon;

/**
 * Smoke tests for the high-level TOON service and helper surface.
 */
final class ToonTest extends TestCase
{
    public function testServiceCanEncodeDecodeAndEstimateTokens(): void
    {
        $toon = new Toon(
            new ToonConverter([
                'min_rows_to_tabular' => 1,
                'compatibility_mode' => 'modern',
            ]),
            new ToonDecoder(['compatibility_mode' => 'modern'])
        );

        $encoded = $toon->encode([
            'users' => [
                ['id' => 1, 'name' => 'Alice'],
                ['id' => 2, 'name' => 'Bob'],
            ],
            'team' => 'platform',
        ]);

        $decoded = $toon->decode($encoded);
        $stats = $toon->estimateTokens($encoded);

        $this->assertSame('Alice', $decoded['users'][0]['name']);
        $this->assertSame('Bob', $decoded['users'][1]['name']);
        $this->assertArrayHasKey('tokens_estimate', $stats);
        $this->assertGreaterThan(0, $stats['tokens_estimate']);
    }

    public function testDiffReportsSavingsMetrics(): void
    {
        $toon = new Toon(new ToonConverter(['min_rows_to_tabular' => 1]));

        $diff = $toon->diff([
            'users' => [
                ['id' => 1, 'name' => 'Alice'],
                ['id' => 2, 'name' => 'Bob'],
            ],
        ]);

        $this->assertArrayHasKey('json_chars', $diff);
        $this->assertArrayHasKey('toon_chars', $diff);
        $this->assertArrayHasKey('saved_tokens_estimate', $diff);
        $this->assertGreaterThanOrEqual(0, $diff['saved_chars']);
    }

    public function testGlobalHelpersAndCollectionMacroAreAvailable(): void
    {
        $payload = [
            'team' => 'platform',
            'active' => true,
        ];

        $collectionPayload = [
            ['id' => 1, 'name' => 'Alice'],
            ['id' => 2, 'name' => 'Bob'],
        ];

        $encoded = toon_encode($payload);
        $decoded = toon_decode($encoded);
        $diff = toon_diff($payload);
        $prompt = toon_prompt($payload);
        $validation = toon_validate($encoded);
        $macroEncoded = Collection::make($collectionPayload)->toToon();

        $this->assertSame($payload, $decoded);
        $this->assertSame(toon_encode($collectionPayload), $macroEncoded);
        $this->assertArrayHasKey('savings_percent', $diff);
        $this->assertStringContainsString('```toon', $prompt);
        $this->assertTrue($validation['valid']);
    }

    public function testPromptValidationAndMetadataHelpersAreAvailable(): void
    {
        $toon = new Toon(new ToonConverter());

        $prompt = $toon->promptBlock(['name' => 'Alice']);
        $valid = $toon->validate("name: Alice");
        $invalid = $toon->validate("items[2]{id,name}:\n  1,Alice");

        $this->assertSame("text/toon; charset=utf-8", $toon->contentType());
        $this->assertSame('toon', $toon->fileExtension());
        $this->assertStringStartsWith("```toon\n", $prompt);
        $this->assertTrue($valid['valid']);
        $this->assertFalse($invalid['valid']);
        $this->assertNotNull($invalid['error']);
    }

    public function testToonableTraitProvidesObjectConvenienceMethods(): void
    {
        $dto = new class {
            use Toonable;

            public function toArray(): array
            {
                return [
                    'id' => 7,
                    'name' => 'Synthetic DTO',
                ];
            }
        };

        $toon = $dto->toToon();
        $prompt = $dto->toToonPrompt();

        $this->assertStringContainsString('id: 7', $toon);
        $this->assertStringContainsString('name: Synthetic DTO', $toon);
        $this->assertStringStartsWith("```toon\n", $prompt);
    }
}
