<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlaybackEvent extends Model
{
    protected $fillable = [
        'action',
        'source',
        'playlist_id',
        'track_id',
        'rfid_uid',
        'trigger',
        'initiated_by',
        'context',
    ];

    protected function casts(): array
    {
        return [
            'context' => 'array',
        ];
    }

    public function playlist(): BelongsTo
    {
        return $this->belongsTo(Playlist::class);
    }

    public function track(): BelongsTo
    {
        return $this->belongsTo(Track::class);
    }
}
