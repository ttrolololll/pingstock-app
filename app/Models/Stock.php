<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Stock extends Model
{
    protected $fillable = [
        'symbol',
        'name',
        'currency',
        'exchange_symbol',
    ];
    
    
    protected $dates = [
        'created_at',
        'updated_at',
    
    ];
    
    protected $appends = ['resource_url'];

    public function getResourceUrlAttribute()
    {
        return url('/admin/stocks/'.$this->getKey());
    }

    public function exchange()
    {
        return $this->belongsTo('stock_exchanges', 'exchange_symbol', 'symbol');
    }
}
