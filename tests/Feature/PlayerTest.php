<?php

namespace Tests\Feature;

use App\Jobs\PlayTrack;
use App\Models\PlayerState;
use App\Models\Playlist;
use App\Models\Track;
use App\Models\User;
use App\Services\PlayerManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Tests\TestCase;

class PlayerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Fake the queue so jobs don't actually execute
        Queue::fake();

        // Create test data
        $this->playlist = Playlist::factory()
            ->has(Track::factory()->count(3))
            ->create(['name' => 'Test Playlist']);
    }

    public function test_player_can_start_playlist(): void
    {
        $playerManager = app(PlayerManager::class);

        $playerManager->playPlaylist($this->playlist);

        Queue::assertPushed(PlayTrack::class);
        $state = $playerManager->getState();
        $this->assertEquals($this->playlist->id, $state->current_playlist_id);
        $this->assertEquals(0, $state->current_position);
    }

    public function test_player_can_skip_to_next_track(): void
    {
        $playerManager = app(PlayerManager::class);

        $playerManager->playPlaylist($this->playlist);
        $playerManager->next();

        $state = $playerManager->getState();
        $this->assertEquals(1, $state->current_position);
    }

    public function test_player_can_go_to_previous_track(): void
    {
        $playerManager = app(PlayerManager::class);

        $playerManager->playPlaylist($this->playlist);
        $playerManager->next();
        $playerManager->previous();

        $state = $playerManager->getState();
        $this->assertEquals(0, $state->current_position);
    }

    public function test_player_pauses_when_pressing_previous_on_first_track(): void
    {
        $playerManager = app(PlayerManager::class);

        $playerManager->playPlaylist($this->playlist);
        $playerManager->previous();

        $state = $playerManager->getState();
        $this->assertEquals(0, $state->current_position);
        $this->assertEquals('paused', $state->status);
        $this->assertTrue((bool) $state->restart_on_next);
    }

    public function test_player_pauses_at_end_with_no_repeat_when_pressing_next(): void
    {
        $playerManager = app(PlayerManager::class);

        $playerManager->playPlaylist($this->playlist);
        $playerManager->setRepeatMode('none');

        // Skip to last track (position 2)
        $playerManager->next();
        $playerManager->next();

        // Try to skip beyond last track
        $playerManager->next();

        $state = $playerManager->getState();
        $this->assertEquals(2, $state->current_position);
        $this->assertEquals('paused', $state->status);
        $this->assertFalse((bool) $state->restart_on_next);
    }

    public function test_player_restarts_first_track_when_next_after_previous_on_first_track(): void
    {
        $playerManager = app(PlayerManager::class);

        $playerManager->playPlaylist($this->playlist);
        $firstTrackId = $playerManager->getState()->current_track_id;

        // At first track, previous should pause and arm restart-on-next behavior
        $playerManager->previous();

        // Next should restart first track (not jump to second)
        $playerManager->next();

        $state = $playerManager->getState();
        $this->assertEquals(0, $state->current_position);
        $this->assertEquals('playing', $state->status);
        $this->assertEquals($firstTrackId, $state->current_track_id);
        $this->assertFalse((bool) $state->restart_on_next);
    }

    public function test_player_repeats_playlist_with_repeat_all(): void
    {
        $playerManager = app(PlayerManager::class);

        $playerManager->playPlaylist($this->playlist);
        $playerManager->setRepeatMode('all');

        // Skip to last track
        $playerManager->next();
        $playerManager->next();

        // Simulate track finished - should go back to start
        $playerManager->onTrackFinished();

        $state = $playerManager->getState();
        $this->assertEquals(0, $state->current_position);
    }

    public function test_player_repeats_current_track_with_repeat_one(): void
    {
        $playerManager = app(PlayerManager::class);

        $playerManager->playPlaylist($this->playlist);
        $playerManager->setRepeatMode('one');

        $initialTrackId = $playerManager->getState()->current_track_id;

        // Simulate track finished
        $playerManager->onTrackFinished();

        $state = $playerManager->getState();
        $this->assertEquals($initialTrackId, $state->current_track_id);
        $this->assertEquals(0, $state->current_position);
    }

    public function test_player_can_set_volume_percentage(): void
    {
        config([
            'player.player.min_volume' => 20,
            'player.player.max_volume' => 80,
        ]);

        $playerManager = app(PlayerManager::class);
        $playerManager->setVolume(65);

        $state = $playerManager->getState();
        $this->assertEquals(65, $state->volume_percentage);
    }

    public function test_player_volume_is_clamped_between_zero_and_hundred(): void
    {
        $playerManager = app(PlayerManager::class);

        $playerManager->setVolume(999);
        $this->assertEquals(100, $playerManager->getState()->volume_percentage);

        $playerManager->setVolume(-30);
        $this->assertEquals(0, $playerManager->getState()->volume_percentage);
    }

    public function test_changing_playlist_resets_position(): void
    {
        $playerManager = app(PlayerManager::class);

        $playlist2 = Playlist::factory()
            ->has(Track::factory()->count(2))
            ->create(['name' => 'Second Playlist']);

        // Play first playlist and skip ahead
        $playerManager->playPlaylist($this->playlist);
        $playerManager->next();

        // Switch to second playlist
        $playerManager->playPlaylist($playlist2);

        $state = $playerManager->getState();
        $this->assertEquals($playlist2->id, $state->current_playlist_id);
        $this->assertEquals(0, $state->current_position);
    }

    public function test_player_state_is_singleton(): void
    {
        $state1 = PlayerState::global();
        $state2 = PlayerState::global();

        $this->assertEquals($state1->id, $state2->id);
        $this->assertEquals(1, $state1->id);
    }

    public function test_livewire_player_component_renders(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('playlists.index'));

        $response->assertStatus(200);
        $response->assertSeeLivewire('player');
    }

    public function test_player_component_syncs_volume_from_player_state(): void
    {
        $user = User::factory()->create();
        app(PlayerManager::class)->setVolume(41);

        $component = Livewire::actingAs($user)
            ->test('player')
            ->assertSet('volumePercentage', 41);

        // Simulate external update (e.g. GPIO button press)
        app(PlayerManager::class)->setVolume(58);

        $component
            ->call('syncFromPlayerState')
            ->assertSet('volumePercentage', 58);
    }
}
