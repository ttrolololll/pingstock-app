<?php

namespace App\Console\Commands;

use App\Helpers\FileHelper;
use App\Models\Stock;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class StockListImportCmd extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stocklist:import {source} {filepath} {--exchanges=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Imports stocks from a CSV file';

    public static $supportedExchanges = ['SGX', 'HKEX', 'NYSE', 'NASDAQ'];
    public static $supportedSources = ['eoddata', 'worldtradingdata'];
    public static $exchangeTimezoneMappings = [
        'SGX' => 'Asia/Singapore',
        'HKEX' => 'Asia/Hong_Kong',
        'NYSE' => 'America/New_York',
        'NASDAQ' => 'America/New_York',
    ];

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
        $this->validateParams();

        $source = $this->argument('source');
        $filepath = $this->argument('filepath');

        switch ($source) {
            case 'worldtradingdata':
                $this->processWorldTradingDataCsv($filepath);
                break;
        }

        return;
    }

    /**
     * processWorldTradingDataCsv
     *
     * CSV fields:
     * Symbol, Name, Currency, Stock Exchange Long, Stock Exchange Short, Timezone Name
     *
     * @param $filepath
     */
    protected function processWorldTradingDataCsv($filepath)
    {
        $file = fopen($filepath, 'r');
        $lines = FileHelper::csvFileLineIterator($file);

        foreach ($lines as $line) {
            if (count($line) != 6) {
                continue;
            }

            $symbol = $line[0];
            $name = $line[1];
            $currency = $line[2];
            $exc = $line[4];
            $timezone = array_key_exists($exc,  self::$exchangeTimezoneMappings) ? self::$exchangeTimezoneMappings[$exc] : $line[5];

            $exchangesOption = explode(',', $this->option('exchanges'));
            $exchanges = [];
            foreach ($exchangesOption as $e) {
                $exchanges[] = strtoupper(trim($e, ' '));
            }

            if (!in_array($exc, $exchanges)) {
                continue;
            }

            DB::table('stocks')->updateOrInsert(
                ['symbol' => $symbol],
                [
                    'symbol' => $symbol,
                    'name' => $name,
                    'currency' => $currency,
                    'exchange_symbol' => $exc,
                    'timezone' => $timezone
                ]
            );
        }
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

        $exchanges = $this->option('exchanges');

        if ($exchanges != '') {
            $exchanges = explode(',', $exchanges);

            foreach ($exchanges as $exchange) {
                $ex = trim($exchange, ' ');
                $ex = strtoupper($ex);
                if (!in_array($ex, self::$supportedExchanges)) {
                    $this->error('One of the given exchanges is not supported');
                    $this->info('Supported exchanges: ' . implode(', ', self::$supportedExchanges));
                    return;
                }
            }
        }
    }
}
