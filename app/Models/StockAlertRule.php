<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockAlertRule extends Model
{
    protected $fillable = [
        'first_name', 'last_name', 'email', 'password',
    ];

    public static $operators = ['greater', 'lesser'];

    public function exchange()
    {
        return $this->belongsTo(StockExchange::class, 'exchange_symbol', 'symbol');
    }

}
