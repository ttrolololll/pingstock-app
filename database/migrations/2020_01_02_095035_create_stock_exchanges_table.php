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
            $table->json('trading_daytimes');
            $table->timestamps();

            $table->primary(['symbol']);
        });

        $now = now()->format('Y-m-d H:i:s');

        DB::table('stock_exchanges')->insert([
            [
                'symbol' => 'SGX',
                'name' => 'Singapore Stock Exchange',
                'timezone' => 'Asia/Singapore',
                'trading_daytimes' => '{"mon":{"start":"09:00","end":"17:00"},"tue":{"start":"09:00","end":"17:00"},"wed":{"start":"09:00","end":"17:00"},"thu":{"start":"09:00","end":"17:00"},"fri":{"start":"09:00","end":"17:00"}}',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'symbol' => 'HKEX',
                'name' => 'Hong Kong Stock Exchange',
                'timezone' => 'Asia/Hong_Kong',
                'trading_daytimes' => '{"mon":{"start":"09:30","end":"16:00"},"tue":{"start":"09:30","end":"16:00"},"wed":{"start":"09:30","end":"16:00"},"thu":{"start":"09:30","end":"16:00"},"fri":{"start":"09:30","end":"16:00"}}',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'symbol' => 'NYSE',
                'name' => 'New York Stock Exchange',
                'timezone' => 'America/New_York',
                'trading_daytimes' => '{"mon":{"start":"09:30","end":"16:00"},"tue":{"start":"09:30","end":"16:00"},"wed":{"start":"09:30","end":"16:00"},"thu":{"start":"09:30","end":"16:00"},"fri":{"start":"09:30","end":"16:00"}}',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'symbol' => 'NASDAQ',
                'name' => 'NASDAQ Stock Exchange',
                'timezone' => 'America/New_York',
                'trading_daytimes' => '{"mon":{"start":"09:30","end":"16:00"},"tue":{"start":"09:30","end":"16:00"},"wed":{"start":"09:30","end":"16:00"},"thu":{"start":"09:30","end":"16:00"},"fri":{"start":"09:30","end":"16:00"}}',
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
