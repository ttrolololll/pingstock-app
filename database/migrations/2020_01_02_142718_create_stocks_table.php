<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStocksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('stocks', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('symbol');
            $table->string('name');
            $table->string('currency');
            $table->string('exchange_symbol');
            $table->timestamps();

            $table->unique(['symbol', 'exchange_symbol']);
            $table->foreign('exchange_symbol')->references('symbol')->on('stock_exchanges')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('stocks');
    }
}
