<?php
declare(strict_types=1);

namespace {
    use Illuminate\Container\Container;

    if (!function_exists('app')) {
        function app(?string $abstract = null): mixed
        {
            $container = Container::getInstance();
            if (!$container instanceof Container) {
                return null;
            }

            if ($abstract === null) {
                return $container;
            }

            if (!$container->bound($abstract)) {
                return null;
            }

            try {
                return $container->make($abstract);
            } catch (\Throwable) {
                return null;
            }
        }
    }

    if (!function_exists('config')) {
        function config(array|string|null $key = null, mixed $default = null): mixed
        {
            $repository = app('config');
            if ($repository === null) {
                if (is_array($key) || $key === null) {
                    return null;
                }

                return $default;
            }

            if (is_array($key)) {
                $repository->set($key);

                return null;
            }

            if ($key === null) {
                return $repository;
            }

            return $repository->get($key, $default);
        }
    }
}

namespace Sbsaga\Toon\Tests\Console {

use Illuminate\Container\Container;
use Illuminate\Filesystem\Filesystem;
use PHPUnit\Framework\TestCase;
use Sbsaga\Toon\Console\ToonConvertCommand;
use Sbsaga\Toon\Converters\ToonConverter;
use Sbsaga\Toon\Toon;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Integration-style tests for the toon:convert command behavior.
 */
final class ToonConvertCommandTest extends TestCase
{
    private Container $container;

    private string $tempDir;

    private Filesystem $filesystem;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'toon-command-tests-' . uniqid('', true);
        mkdir($this->tempDir, 0777, true);

        $this->filesystem = new Filesystem();
        $this->container = new class extends Container {
            public function runningUnitTests(): bool
            {
                return true;
            }
        };
        Container::setInstance($this->container);

        $this->container->instance('config', new class([
            'toon' => [
                'coerce_scalar_types' => true,
                'escape_style' => 'backslash',
                'delimiter' => 'comma',
                'strict_mode' => false,
                'compatibility_mode' => 'legacy',
                'min_rows_to_tabular' => 1,
                'max_preview_items' => 100,
            ],
        ]) {
            private array $items;

            public function __construct(array $items)
            {
                $this->items = $items;
            }

            public function get(string $key, mixed $default = null): mixed
            {
                $segments = explode('.', $key);
                $current = $this->items;

                foreach ($segments as $segment) {
                    if (!is_array($current) || !array_key_exists($segment, $current)) {
                        return $default;
                    }

                    $current = $current[$segment];
                }

                return $current;
            }

            public function set(array|string $key, mixed $value = null): void
            {
                if (is_array($key)) {
                    foreach ($key as $nestedKey => $nestedValue) {
                        $this->set((string) $nestedKey, $nestedValue);
                    }

                    return;
                }

                $segments = explode('.', $key);
                $current = &$this->items;

                foreach ($segments as $segment) {
                    if (!isset($current[$segment]) || !is_array($current[$segment])) {
                        $current[$segment] = [];
                    }

                    $current = &$current[$segment];
                }

                $current = $value;
            }
        });
        $this->container->instance('files', $this->filesystem);
        $this->container->singleton('toon.converter', function ($app) {
            return new ToonConverter($app->make('config')->get('toon', []));
        });
        $this->container->singleton('toon', function ($app) {
            return new Toon($app->make('toon.converter'));
        });
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
        Container::setInstance(null);

        parent::tearDown();
    }

    public function testAutoDetectByJsonExtensionChoosesEncoding(): void
    {
        $jsonPath = $this->writeTempFile('payload.json', '[{"id":1,"name":"Alice"},{"id":2,"name":"Bob"}]');
        $toonPath = $this->tempPath('payload.toon');

        $tester = $this->makeTester();
        $exitCode = $tester->execute([
            'file' => $jsonPath,
            '--output' => $toonPath,
        ]);

        $output = (string) file_get_contents($toonPath);

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('items[2]{id,name}:', $output);
        $this->assertStringContainsString('1,Alice', $output);
    }

