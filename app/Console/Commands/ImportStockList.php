<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ImportStockList extends Command
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
    protected $description = 'Imports stocks from a JSON file';

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
        $filepath = $this->argument('filepath');

    }
}
