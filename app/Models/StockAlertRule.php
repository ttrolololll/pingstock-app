<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockAlertRule extends Model
{
    public function exchange()
    {
        return $this->belongsTo(StockExchange::class, 'exchange_symbol', 'symbol');
    }
}
