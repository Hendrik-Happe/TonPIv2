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

    public function test_it_uses_ffplay_for_m3u_stream_urls(): void
    {
        $playlist = Playlist::factory()->create();
        $track = Track::factory()->for($playlist)->create([
            'file_path' => 'https://radio.example.com/live.m3u',
        ]);

        $job = new PlayTrack($track);
        $method = new \ReflectionMethod($job, 'buildPlaybackCommand');
        $method->setAccessible(true);

        $command = $method->invoke($job, '/tmp/test_fifo', $track->file_path);

        $this->assertStringContainsString('ffplay', $command);
        $this->assertStringNotContainsString('-playlist', $command);
        $this->assertStringContainsString(escapeshellarg($track->file_path), $command);
    }

    public function test_it_uses_ffplay_for_m3u8_stream_urls(): void
    {
        $playlist = Playlist::factory()->create();
        $track = Track::factory()->for($playlist)->create([
            'file_path' => 'https://radio.example.com/live.m3u8',
        ]);

        $job = new PlayTrack($track);
        $method = new \ReflectionMethod($job, 'buildPlaybackCommand');
        $method->setAccessible(true);

        $command = $method->invoke($job, '/tmp/test_fifo', $track->file_path);

        $this->assertStringContainsString('ffplay', $command);
        $this->assertStringNotContainsString('-playlist', $command);
        $this->assertStringContainsString(escapeshellarg($track->file_path), $command);
    }

    public function test_it_uses_direct_source_mode_for_non_playlist_sources(): void
    {
        $playlist = Playlist::factory()->create();
        $track = Track::factory()->for($playlist)->create([
            'file_path' => '/tmp/local-track.mp3',
        ]);

        $job = new PlayTrack($track);
        $method = new \ReflectionMethod($job, 'buildPlaybackCommand');
        $method->setAccessible(true);

        $command = $method->invoke($job, '/tmp/test_fifo', $track->file_path);

        $this->assertStringContainsString('mplayer', $command);
        $this->assertStringNotContainsString('-playlist', $command);
        $this->assertStringContainsString(escapeshellarg($track->file_path), $command);
    }

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
