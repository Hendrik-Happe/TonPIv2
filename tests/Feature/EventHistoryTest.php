<?php

namespace Tests\Feature;

use App\Livewire\EventHistory;
use App\Models\PlaybackEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_event_history_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('event-history'));

        $response->assertStatus(200);
        $response->assertSeeLivewire(EventHistory::class);
        $response->assertSee('Event History');
    }

    public function test_guest_cannot_access_event_history_page(): void
    {
        $response = $this->get(route('event-history'));

        $response->assertRedirect('/login');
    }

    public function test_event_history_page_shows_recorded_events(): void
    {
        $user = User::factory()->create();

        PlaybackEvent::query()->create([
            'action' => 'started',
            'source' => 'rfid',
            'rfid_uid' => 'ABCD1234',
            'trigger' => 'present',
            'initiated_by' => null,
        ]);

        $response = $this->actingAs($user)->get(route('event-history'));

        $response->assertStatus(200);
        $response->assertSee('ABCD1234');
        $response->assertSee('started', false);
        $response->assertSee('rfid', false);
    }
}
