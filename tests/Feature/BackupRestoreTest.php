<?php

namespace Tests\Feature;

use App\Livewire\BackupRestore;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BackupRestoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_backup_restore_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('backup-restore'));

        $response->assertStatus(200);
        $response->assertSeeLivewire(BackupRestore::class);
        $response->assertSee('Backup & Restore');
    }

    public function test_guest_cannot_access_backup_restore_page(): void
    {
        $response = $this->get(route('backup-restore'));

        $response->assertRedirect('/login');
    }
}
