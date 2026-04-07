<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlayerState extends Model
{
    protected $table = 'player_state';

    protected $fillable = [
        'current_playlist_id',
        'current_track_id',
        'current_position',
        'status',
        'repeat_mode',
        'volume_percentage',
        'mplayer_pid',
        'expected_pid',
        'restart_on_next',
    ];

    protected $casts = [
        'current_position' => 'integer',
        'volume_percentage' => 'integer',
        'mplayer_pid' => 'integer',
        'expected_pid' => 'integer',
        'restart_on_next' => 'boolean',
    ];

    public function currentPlaylist()
    {
        return $this->belongsTo(Playlist::class, 'current_playlist_id');
    }

    public function currentTrack()
    {
        return $this->belongsTo(Track::class, 'current_track_id');
    }

    public function isPlaying(): bool
    {
        return $this->status === 'playing';
    }

    public function isPaused(): bool
    {
        return $this->status === 'paused';
    }

    public function isStopped(): bool
    {
        return $this->status === 'stopped';
    }

    public static function global(): self
    {
        return self::firstOrCreate(['id' => 1]);
    }
}
