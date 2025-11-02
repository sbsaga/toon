<?php

namespace Sbsaga\Toon\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class ToonConvertCommand extends Command
{
    protected $signature = 'toon:convert
        {file? : path to input file (json / text) ; if omitted reads STDIN}
        {--d|--decode : decode TOON to JSON}
        {--o|--output= : output file (if omitted prints to stdout)}
        {--p|--pretty : pretty print JSON when decoding}';
    protected $description = 'Encode (or decode) a file/string to/from TOON format.';

    public function handle(Filesystem $fs)
    {
        $file = $this->argument('file');
        $decode = $this->option('decode');
        $output = $this->option('output');
        $pretty = $this->option('pretty');

        $input = null;

        if ($file) {
            if (!$fs->exists($file)) {
                $this->error("File not found: $file");
                return 1;
            }
            $input = $fs->get($file);
        } else {
            // read from STDIN
            $this->info('Reading from STDIN (press Ctrl+D to end):');
            $input = stream_get_contents(STDIN);
            if ($input === false) $input = '';
        }

        if ($decode) {
            $decoded = app('toon')->decode($input);
            $out = $pretty ? json_encode($decoded, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES) : json_encode($decoded);
        } else {
            $out = app('toon')->convert($input);
        }

        if ($output) {
            $fs->put($output, $out);
            $this->info("Saved to $output");
        } else {
            $this->line($out);
        }

        return 0;
    }
}
