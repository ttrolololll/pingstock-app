<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ModifyStockAlertRulesTriggeredColumn extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('stock_alert_rules', function (Blueprint $table) {
            $table->timestamp('triggered_at')->nullable()->after('triggered');
        });

        $now = Carbon::now();
        DB::table('stock_alert_rules')
            ->where('triggered', '=', 1)
            ->update(['triggered_at' => $now]);

        Schema::table('stock_alert_rules', function (Blueprint $table) {
            $table->dropColumn('triggered');
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
            $table->boolean('triggered')->default(0)->after('triggered_at');
        });

        DB::table('stock_alert_rules')
            ->whereNotNull('triggered_at')
            ->update(['triggered' => 1]);

        Schema::table('stock_alert_rules', function (Blueprint $table) {
            $table->dropColumn('triggered_at');
        });
    }
}
