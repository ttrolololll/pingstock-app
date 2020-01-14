<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class StockListClean extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stocklist:clean {source} {filepath}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prepares given file for import and removes irrelevant data';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->validateParams();

        switch ($this->argument('source')) {
            case 'eoddata':
                $this->processEoddataFile($this->argument('filepath'));
                break;
        }
    }

    protected function processEoddataFile($filepath)
    {
        $file = fopen($filepath, 'r');
        $outputFile = fopen(Str::random() . '.txt', "w");

        $lines = $this->fileLineIterator($file);

        foreach ($lines as $line) {
            if (!empty($line)) {
                $line = trim($line, "\r\n");
                $parts = explode("\t", $line);

                if (count($parts) != 2) {
                    continue;
                }

                if (preg_match('/w\d/i', $parts[1])) {
                    continue;
                }

                fwrite($outputFile, implode(',', $parts) . "\n");
            }
        }

        fclose($file);
        fclose($outputFile);
    }

    protected function fileLineIterator($file)
    {
        while (($line = fgets($file)) !== false) {
            yield $line;
        }
    }

    protected function validateParams()
    {
        $sourceFilepath = $this->argument('filepath');
        if (!file_exists($sourceFilepath)) {
            $this->error('Source file does not not exists!');
            return;
        }
    }
}
