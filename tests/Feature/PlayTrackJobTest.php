<?php

namespace Tests\Feature;

use App\Jobs\PlayTrack;
use App\Models\PlayerState;
use App\Models\Playlist;
use App\Models\Track;
use App\Services\PlayerManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class PlayTrackJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_skips_stale_playback_jobs_when_track_is_no_longer_current(): void
    {
        $playlist = Playlist::factory()->create();
        $track = Track::factory()->for($playlist)->create([
            'file_path' => '/tmp/does-not-exist-stale.mp3',
        ]);

        PlayerState::global()->update([
            'current_playlist_id' => $playlist->id,
            'current_track_id' => null,
            'status' => 'playing',
        ]);

        $job = new PlayTrack($track);
        $job->handle(Mockery::mock(PlayerManager::class));

        $this->assertTrue(true);
    }

    public function test_it_throws_if_current_job_has_missing_audio_file(): void
    {
        $playlist = Playlist::factory()->create();
        $track = Track::factory()->for($playlist)->create([
            'file_path' => '/tmp/does-not-exist-current.mp3',
        ]);

        PlayerState::global()->update([
            'current_playlist_id' => $playlist->id,
            'current_track_id' => $track->id,
            'status' => 'playing',
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Audio file not found');

        $job = new PlayTrack($track);
        $job->handle(Mockery::mock(PlayerManager::class));
    }
}
