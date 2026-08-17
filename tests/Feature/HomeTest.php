<?php

namespace Tests\Feature;

use App\Livewire\Home;
use App\Models\Playlist;
use App\Models\Tag;
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

    public function test_home_can_filter_playlists_by_tag(): void
    {
        $sleepTag = Tag::factory()->create(['name' => 'sleep', 'slug' => 'sleep']);
        $kidsTag = Tag::factory()->create(['name' => 'kids', 'slug' => 'kids']);

        $sleepPlaylist = Playlist::factory()->create(['name' => 'Sleep Home']);
        $kidsPlaylist = Playlist::factory()->create(['name' => 'Kids Home']);

        $sleepPlaylist->tags()->attach($sleepTag->id);
        $kidsPlaylist->tags()->attach($kidsTag->id);

        Livewire::test(Home::class)
            ->call('toggleTag', $sleepTag->id)
            ->assertSee('Sleep Home')
            ->assertDontSee('Kids Home');
    }

    public function test_home_can_search_tags_by_name(): void
    {
        $sleepTag = Tag::factory()->create(['name' => 'sleepy time', 'slug' => 'sleepy-time']);
        $relaxTag = Tag::factory()->create(['name' => 'relaxation', 'slug' => 'relaxation']);

        Livewire::test(Home::class)
            ->set('tagSearch', 'sleep')
            ->assertSee('sleepy time')
            ->assertDontSee('relaxation');
    }

    public function test_home_can_toggle_tag(): void
    {
        $sleepTag = Tag::factory()->create(['name' => 'sleep', 'slug' => 'sleep']);
        $sleepPlaylist = Playlist::factory()->create(['name' => 'Sleep Home']);
        $sleepPlaylist->tags()->attach($sleepTag->id);

        Livewire::test(Home::class)
            ->call('toggleTag', $sleepTag->id)
            ->assertSee('Sleep Home')
            ->assertSet('tagFilter', [$sleepTag->id]);
    }

    public function test_home_can_remove_tag(): void
    {
        $sleepTag = Tag::factory()->create(['name' => 'sleep', 'slug' => 'sleep']);
        $sleepPlaylist = Playlist::factory()->create(['name' => 'Sleep Home']);
        $sleepPlaylist->tags()->attach($sleepTag->id);

        Livewire::test(Home::class)
            ->call('toggleTag', $sleepTag->id)
            ->assertSet('tagFilter', [$sleepTag->id])
            ->call('removeTag', $sleepTag->id)
            ->assertSet('tagFilter', []);
    }

    public function test_home_can_filter_by_multiple_tags_and_logic(): void
    {
        $sleepTag = Tag::factory()->create(['name' => 'sleep', 'slug' => 'sleep']);
        $relaxTag = Tag::factory()->create(['name' => 'relax', 'slug' => 'relax']);

        $bothTagsPlaylist = Playlist::factory()->create(['name' => 'Both Tags']);
        $bothTagsPlaylist->tags()->attach([$sleepTag->id, $relaxTag->id]);

        $sleepOnlyPlaylist = Playlist::factory()->create(['name' => 'Sleep Only']);
        $sleepOnlyPlaylist->tags()->attach($sleepTag->id);

        $relaxOnlyPlaylist = Playlist::factory()->create(['name' => 'Relax Only']);
        $relaxOnlyPlaylist->tags()->attach($relaxTag->id);

        Livewire::test(Home::class)
            ->call('toggleTag', $sleepTag->id)
            ->call('toggleTag', $relaxTag->id)
            ->assertSee('Both Tags')
            ->assertDontSee('Sleep Only')
            ->assertDontSee('Relax Only');
    }

    public function test_home_can_clear_tag_filter(): void
    {
        $sleepTag = Tag::factory()->create(['name' => 'sleep', 'slug' => 'sleep']);
        $normalPlaylist = Playlist::factory()->create(['name' => 'Normal Home']);

        Livewire::test(Home::class)
            ->call('toggleTag', $sleepTag->id)
            ->call('clearTagFilter')
            ->assertSet('tagFilter', [])
            ->assertSet('tagSearch', '');
    }
}
