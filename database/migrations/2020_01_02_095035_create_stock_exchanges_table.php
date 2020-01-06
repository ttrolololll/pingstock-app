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
            $table->json('trading_times');
            $table->timestamps();

            $table->primary(['symbol']);
        });

        DB::table('stock_exchanges')->insert(
            [
                'symbol' => 'SGX',
                'name' => 'Singapore Stocks Exchange',
                'timezone' => 'Asia/Singapore',
                'trading_times' => '{"mon":{"start":"08:30","end":"17:06"},"tue":{"start":"08:30","end":"17:06"},"wed":{"start":"08:30","end":"17:06"},"thu":{"start":"08:30","end":"17:06"},"fri":{"start":"08:30","end":"17:06"}}',
                'created_at' => now()->format('Y-m-d H:i:s')
            ]
        );
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