    public function testExplicitFromToWorksWithoutEncodeDecodeFlags(): void
    {
        $jsonPath = $this->writeTempFile('payload.data', '[{"id":1,"name":"Alice"},{"id":2,"name":"Bob"}]');
        $toonPath = $this->tempPath('payload.output');

        $tester = $this->makeTester();
        $exitCode = $tester->execute([
            'file' => $jsonPath,
            '--from' => 'json',
            '--to' => 'toon',
            '--output' => $toonPath,
        ]);

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('items[2]{id,name}:', (string) file_get_contents($toonPath));
    }

    public function testDecodeFlagTakesPrecedenceOverConflictingFromToOptions(): void
    {
        $toonPath = $this->writeTempFile(
            'payload.toon',
            "items[2]{id,name}:\n  1,Alice\n  2,Bob"
        );
        $jsonPath = $this->tempPath('payload.json');

        $tester = $this->makeTester();
        $exitCode = $tester->execute([
            'file' => $toonPath,
            '--decode' => true,
            '--from' => 'json',
            '--to' => 'toon',
            '--output' => $jsonPath,
            '--pretty' => true,
        ]);

        $decoded = json_decode((string) file_get_contents($jsonPath), true);

        $this->assertSame(0, $exitCode);
        $this->assertIsArray($decoded);
        $this->assertSame('Alice', $decoded[0][0]['name']);
    }

    public function testStatsAreWrittenToStderrWithoutAffectingStdoutPayload(): void
    {
        $jsonPath = $this->writeTempFile('stats.json', '[{"id":1,"name":"Alice"},{"id":2,"name":"Bob"}]');

        $tester = $this->makeTester();
        $exitCode = $tester->execute(
            [
                'file' => $jsonPath,
                '--encode' => true,
                '--stats' => true,
            ],
            [
                'capture_stderr_separately' => true,
            ]
        );

        $stdout = $tester->getDisplay();
        $stderr = $tester->getErrorOutput();
        $stats = json_decode(trim($stderr), true);

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('items[2]{id,name}:', $stdout);
        $this->assertStringNotContainsString('"direction"', $stdout);
        $this->assertIsArray($stats);
        $this->assertSame('encode', $stats['direction'] ?? null);
        $this->assertArrayHasKey('diff', $stats);
    }

    public function testModeAndDelimiterFlagsOverrideRuntimeEncodingBehavior(): void
    {
        $jsonPath = $this->writeTempFile('mode-delimiter.json', '[{"id":1,"name":"Alice"},{"id":2,"name":"Bob"}]');
        $toonPath = $this->tempPath('mode-delimiter.toon');

        $tester = $this->makeTester();
        $exitCode = $tester->execute([
            'file' => $jsonPath,
            '--encode' => true,
            '--mode' => 'modern',
            '--delimiter' => 'pipe',
            '--output' => $toonPath,
        ]);

        $output = (string) file_get_contents($toonPath);

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('items[2]{id|name}:', $output);
        $this->assertStringContainsString('1|Alice', $output);
    }

    public function testStrictFlagCausesDecodeFailureForInvalidTableCounts(): void
    {
        $toonPath = $this->writeTempFile(
            'strict.toon',
            "items[2]{id,name}:\n  1,Alice"
        );

        $tester = $this->makeTester();
        $exitCode = $tester->execute([
            'file' => $toonPath,
            '--decode' => true,
            '--strict' => true,
        ]);

        $this->assertSame(3, $exitCode);
    }

    private function makeTester(): CommandTester
    {
        $this->container->forgetInstance('toon.converter');
        $this->container->forgetInstance('toon');

        $command = new ToonConvertCommand();
        $command->setLaravel($this->container);

        return new CommandTester($command);
    }

    private function writeTempFile(string $name, string $content): string
    {
        $path = $this->tempPath($name);
        file_put_contents($path, $content);

        return $path;
    }

    private function tempPath(string $name): string
    {
        return $this->tempDir . DIRECTORY_SEPARATOR . $name;
    }

    private function removeDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $this->filesystem->deleteDirectory($path);
    }
}
}
