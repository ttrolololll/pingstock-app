<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateStockExchangesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('stock_exchanges', function (Blueprint $table) {
            $table->string('symbol');
            $table->string('name');
            $table->string('timezone');
            $table->string('trading_start_utc');
            $table->string('trading_end_utc');
            $table->timestamps();

            $table->primary(['symbol']);
        });

        $now = now()->format('Y-m-d H:i:s');

        DB::table('stock_exchanges')->insert([
            [
                'symbol' => 'SGX',
                'name' => 'Singapore Stock Exchange',
                'timezone' => 'Asia/Singapore',
                'trading_start_utc' => '01:00',
                'trading_end_utc' => '09:00',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'symbol' => 'HKEX',
                'name' => 'Hong Kong Stock Exchange',
                'timezone' => 'Asia/Hong_Kong',
                'trading_start_utc' => '01:30',
                'trading_end_utc' => '08:00',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'symbol' => 'NYSE',
                'name' => 'New York Stock Exchange',
                'timezone' => 'America/New_York',
                'trading_start_utc' => '14:30',
                'trading_end_utc' => '21:00',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'symbol' => 'NASDAQ',
                'name' => 'NASDAQ Stock Exchange',
                'timezone' => 'America/New_York',
                'trading_start_utc' => '14:30',
                'trading_end_utc' => '21:00',
                'created_at' => $now,
                'updated_at' => $now,
            ]
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('stock_exchanges');
    }
}
