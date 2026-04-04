<?php

namespace App\Models;

use Database\Factories\PlaylistFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Playlist extends Model
{
    /** @use HasFactory<PlaylistFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'rfid_uid',
    ];

    public function tracks()
    {
        return $this->hasMany(Track::class)->orderBy('track_number');
    }
}
