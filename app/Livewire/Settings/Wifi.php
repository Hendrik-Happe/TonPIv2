<?php

namespace App\Livewire\Settings;

use App\Models\WifiNetwork;
use App\Services\WifiManager;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Wi-Fi settings')]
class Wifi extends Component
{
    public string $ssid = '';

    public string $password = '';

    public int $priority = 0;

    public bool $auto_connect = true;

    public ?int $editingNetworkId = null;

    public bool $nmcliAvailable = false;

    public bool $hotspotActive = false;

    public ?string $currentSsid = null;

    public string $lastRunMessage = '';

    public function mount(WifiManager $wifiManager): void
    {
        $this->refreshRuntimeStatus($wifiManager);
    }

    public function saveNetwork(): void
    {
        $validated = $this->validate([
            'ssid' => [
                'required',
                'string',
                'max:191',
                Rule::unique('wifi_networks', 'ssid')->ignore($this->editingNetworkId),
            ],
            'password' => ['nullable', 'string', 'max:191'],
            'priority' => ['required', 'integer', 'between:-100,100'],
            'auto_connect' => ['required', 'boolean'],
        ]);

        if ($this->editingNetworkId !== null) {
            $network = WifiNetwork::query()->findOrFail($this->editingNetworkId);
            $network->update([
                'ssid' => trim((string) $validated['ssid']),
                'password' => $validated['password'] !== '' ? $validated['password'] : null,
                'priority' => (int) $validated['priority'],
                'auto_connect' => (bool) $validated['auto_connect'],
            ]);
        } else {
            WifiNetwork::query()->create([
                'ssid' => trim((string) $validated['ssid']),
                'password' => $validated['password'] !== '' ? $validated['password'] : null,
                'priority' => (int) $validated['priority'],
                'auto_connect' => (bool) $validated['auto_connect'],
            ]);
        }

        $this->resetForm();
        $this->dispatch('wifi-network-saved');
    }

    public function editNetwork(int $networkId): void
    {
        $network = WifiNetwork::query()->findOrFail($networkId);

        $this->editingNetworkId = $network->id;
        $this->ssid = $network->ssid;
        $this->password = '';
        $this->priority = $network->priority;
        $this->auto_connect = $network->auto_connect;
    }

    public function deleteNetwork(int $networkId): void
    {
        WifiNetwork::query()->whereKey($networkId)->delete();

        if ($this->editingNetworkId === $networkId) {
            $this->resetForm();
        }
    }

    public function cancelEdit(): void
    {
        $this->resetForm();
    }

    public function runWifiManager(WifiManager $wifiManager): void
    {
        $result = $wifiManager->ensurePreferredConnectivity();
        $this->lastRunMessage = (string) ($result['message'] ?? 'Done');

        $this->refreshRuntimeStatus($wifiManager);
    }

    public function refreshRuntimeStatus(WifiManager $wifiManager): void
    {
        $status = $wifiManager->getRuntimeStatus();

        $this->nmcliAvailable = (bool) ($status['nmcli_available'] ?? false);
        $this->hotspotActive = (bool) ($status['hotspot_active'] ?? false);
        $this->currentSsid = $status['current_ssid'] ?? null;
    }

    #[Computed]
    public function networks(): Collection
    {
        return WifiNetwork::query()
            ->orderByDesc('priority')
            ->orderBy('ssid')
            ->get();
    }

    public function render()
    {
        return view('livewire.settings.wifi', [
            'hotspotSsid' => config('wifi.hotspot.ssid'),
        ]);
    }

    private function resetForm(): void
    {
        $this->editingNetworkId = null;
        $this->ssid = '';
        $this->password = '';
        $this->priority = 0;
        $this->auto_connect = true;
    }
}
