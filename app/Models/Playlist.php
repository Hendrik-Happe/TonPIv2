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
        'cover_path',
        'rfid_uid',
        'volume_profile',
    ];

    protected function casts(): array
    {
        return [
            'volume_profile' => 'integer',
        ];
    }

    public function tracks()
    {
        return $this->hasMany(Track::class)->orderBy('track_number');
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class);
    }
}
