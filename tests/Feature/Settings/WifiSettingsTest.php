<?php

namespace Tests\Feature\Settings;

use App\Livewire\Settings\Wifi;
use App\Models\User;
use App\Models\WifiNetwork;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class WifiSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_wifi_settings_page_is_displayed(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('wifi.edit'))
            ->assertOk()
            ->assertSee('Wi-Fi');
    }

    public function test_user_can_create_wifi_network(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(Wifi::class)
            ->set('ssid', 'Home-Network')
            ->set('password', 'secret-pass-123')
            ->set('priority', 10)
            ->set('auto_connect', true)
            ->call('saveNetwork')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('wifi_networks', [
            'ssid' => 'Home-Network',
            'priority' => 10,
            'auto_connect' => true,
        ]);

        $network = WifiNetwork::query()->where('ssid', 'Home-Network')->first();

        $this->assertNotNull($network);
        $this->assertSame('secret-pass-123', $network->password);
    }

    public function test_user_can_update_and_delete_wifi_network(): void
    {
        $this->actingAs(User::factory()->create());

        $network = WifiNetwork::query()->create([
            'ssid' => 'Old-SSID',
            'password' => 'old-pass-123',
            'priority' => 0,
            'auto_connect' => true,
        ]);

        Livewire::test(Wifi::class)
            ->call('editNetwork', $network->id)
            ->set('ssid', 'New-SSID')
            ->set('password', 'new-pass-123')
            ->set('priority', 20)
            ->set('auto_connect', false)
            ->call('saveNetwork')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('wifi_networks', [
            'id' => $network->id,
            'ssid' => 'New-SSID',
            'priority' => 20,
            'auto_connect' => false,
        ]);

        Livewire::test(Wifi::class)
            ->call('deleteNetwork', $network->id);

        $this->assertDatabaseMissing('wifi_networks', [
            'id' => $network->id,
        ]);
    }
}
