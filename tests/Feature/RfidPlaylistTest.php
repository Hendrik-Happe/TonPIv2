<?php

namespace Tests\Feature;

use App\Jobs\PlayTrack;
use App\Livewire\Playlists\Create;
use App\Models\Playlist;
use App\Models\Track;
use App\Models\User;
use App\Services\PlayerManager;
use App\Services\RfidPlaylistPlayer;
use App\Services\RfidReader;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Tests\TestCase;

class RfidPlaylistTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();
    }

    public function test_scanned_rfid_uid_starts_the_mapped_playlist(): void
    {
        $playlist = Playlist::factory()
            ->has(Track::factory()->count(2))
            ->create([
                'name' => 'RFID Playlist',
                'rfid_uid' => '04A1B2C3D4',
            ]);

        $startedPlaylist = app(RfidPlaylistPlayer::class)->playForUid('04 A1 B2 C3 D4');

        $this->assertNotNull($startedPlaylist);
        $this->assertSame($playlist->id, $startedPlaylist->id);
        $this->assertEquals($playlist->id, app(PlayerManager::class)->getState()->current_playlist_id);
        Queue::assertPushed(PlayTrack::class);
    }

    public function test_rfid_start_applies_playlist_volume_profile_when_defined(): void
    {
        $playlist = Playlist::factory()
            ->has(Track::factory()->count(1))
            ->create([
                'rfid_uid' => 'A1B2C3D4',
                'volume_profile' => 35,
            ]);

        app(PlayerManager::class)->setVolume(80);

        $startedPlaylist = app(RfidPlaylistPlayer::class)->playForUid('A1 B2 C3 D4');

        $this->assertNotNull($startedPlaylist);
        $this->assertSame($playlist->id, $startedPlaylist->id);
        $this->assertSame(35, app(PlayerManager::class)->getState()->volume_percentage);
    }

    public function test_rfid_start_keeps_current_volume_when_playlist_has_no_profile(): void
    {
        $playlist = Playlist::factory()
            ->has(Track::factory()->count(1))
            ->create([
                'rfid_uid' => 'B1C2D3E4',
                'volume_profile' => null,
            ]);

        app(PlayerManager::class)->setVolume(62);

        $startedPlaylist = app(RfidPlaylistPlayer::class)->playForUid('B1 C2 D3 E4');

        $this->assertNotNull($startedPlaylist);
        $this->assertSame($playlist->id, $startedPlaylist->id);
        $this->assertSame(62, app(PlayerManager::class)->getState()->volume_percentage);
    }

    public function test_listener_command_plays_playlist_for_scanned_uid(): void
    {
        $playlist = Playlist::factory()
            ->has(Track::factory()->count(1))
            ->create([
                'name' => 'Command Playlist',
                'rfid_uid' => 'ABCD1234',
            ]);

        $reader = $this->mock(RfidReader::class);
        $reader->shouldReceive('listen')
            ->once()
            ->withArgs(function ($callback) {
                $callback('present', 'AB CD 12 34');

                return true;
            });

        $this->artisan('rfid:listen')
            ->expectsOutput('Listening for RFID chip scans...')
            ->expectsOutput('Started playlist "Command Playlist" for RFID chip ABCD1234.')
            ->assertSuccessful();

        $state = app(PlayerManager::class)->getState();
        $this->assertSame('playing', $state->status);
        $this->assertTrue((bool) $state->rfid_chip_present);
        $this->assertSame('ABCD1234', $state->present_rfid_uid);

        $this->assertDatabaseHas('playback_events', [
            'action' => 'started',
            'source' => 'rfid',
            'playlist_id' => $playlist->id,
            'rfid_uid' => 'ABCD1234',
            'trigger' => 'present',
        ]);
    }

    public function test_listener_command_pauses_when_chip_is_removed(): void
    {
        $playlist = Playlist::factory()
            ->has(Track::factory()->count(1))
            ->create([
                'name' => 'Pause On Remove Playlist',
                'rfid_uid' => 'DEADBEAF',
            ]);

        $reader = $this->mock(RfidReader::class);
        $reader->shouldReceive('listen')
            ->once()
            ->withArgs(function ($callback) {
                $callback('present', 'DE AD BE AF');
                $callback('removed', 'DE AD BE AF');

                return true;
            });

        $this->artisan('rfid:listen')
            ->expectsOutput('Listening for RFID chip scans...')
            ->expectsOutput('Started playlist "Pause On Remove Playlist" for RFID chip DEADBEAF.')
            ->expectsOutput('Paused playback because RFID chip DEADBEAF was removed.')
            ->assertSuccessful();

        $state = app(PlayerManager::class)->getState();
        $this->assertSame('paused', $state->status);
        $this->assertFalse((bool) $state->rfid_chip_present);
        $this->assertNull($state->present_rfid_uid);

        $this->assertDatabaseHas('playback_events', [
            'action' => 'started',
            'source' => 'rfid',
            'playlist_id' => $playlist->id,
            'rfid_uid' => 'DEADBEAF',
            'trigger' => 'present',
        ]);

        $this->assertDatabaseHas('playback_events', [
            'action' => 'paused',
            'source' => 'rfid',
            'rfid_uid' => 'DEADBEAF',
            'trigger' => 'removed',
        ]);
    }

    public function test_listener_command_resumes_when_same_chip_is_represented_after_removal(): void
    {
        $playlist = Playlist::factory()
            ->has(Track::factory()->count(2))
            ->create([
                'name' => 'Resume On Re-Scan Playlist',
                'rfid_uid' => 'C253032FBD',
            ]);

        $reader = $this->mock(RfidReader::class);
        $reader->shouldReceive('listen')
            ->once()
            ->withArgs(function ($callback) {
                $callback('present', 'C2 53 03 2F BD');
                $callback('removed', 'C2 53 03 2F BD');
                $callback('present', 'C2 53 03 2F BD');

                return true;
            });

        $this->artisan('rfid:listen')
            ->expectsOutput('Listening for RFID chip scans...')
            ->expectsOutput('Started playlist "Resume On Re-Scan Playlist" for RFID chip C253032FBD.')
            ->expectsOutput('Paused playback because RFID chip C253032FBD was removed.')
            ->expectsOutput('Resumed playback for RFID chip C253032FBD.')
            ->assertSuccessful();

        $state = app(PlayerManager::class)->getState();
        $this->assertSame('playing', $state->status);
        $this->assertSame($playlist->id, $state->current_playlist_id);

        $this->assertDatabaseHas('playback_events', [
            'action' => 'started',
            'source' => 'rfid',
            'playlist_id' => $playlist->id,
            'rfid_uid' => 'C253032FBD',
            'trigger' => 'present',
        ]);

        $this->assertDatabaseHas('playback_events', [
            'action' => 'paused',
            'source' => 'rfid',
            'rfid_uid' => 'C253032FBD',
            'trigger' => 'removed',
        ]);

        $this->assertDatabaseHas('playback_events', [
            'action' => 'resumed',
            'source' => 'rfid',
            'playlist_id' => $playlist->id,
            'rfid_uid' => 'C253032FBD',
            'trigger' => 'present',
        ]);
    }

    public function test_listener_command_does_not_restart_when_same_chip_is_presented_while_same_playlist_is_already_playing(): void
    {
        $playlist = Playlist::factory()
            ->has(Track::factory()->count(3))
            ->create([
                'name' => 'Keep Position Playlist',
                'rfid_uid' => 'AA11BB22CC',
            ]);

        $reader = $this->mock(RfidReader::class);
        $reader->shouldReceive('listen')
            ->once()
            ->withArgs(function ($callback) {
                $callback('present', 'AA 11 BB 22 CC');
                $callback('removed', 'AA 11 BB 22 CC');

                // Manually continue playback and advance to track 2 (index 1).
                app(PlayerManager::class)->resume('ui', 'manual');
                app(PlayerManager::class)->next('ui', 'manual');

                // Re-presenting same chip must not restart from track 1.
                $callback('present', 'AA 11 BB 22 CC');

                return true;
            });

        $this->artisan('rfid:listen')
            ->expectsOutput('Listening for RFID chip scans...')
            ->expectsOutput('Started playlist "Keep Position Playlist" for RFID chip AA11BB22CC.')
            ->expectsOutput('Paused playback because RFID chip AA11BB22CC was removed.')
            ->expectsOutput('Playback is already running for RFID chip AA11BB22CC.')
            ->assertSuccessful();

        $state = app(PlayerManager::class)->getState();
        $this->assertSame('playing', $state->status);
        $this->assertSame($playlist->id, $state->current_playlist_id);
        $this->assertSame(1, $state->current_position);
    }

    public function test_create_component_saves_normalized_rfid_uid(): void
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->create('track-1.mp3', 1024, 'audio/mpeg');

        Livewire::actingAs($user)
            ->test(Create::class)
            ->set('name', 'Playlist With Chip')
            ->set('rfidUid', '04 a1-b2 c3:d4')
            ->set('uploadedFiles', [$file])
            ->call('save')
            ->assertRedirect('/playlists');

        $this->assertDatabaseHas('playlists', [
            'name' => 'Playlist With Chip',
            'rfid_uid' => '04A1B2C3D4',
        ]);
    }
}
