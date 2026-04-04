<?php

namespace Tests\Feature;

use App\Livewire\Playlists\Create;
use App\Livewire\Playlists\Edit;
use App\Livewire\Playlists\Index;
use App\Models\Playlist;
use App\Models\Track;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class PlaylistManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->user = User::factory()->create();
    }

    public function test_authenticated_user_can_view_playlists_index(): void
    {
        $response = $this->actingAs($this->user)->get('/playlists');

        $response->assertStatus(200);
        $response->assertSeeLivewire(Index::class);
    }

    public function test_authenticated_user_can_view_create_playlist_page(): void
    {
        $response = $this->actingAs($this->user)->get('/playlists/create');

        $response->assertStatus(200);
        $response->assertSeeLivewire(Create::class);
    }

    public function test_guest_cannot_access_playlists(): void
    {
        $response = $this->get('/playlists');

        $response->assertRedirect('/login');
    }

    public function test_can_upload_audio_files(): void
    {
        $file = UploadedFile::fake()->create('test-track.mp3', 1024, 'audio/mpeg');

        Livewire::actingAs($this->user)
            ->test(Create::class)
            ->set('uploadedFiles', [$file])
            ->assertCount('tracks', 1)
            ->assertSet('tracks', function ($tracks) {
                return $tracks[0]['title'] === 'test-track'
                    && $tracks[0]['file_name'] === 'test-track.mp3'
                    && $tracks[0]['track_number'] === 1;
            });
    }

    public function test_can_upload_multiple_audio_files(): void
    {
        $files = [
            UploadedFile::fake()->create('track-1.mp3', 1024, 'audio/mpeg'),
            UploadedFile::fake()->create('track-2.mp3', 1024, 'audio/mpeg'),
            UploadedFile::fake()->create('track-3.wav', 1024, 'audio/wav'),
        ];

        Livewire::actingAs($this->user)
            ->test(Create::class)
            ->set('uploadedFiles', $files)
            ->assertCount('tracks', 3)
            ->assertSet('tracks', function ($tracks) {
                return $tracks[0]['title'] === 'track-1'
                    && $tracks[1]['title'] === 'track-2'
                    && $tracks[2]['title'] === 'track-3';
            });
    }

    public function test_can_remove_track_from_playlist(): void
    {
        $files = [
            UploadedFile::fake()->create('track-1.mp3', 1024, 'audio/mpeg'),
            UploadedFile::fake()->create('track-2.mp3', 1024, 'audio/mpeg'),
        ];

        $component = Livewire::actingAs($this->user)
            ->test(Create::class)
            ->set('uploadedFiles', $files)
            ->assertCount('tracks', 2);

        $firstTrackId = $component->get('tracks')[0]['id'];

        $component
            ->call('removeTrack', $firstTrackId)
            ->assertCount('tracks', 1)
            ->assertSet('tracks', function ($tracks) {
                return $tracks[0]['title'] === 'track-2'
                    && $tracks[0]['track_number'] === 1;
            });
    }

    public function test_track_numbers_update_after_reordering(): void
    {
        $files = [
            UploadedFile::fake()->create('track-1.mp3', 1024, 'audio/mpeg'),
            UploadedFile::fake()->create('track-2.mp3', 1024, 'audio/mpeg'),
            UploadedFile::fake()->create('track-3.mp3', 1024, 'audio/mpeg'),
        ];

        $component = Livewire::actingAs($this->user)
            ->test(Create::class)
            ->set('uploadedFiles', $files);

        $tracks = $component->get('tracks');
        $orderedIds = [
            $tracks[2]['id'],
            $tracks[0]['id'],
            $tracks[1]['id'],
        ];

        $component
            ->call('updateTrackOrder', $orderedIds)
            ->assertSet('tracks', function ($tracks) {
                return $tracks[0]['title'] === 'track-3'
                    && $tracks[0]['track_number'] === 1
                    && $tracks[1]['title'] === 'track-1'
                    && $tracks[1]['track_number'] === 2
                    && $tracks[2]['title'] === 'track-2'
                    && $tracks[2]['track_number'] === 3;
            });
    }

    public function test_can_create_playlist_with_tracks(): void
    {
        $files = [
            UploadedFile::fake()->create('track-1.mp3', 1024, 'audio/mpeg'),
            UploadedFile::fake()->create('track-2.mp3', 1024, 'audio/mpeg'),
        ];

        Livewire::actingAs($this->user)
            ->test(Create::class)
            ->set('name', 'My Test Playlist')
            ->set('uploadedFiles', $files)
            ->call('save')
            ->assertRedirect('/playlists');

        $this->assertDatabaseHas('playlists', [
            'name' => 'My Test Playlist',
        ]);

        $playlist = Playlist::where('name', 'My Test Playlist')->first();
        $this->assertCount(2, $playlist->tracks);

        $this->assertDatabaseHas('tracks', [
            'playlist_id' => $playlist->id,
            'title' => 'track-1',
            'track_number' => 1,
        ]);

        $this->assertDatabaseHas('tracks', [
            'playlist_id' => $playlist->id,
            'title' => 'track-2',
            'track_number' => 2,
        ]);

        // Verify files were stored (check at least 1 file in audio directory)
        $this->assertGreaterThan(0, count(Storage::disk('public')->files('audio')));
    }

    public function test_reordered_tracks_are_saved_in_correct_order(): void
    {
        $files = [
            UploadedFile::fake()->create('track-1.mp3', 1024, 'audio/mpeg'),
            UploadedFile::fake()->create('track-2.mp3', 1024, 'audio/mpeg'),
            UploadedFile::fake()->create('track-3.mp3', 1024, 'audio/mpeg'),
        ];

        $component = Livewire::actingAs($this->user)
            ->test(Create::class)
            ->set('name', 'Reordered Playlist')
            ->set('uploadedFiles', $files);

        // Get track IDs
        $tracks = $component->get('tracks');

        // Reorder: move track-3 to first, track-1 to second, track-2 to third
        $orderedIds = [
            $tracks[2]['id'], // track-3
            $tracks[0]['id'], // track-1
            $tracks[1]['id'], // track-2
        ];

        $component
            ->call('updateTrackOrder', $orderedIds)
            ->call('save')
            ->assertRedirect('/playlists');

        $playlist = Playlist::where('name', 'Reordered Playlist')->first();
        $savedTracks = $playlist->tracks()->orderBy('track_number')->get();

        // Verify the order in database matches the reordered sequence
        $this->assertEquals('track-3', $savedTracks[0]->title);
        $this->assertEquals(1, $savedTracks[0]->track_number);

        $this->assertEquals('track-1', $savedTracks[1]->title);
        $this->assertEquals(2, $savedTracks[1]->track_number);

        $this->assertEquals('track-2', $savedTracks[2]->title);
        $this->assertEquals(3, $savedTracks[2]->track_number);
    }

    public function test_playlist_name_is_required(): void
    {
        $file = UploadedFile::fake()->create('track-1.mp3', 1024, 'audio/mpeg');

        Livewire::actingAs($this->user)
            ->test(Create::class)
            ->set('name', '')
            ->set('uploadedFiles', [$file])
            ->call('save')
            ->assertHasErrors(['name']);
    }

    public function test_playlist_must_have_at_least_one_track(): void
    {
        Livewire::actingAs($this->user)
            ->test(Create::class)
            ->set('name', 'Test Playlist')
            ->call('save')
            ->assertHasErrors(['tracks']);
    }

    public function test_only_audio_files_are_accepted(): void
    {
        $invalidFile = UploadedFile::fake()->create('document.pdf', 1024, 'application/pdf');

        Livewire::actingAs($this->user)
            ->test(Create::class)
            ->set('uploadedFiles', [$invalidFile])
            ->assertHasErrors(['uploadedFiles.*']);
    }

    public function test_files_exceeding_size_limit_are_rejected(): void
    {
        // Create a file larger than 100MB (102400 KB)
        $largeFile = UploadedFile::fake()->create('large-track.mp3', 102401, 'audio/mpeg');

        Livewire::actingAs($this->user)
            ->test(Create::class)
            ->set('uploadedFiles', [$largeFile])
            ->assertHasErrors(['uploadedFiles.*']);
    }

    public function test_can_delete_playlist(): void
    {
        $playlist = Playlist::factory()
            ->has(Track::factory()->count(2))
            ->create(['name' => 'To Delete']);

        Livewire::actingAs($this->user)
            ->test(Index::class)
            ->call('deletePlaylist', $playlist->id);

        $this->assertDatabaseMissing('playlists', [
            'id' => $playlist->id,
        ]);

        // Tracks should be cascade deleted
        $this->assertDatabaseMissing('tracks', [
            'playlist_id' => $playlist->id,
        ]);
    }

    public function test_playlists_are_displayed_in_index(): void
    {
        Playlist::factory()
            ->has(Track::factory()->count(3))
            ->create(['name' => 'Playlist 1']);

        Playlist::factory()
            ->has(Track::factory()->count(5))
            ->create(['name' => 'Playlist 2']);

        Livewire::actingAs($this->user)
            ->test(Index::class)
            ->assertSee('Playlist 1')
            ->assertSee('Playlist 2')
            ->assertSee('3 '.__('tracks'))
            ->assertSee('5 '.__('tracks'));
    }

    public function test_tracks_are_retrieved_in_correct_order(): void
    {
        $playlist = Playlist::create(['name' => 'Ordered Playlist']);

        // Create tracks in reverse order to test sorting
        Track::create([
            'playlist_id' => $playlist->id,
            'title' => 'Third Track',
            'file_path' => '/path/to/third.mp3',
            'track_number' => 3,
            'duration' => 180,
        ]);

        Track::create([
            'playlist_id' => $playlist->id,
            'title' => 'First Track',
            'file_path' => '/path/to/first.mp3',
            'track_number' => 1,
            'duration' => 180,
        ]);

        Track::create([
            'playlist_id' => $playlist->id,
            'title' => 'Second Track',
            'file_path' => '/path/to/second.mp3',
            'track_number' => 2,
            'duration' => 180,
        ]);

        // Retrieve playlist with tracks
        $retrievedPlaylist = Playlist::with('tracks')->find($playlist->id);

        // Assert tracks are ordered by track_number
        $this->assertEquals('First Track', $retrievedPlaylist->tracks[0]->title);
        $this->assertEquals(1, $retrievedPlaylist->tracks[0]->track_number);

        $this->assertEquals('Second Track', $retrievedPlaylist->tracks[1]->title);
        $this->assertEquals(2, $retrievedPlaylist->tracks[1]->track_number);

        $this->assertEquals('Third Track', $retrievedPlaylist->tracks[2]->title);
        $this->assertEquals(3, $retrievedPlaylist->tracks[2]->track_number);
    }

    public function test_authenticated_user_can_view_edit_playlist_page(): void
    {
        $playlist = Playlist::factory()->create();

        $response = $this->actingAs($this->user)->get("/playlists/{$playlist->id}/edit");

        $response->assertStatus(200);
        $response->assertSeeLivewire(Edit::class);
    }

    public function test_guest_cannot_access_edit_playlist_page(): void
    {
        $playlist = Playlist::factory()->create();

        $response = $this->get("/playlists/{$playlist->id}/edit");

        $response->assertRedirect('/login');
    }

    public function test_edit_page_loads_existing_playlist_data(): void
    {
        $playlist = Playlist::factory()->create(['name' => 'Original Playlist']);
        $track1 = Track::factory()->create([
            'playlist_id' => $playlist->id,
            'title' => 'Track 1',
            'track_number' => 1,
        ]);
        $track2 = Track::factory()->create([
            'playlist_id' => $playlist->id,
            'title' => 'Track 2',
            'track_number' => 2,
        ]);

        Livewire::actingAs($this->user)
            ->test(Edit::class, ['playlist' => $playlist])
            ->assertSet('name', 'Original Playlist')
            ->assertCount('tracks', 2)
            ->assertSet('tracks', function ($tracks) use ($track1, $track2) {
                return $tracks[0]['title'] === 'Track 1'
                    && $tracks[0]['id'] === $track1->id
                    && $tracks[1]['title'] === 'Track 2'
                    && $tracks[1]['id'] === $track2->id;
            });
    }

    public function test_can_update_playlist_name(): void
    {
        $playlist = Playlist::factory()->create(['name' => 'Old Name']);
        $track = Track::factory()->create([
            'playlist_id' => $playlist->id,
            'track_number' => 1,
        ]);

        Livewire::actingAs($this->user)
            ->test(Edit::class, ['playlist' => $playlist])
            ->set('name', 'New Name')
            ->call('save')
            ->assertRedirect('/');

        $this->assertDatabaseHas('playlists', [
            'id' => $playlist->id,
            'name' => 'New Name',
        ]);
    }

    public function test_can_add_new_tracks_to_existing_playlist(): void
    {
        $playlist = Playlist::factory()->create(['name' => 'Test Playlist']);
        $existingTrack = Track::factory()->create([
            'playlist_id' => $playlist->id,
            'title' => 'Existing Track',
            'track_number' => 1,
        ]);

        $newFile = UploadedFile::fake()->create('new-track.mp3', 1024, 'audio/mpeg');

        Livewire::actingAs($this->user)
            ->test(Edit::class, ['playlist' => $playlist])
            ->set('uploadedFiles', [$newFile])
            ->assertCount('tracks', 2);
    }

    public function test_can_remove_tracks_from_existing_playlist(): void
    {
        $playlist = Playlist::factory()->create(['name' => 'Test Playlist']);
        $track1 = Track::factory()->create([
            'playlist_id' => $playlist->id,
            'title' => 'Track 1',
            'track_number' => 1,
        ]);
        $track2 = Track::factory()->create([
            'playlist_id' => $playlist->id,
            'title' => 'Track 2',
            'track_number' => 2,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(Edit::class, ['playlist' => $playlist])
            ->assertCount('tracks', 2);

        $component
            ->call('removeTrack', $track1->id)
            ->assertCount('tracks', 1)
            ->assertSet('tracks', function ($tracks) use ($track2) {
                return $tracks[0]['id'] === $track2->id
                    && $tracks[0]['title'] === 'Track 2';
            });
    }

    public function test_edited_playlist_maintains_track_order(): void
    {
        $playlist = Playlist::factory()->create(['name' => 'Test Playlist']);
        $track1 = Track::factory()->create([
            'playlist_id' => $playlist->id,
            'title' => 'Track 1',
            'track_number' => 1,
        ]);
        $track2 = Track::factory()->create([
            'playlist_id' => $playlist->id,
            'title' => 'Track 2',
            'track_number' => 2,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(Edit::class, ['playlist' => $playlist]);

        $tracks = $component->get('tracks');
        $orderedIds = [
            $tracks[1]['id'], // Track 2
            $tracks[0]['id'], // Track 1
        ];

        $newFile = UploadedFile::fake()->create('new-track.mp3', 1024, 'audio/mpeg');

        $component
            ->call('updateTrackOrder', $orderedIds)
            ->set('uploadedFiles', [$newFile])
            ->call('save')
            ->assertRedirect('/');

        $playlist->refresh();
        $savedTracks = $playlist->tracks()->orderBy('track_number')->get();

        $this->assertCount(3, $savedTracks);
        $this->assertEquals('Track 2', $savedTracks[0]->title);
        $this->assertEquals(1, $savedTracks[0]->track_number);
        $this->assertEquals('Track 1', $savedTracks[1]->title);
        $this->assertEquals(2, $savedTracks[1]->track_number);
        $this->assertEquals('new-track', $savedTracks[2]->title);
        $this->assertEquals(3, $savedTracks[2]->track_number);
    }
}
