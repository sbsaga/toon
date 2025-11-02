<?php

namespace Sbsaga\Toon\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class ToonConvertCommand extends Command
{
    protected $signature = 'toon:convert {file?} {--o|output=}';
    protected $description = 'Convert JSON/text file to TOON format and print or save to file.';

    public function handle(Filesystem $fs)
    {
        $file = $this->argument('file');

        if (!$file) {
            $this->error('Please provide a file path.');
            return 1;
        }

        if (!$fs->exists($file)) {
            $this->error("File not found: $file");
            return 1;
        }

        $content = $fs->get($file);
        $toon = app('toon')->convert($content);

        if ($out = $this->option('output')) {
            $fs->put($out, $toon);
            $this->info("TOON saved to $out");
        } else {
            $this->line($toon);
        }

        return 0;
    }
}
