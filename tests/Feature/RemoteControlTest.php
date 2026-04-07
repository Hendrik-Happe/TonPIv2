<?php

namespace Tests\Feature;

use App\Livewire\RemoteControl;
use App\Models\Playlist;
use App\Models\Track;
use App\Models\User;
use App\Services\PlayerManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Tests\TestCase;

class RemoteControlTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();
    }

    public function test_authenticated_user_can_view_remote_control_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('remote-control'));

        $response->assertStatus(200);
        $response->assertSeeLivewire(RemoteControl::class);
        $response->assertSee('Remote Control');
    }

    public function test_guest_can_access_remote_control_page(): void
    {
        $response = $this->get(route('remote-control'));

        $response->assertStatus(200);
        $response->assertSeeLivewire(RemoteControl::class);
    }

    public function test_remote_control_can_start_playlist(): void
    {
        $user = User::factory()->create();
        $playlist = Playlist::factory()
            ->has(Track::factory()->count(2))
            ->create();

        Livewire::actingAs($user)
            ->test(RemoteControl::class)
            ->call('playPlaylist', $playlist->id)
            ->assertSet('selectedPlaylistId', $playlist->id);

        $this->assertSame($playlist->id, app(PlayerManager::class)->getState()->current_playlist_id);
    }

    public function test_remote_control_can_skip_and_previous_track(): void
    {
        $user = User::factory()->create();
        $playlist = Playlist::factory()
            ->has(Track::factory()->count(3))
            ->create();

        $component = Livewire::actingAs($user)
            ->test(RemoteControl::class)
            ->call('playPlaylist', $playlist->id);

        $component->call('next');
        $this->assertSame(1, app(PlayerManager::class)->getState()->current_position);

        $component->call('previous');
        $this->assertSame(0, app(PlayerManager::class)->getState()->current_position);
    }

    public function test_remote_control_can_change_volume(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(RemoteControl::class)
            ->set('volumePercentage', 44);

        $this->assertSame(44, app(PlayerManager::class)->getState()->volume_percentage);
    }

    public function test_remote_control_syncs_volume_from_player_state(): void
    {
        $user = User::factory()->create();
        app(PlayerManager::class)->setVolume(35);

        $component = Livewire::actingAs($user)
            ->test(RemoteControl::class)
            ->assertSet('volumePercentage', 35);

        // Simulate external update (e.g. GPIO button press)
        app(PlayerManager::class)->setVolume(52);

        $component
            ->call('syncFromPlayerState')
            ->assertSet('volumePercentage', 52);
    }

    public function test_remote_control_supports_search_and_pagination_for_playlists(): void
    {
        $user = User::factory()->create();

        for ($i = 1; $i <= 11; $i++) {
            Playlist::factory()->create([
                'name' => sprintf('Remote List %02d', $i),
            ]);
        }

        Playlist::factory()->create(['name' => 'Aardvark Playlist']);
        Playlist::factory()->create(['name' => 'Zulu Playlist']);

        $component = Livewire::actingAs($user)
            ->test(RemoteControl::class)
            ->assertSee('Aardvark Playlist')
            ->assertDontSee('Zulu Playlist');

        $component
            ->call('gotoPage', 2)
            ->assertSee('Zulu Playlist')
            ->set('search', 'Aardvark')
            ->assertSee('Aardvark Playlist')
            ->assertDontSee('Zulu Playlist');
    }
}
