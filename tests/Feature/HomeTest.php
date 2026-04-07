<?php

namespace Tests\Feature;

use App\Livewire\Home;
use App\Models\Playlist;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class HomeTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_a_successful_response(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
    }

    public function test_home_supports_search_and_pagination_for_playlists(): void
    {
        for ($i = 1; $i <= 11; $i++) {
            Playlist::factory()->create([
                'name' => sprintf('Home List %02d', $i),
                'created_at' => now()->subDays(2),
                'updated_at' => now()->subDays(2),
            ]);
        }

        Playlist::factory()->create([
            'name' => 'Alpha Home',
            'created_at' => now()->subDays(3),
            'updated_at' => now()->subDays(3),
        ]);

        Playlist::factory()->create([
            'name' => 'Zulu Home',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $component = Livewire::test(Home::class)
            ->assertSee('Zulu Home')
            ->assertDontSee('Alpha Home');

        $component
            ->call('gotoPage', 2)
            ->assertSee('Alpha Home')
            ->set('search', 'Zulu')
            ->assertSee('Zulu Home')
            ->assertDontSee('Alpha Home');
    }
}
