<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

class Stock extends Model
{
    use Searchable;

    protected $fillable = [
        'symbol',
        'name',
        'currency',
        'exchange_symbol',
        'timezone'
    ];

    protected $dates = [
        'created_at',
        'updated_at',
    ];

    protected $appends = ['resource_url', 'display_title'];

    public function toSearchableArray()
    {
        $array = [
            'id' => $this->id,
            'symbol' => $this->symbol,
            'name' => $this->name
        ];

        return $array;
    }

    public function getResourceUrlAttribute()
    {
        return url('/admin/stocks/'.$this->getKey());
    }

    public function getDisplayTitleAttribute()
    {
        return "{$this->exchange_symbol}:{$this->symbol} {$this->name}";
    }

    public function exchange()
    {
        return $this->belongsTo(StockExchange::class, 'exchange_symbol', 'symbol');
    }
}
