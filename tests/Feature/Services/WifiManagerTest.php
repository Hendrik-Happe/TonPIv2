<?php

namespace Tests\Feature\Services;

use App\Models\WifiNetwork;
use App\Services\WifiManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Process;
use Tests\TestCase;

class WifiManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_connects_to_a_visible_known_network(): void
    {
        WifiNetwork::query()->create([
            'ssid' => 'Home',
            'password' => 'home-secret',
            'priority' => 50,
            'auto_connect' => true,
        ]);

        Process::fake(function ($process) {
            $command = $process->command;

            if ($command === 'command -v nmcli >/dev/null') {
                return Process::result();
            }

            if ($command === 'nmcli -t -f ACTIVE,SSID dev wifi') {
                return Process::result("no:\n");
            }

            if ($command === 'nmcli -t -f SSID dev wifi list --rescan auto') {
                return Process::result("Cafe\nHome\n");
            }

            if ($command === "nmcli --wait 10 dev wifi connect 'Home' password 'home-secret'") {
                return Process::result();
            }

            if ($command === 'nmcli -t -f NAME connection show --active') {
                return Process::result();
            }

            return Process::result('', 'Unexpected command: '.$command, 1);
        });

        $result = app(WifiManager::class)->ensurePreferredConnectivity();

        $this->assertSame('connected', $result['status']);
        Process::assertRan("nmcli --wait 10 dev wifi connect 'Home' password 'home-secret'");
    }

    public function test_it_switches_to_hotspot_when_no_known_network_is_visible(): void
    {
        config([
            'wifi.hotspot.connection_name' => 'tonpi-hotspot',
            'wifi.hotspot.ssid' => 'TonPI-Setup',
            'wifi.hotspot.password' => 'tonpi-pass-123',
            'wifi.hotspot.interface' => 'wlan0',
        ]);

        WifiNetwork::query()->create([
            'ssid' => 'Home',
            'password' => 'home-secret',
            'priority' => 50,
            'auto_connect' => true,
        ]);

        Process::fake(function ($process) {
            $command = $process->command;

            if ($command === 'command -v nmcli >/dev/null') {
                return Process::result();
            }

            if ($command === 'nmcli -t -f ACTIVE,SSID dev wifi') {
                return Process::result("no:\n");
            }

            if ($command === 'nmcli -t -f SSID dev wifi list --rescan auto') {
                return Process::result("Other\n");
            }

            if ($command === 'nmcli -t -f NAME connection show --active') {
                return Process::result();
            }

            if ($command === "nmcli -t -f NAME connection show 'tonpi-hotspot'") {
                return Process::result('', 'not found', 1);
            }

            if ($command === "nmcli connection add type wifi ifname 'wlan0' con-name 'tonpi-hotspot' autoconnect no ssid 'TonPI-Setup'") {
                return Process::result();
            }

            if ($command === "nmcli connection modify 'tonpi-hotspot' 802-11-wireless.mode ap 802-11-wireless.band bg ipv4.method shared ipv6.method ignore wifi-sec.key-mgmt wpa-psk wifi-sec.psk 'tonpi-pass-123'") {
                return Process::result();
            }

            if ($command === "nmcli connection up 'tonpi-hotspot'") {
                return Process::result();
            }

            return Process::result('', 'Unexpected command: '.$command, 1);
        });

        $result = app(WifiManager::class)->ensurePreferredConnectivity();

        $this->assertSame('hotspot', $result['status']);
        Process::assertRan("nmcli connection up 'tonpi-hotspot'");
    }

    public function test_it_uses_hotspot_when_no_networks_are_saved(): void
    {
        Process::fake(function ($process) {
            $command = $process->command;

            if ($command === 'command -v nmcli >/dev/null') {
                return Process::result();
            }

            if ($command === 'nmcli -t -f NAME connection show --active') {
                return Process::result();
            }

            if ($command === "nmcli -t -f NAME connection show 'tonpi-hotspot'") {
                return Process::result('', 'not found', 1);
            }

            if ($command === "nmcli connection add type wifi ifname 'wlan0' con-name 'tonpi-hotspot' autoconnect no ssid 'TonPI-Setup'") {
                return Process::result();
            }

            if ($command === "nmcli connection modify 'tonpi-hotspot' 802-11-wireless.mode ap 802-11-wireless.band bg ipv4.method shared ipv6.method ignore wifi-sec.key-mgmt wpa-psk wifi-sec.psk 'tonpi-setup-123'") {
                return Process::result();
            }

            if ($command === "nmcli connection up 'tonpi-hotspot'") {
                return Process::result();
            }

            return Process::result('', 'Unexpected command: '.$command, 1);
        });

        $result = app(WifiManager::class)->ensurePreferredConnectivity();

        $this->assertSame('hotspot', $result['status']);
        Process::assertRan("nmcli connection up 'tonpi-hotspot'");
    }
}
