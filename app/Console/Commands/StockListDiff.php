<?php

namespace App\Console\Commands;

use App\Helpers\FileHelper;
use App\Models\Stock;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class StockListDiff extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stocklist:diff {source} {filepath}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generates a list of stocks in a given file that are not yet in DB';

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
                $this->processEoddataCsv($this->argument('filepath'));
                break;
        }
    }

    protected function processEoddataCsv($filepath)
    {
        $file = fopen($filepath, 'r');
        $outputFile = fopen('stocklist-diff-' . Str::random() . '.txt', "w");
        $lines = FileHelper::csvFileLineIterator($file);
        $stocks = Stock::cursor();

        foreach ($lines as $line) {
            if (!empty($line)) {
                if (count($line) != 2) {
                    continue;
                }

                $symbol = trim($line[0], ' ');
                $name = trim($line[1], ' ');

                if (preg_match('/w\d/i', $name)) {
                    continue;
                }

                $stock = $stocks->filter(function ($val, $key) use ($symbol) {
                    return $val['symbol'] == $symbol;
                });

                if (count($stock) == 0) {
                    fwrite($outputFile, implode(',', [$symbol, $name]) . "\n");
                }
            }
        }

        fclose($file);
        fclose($outputFile);
    }

    /**
     * validateParams
     */
    protected function validateParams()
    {
        $filepath = $this->argument('filepath');
        if (!file_exists($filepath)) {
            $this->error('File does not not exists!');
            return;
        }
    }
}
