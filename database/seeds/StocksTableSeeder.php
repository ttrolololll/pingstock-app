<?php

use Illuminate\Database\Seeder;

class StocksTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $now = now()->format('Y-m-d H:i:s');

        \Illuminate\Support\Facades\DB::table('stocks')->insert([
            [
                'symbol' => 'D05.SI',
                'name' => 'DBS Group Holdings Ltd',
                'currency' => 'SGD',
                'exchange_symbol' => 'SGX',
                'timezone' => 'Asia/Singapore',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'symbol' => 'F17.SI',
                'name' => 'GuocoLand Limited',
                'currency' => 'SGD',
                'exchange_symbol' => 'SGX',
                'timezone' => 'Asia/Singapore',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'symbol' => '5MZ.SI',
                'name' => 'Kingsmen Creatives Ltd.',
                'currency' => 'SGD',
                'exchange_symbol' => 'SGX',
                'timezone' => 'Asia/Singapore',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }
}
