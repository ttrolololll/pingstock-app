<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWatchlistItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('watchlist_items', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('watchlist_id');
            $table->string('alert_email');
            $table->string('alert_telegram')->nullable();
            $table->string('stock_symbol');
            $table->string('exchange_symbol');
            $table->string('source');
            $table->unsignedDecimal('reference_target', 9, 4);
            $table->json('circuit_breakers');
            $table->timestamps();
            $table->unique(['watchlist_id', 'stock_symbol']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('watchlist_items');
    }
}
