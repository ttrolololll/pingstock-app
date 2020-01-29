<?php

use Illuminate\Database\Seeder;

class StockAlertRulesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $now = now()->format('Y-m-d H:i:s');

        \Illuminate\Support\Facades\DB::table('stock_alert_rules')->insert([
            [
                'user_id' => 1,
                'stock_symbol' => 'D05.SI',
                'exchange_symbol' => 'SGX',
                'target' => 26,
                'target_type' => 'price',
                'operator' => 'greater',
                'source' => 'wtd',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'user_id' => 1,
                'stock_symbol' => 'F17.SI',
                'exchange_symbol' => 'SGX',
                'target' => 1.389,
                'target_type' => 'price',
                'operator' => 'lesser',
                'source' => 'av',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'user_id' => 1,
                'stock_symbol' => '5MZ.SI',
                'exchange_symbol' => 'SGX',
                'target' => 0.5,
                'target_type' => 'price',
                'operator' => 'greater',
                'source' => 'wtd',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'user_id' => 2,
                'stock_symbol' => 'F17.SI',
                'exchange_symbol' => 'SGX',
                'target' => 1.389,
                'target_type' => 'price',
                'operator' => 'lesser',
                'source' => 'av',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'user_id' => 2,
                'stock_symbol' => '5MZ.SI',
                'exchange_symbol' => 'SGX',
                'target' => 0.5,
                'target_type' => 'price',
                'operator' => 'greater',
                'source' => 'wtd',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }
}
