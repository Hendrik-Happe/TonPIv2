<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class WifiNetwork extends Model
{
    protected $fillable = [
        'ssid',
        'password',
        'priority',
        'auto_connect',
    ];

    protected $casts = [
        'password' => 'encrypted',
        'priority' => 'integer',
        'auto_connect' => 'boolean',
    ];

    public function scopeAutoConnect(Builder $query): Builder
    {
        return $query->where('auto_connect', true);
    }
}
