<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Sbsaga\Toon\Converters\ToonConverter;
use Sbsaga\Toon\Toon;

if ($argc < 2) {
    fwrite(STDERR, "Usage: php benchmarks/run.php <fixture.json>\n");
    exit(1);
}

$fixturePath = $argv[1];

if (!is_file($fixturePath)) {
    fwrite(STDERR, "Fixture not found: {$fixturePath}\n");
    exit(1);
}

$decoded = json_decode((string) file_get_contents($fixturePath), true);
if (!is_array($decoded)) {
    fwrite(STDERR, "Fixture must decode to an array payload.\n");
    exit(1);
}

$toon = new Toon(new ToonConverter(['min_rows_to_tabular' => 1]));
$diff = $toon->diff($decoded);

echo json_encode([
    'fixture' => $fixturePath,
    'json_chars' => $diff['json_chars'],
    'toon_chars' => $diff['toon_chars'],
    'saved_chars' => $diff['saved_chars'],
    'savings_percent' => $diff['savings_percent'],
    'json_tokens_estimate' => $diff['json_tokens_estimate'],
    'toon_tokens_estimate' => $diff['toon_tokens_estimate'],
    'saved_tokens_estimate' => $diff['saved_tokens_estimate'],
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
