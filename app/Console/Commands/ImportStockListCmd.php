<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ImportStockListCmd extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stock:import {filepath}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Imports stocks from a CSV file';

    public static $supportedExchanges = ['SGX', 'HKEX', 'NYSE', 'NASDAQ'];

    /**
     * Create a new command instance.
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
        $filepath = $this->argument('filepath');

        // check if file exists
        if (!file_exists($filepath)) {
            $this->error('File does not not exists!');
            return;
        }

        $csvLines = $this->readCSVFile($filepath);

        foreach ($csvLines as $line) {
            $this->info(implode(',', $line));
        }

        return;
    }

    protected function readCSVFile($filepath)
    {
        $file = fopen($filepath, 'r');
        while (($line = fgetcsv($file)) !== false) {
            yield $line;
        }
        fclose($file);
    }
}
