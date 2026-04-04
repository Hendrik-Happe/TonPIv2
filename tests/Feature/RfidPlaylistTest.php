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

    public function test_listener_command_plays_playlist_for_scanned_uid(): void
    {
        Playlist::factory()
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

        $this->assertSame('playing', app(PlayerManager::class)->getState()->status);
    }

    public function test_listener_command_pauses_when_chip_is_removed(): void
    {
        Playlist::factory()
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

        $this->assertSame('paused', app(PlayerManager::class)->getState()->status);
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
