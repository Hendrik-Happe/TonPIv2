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

    public function test_guest_cannot_access_remote_control_page(): void
    {
        $response = $this->get(route('remote-control'));

        $response->assertRedirect('/login');
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
}
