<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTelegramIdColumnToStockAlertRulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('stock_alert_rules', function (Blueprint $table) {
            $table->string('alert_telegram')->nullable()->after('alert_email');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('stock_alert_rules', function (Blueprint $table) {
            $table->dropColumn('alert_telegram');
        });
    }
}
