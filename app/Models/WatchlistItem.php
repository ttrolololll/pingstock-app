<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WatchlistItem extends Model
{

    protected $dates = [
        'created_at',
        'updated_at',
    ];

    public static $defaultCircuitBreakers = [
        'default_level_1' => [
            'threshold' => 7,
            'is_active' => true,
            'mute_till' => null,
            'last_triggered' => null
        ],
        'default_level_2' => [
            'threshold' => 13,
            'is_active' => true,
            'mute_till' => null,
            'last_triggered' => null
        ],
        'default_level_3' => [
            'threshold' => 20,
            'is_active' => true,
            'mute_till' => null,
            'last_triggered' => null
        ],
    ];

    public function stock()
    {
        return $this->belongsTo(Stock::class, 'stock_symbol', 'symbol');
    }

    public function stockAlertRules()
    {
        return $this->hasMany(StockAlertRule::class, 'stock_symbol', 'stock_symbol');
    }

}
