<?php

namespace Tests\Feature;

use App\Livewire\SystemStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SystemStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_system_status_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('system-status'));

        $response->assertStatus(200);
        $response->assertSeeLivewire(SystemStatus::class);
        $response->assertSee('System Status');
    }

    public function test_guest_cannot_access_system_status_page(): void
    {
        $response = $this->get(route('system-status'));

        $response->assertRedirect('/login');
    }
}
