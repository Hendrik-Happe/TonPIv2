<?php

namespace Tests\Feature;

use App\Models\Playlist;
use App\Models\Track;
use App\Services\GpioControlReader;
use App\Services\PlayerManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class GpioControlTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();
    }

    public function test_gpio_next_event_skips_to_next_track(): void
    {
        $playlist = Playlist::factory()
            ->has(Track::factory()->count(3))
            ->create();

        app(PlayerManager::class)->playPlaylist($playlist);

        $reader = $this->mock(GpioControlReader::class);
        $reader->shouldReceive('listen')
            ->once()
            ->withArgs(function ($callback) {
                $callback('NEXT');

                return true;
            });

        $this->artisan('gpio:listen-controls')
            ->expectsOutput('Listening for GPIO control events...')
            ->expectsOutput('GPIO action: next track')
            ->assertSuccessful();

        $this->assertSame(1, app(PlayerManager::class)->getState()->current_position);

        $this->assertDatabaseHas('playback_events', [
            'action' => 'next',
            'source' => 'gpio',
            'playlist_id' => $playlist->id,
            'trigger' => 'NEXT',
        ]);
    }

    public function test_gpio_previous_event_goes_to_previous_track(): void
    {
        $playlist = Playlist::factory()
            ->has(Track::factory()->count(3))
            ->create();

        $playerManager = app(PlayerManager::class);
        $playerManager->playPlaylist($playlist);
        $playerManager->next();

        $reader = $this->mock(GpioControlReader::class);
        $reader->shouldReceive('listen')
            ->once()
            ->withArgs(function ($callback) {
                $callback('PREVIOUS');

                return true;
            });

        $this->artisan('gpio:listen-controls')
            ->expectsOutput('Listening for GPIO control events...')
            ->expectsOutput('GPIO action: previous track')
            ->assertSuccessful();

        $this->assertSame(0, app(PlayerManager::class)->getState()->current_position);

        $this->assertDatabaseHas('playback_events', [
            'action' => 'previous',
            'source' => 'gpio',
            'playlist_id' => $playlist->id,
            'trigger' => 'PREVIOUS',
        ]);
    }

    public function test_gpio_volume_up_event_increases_volume(): void
    {
        config(['gpio.volume_step' => 5]);

        $playerManager = app(PlayerManager::class);
        $playerManager->setVolume(40);

        $reader = $this->mock(GpioControlReader::class);
        $reader->shouldReceive('listen')
            ->once()
            ->withArgs(function ($callback) {
                $callback('VOLUME_UP');

                return true;
            });

        $this->artisan('gpio:listen-controls')
            ->expectsOutput('Listening for GPIO control events...')
            ->expectsOutput('GPIO action: volume up')
            ->assertSuccessful();

        $this->assertSame(45, app(PlayerManager::class)->getState()->volume_percentage);
    }

    public function test_gpio_volume_down_event_decreases_volume(): void
    {
        config(['gpio.volume_step' => 5]);

        $playerManager = app(PlayerManager::class);
        $playerManager->setVolume(40);

        $reader = $this->mock(GpioControlReader::class);
        $reader->shouldReceive('listen')
            ->once()
            ->withArgs(function ($callback) {
                $callback('VOLUME_DOWN');

                return true;
            });

        $this->artisan('gpio:listen-controls')
            ->expectsOutput('Listening for GPIO control events...')
            ->expectsOutput('GPIO action: volume down')
            ->assertSuccessful();

        $this->assertSame(35, app(PlayerManager::class)->getState()->volume_percentage);
    }

    public function test_gpio_unknown_event_outputs_warning(): void
    {
        $reader = $this->mock(GpioControlReader::class);
        $reader->shouldReceive('listen')
            ->once()
            ->withArgs(function ($callback) {
                $callback('SOMETHING_ELSE');

                return true;
            });

        $this->artisan('gpio:listen-controls')
            ->expectsOutput('Listening for GPIO control events...')
            ->expectsOutput('Unknown GPIO event: SOMETHING_ELSE')
            ->assertSuccessful();
    }
}
