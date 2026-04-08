<?php

namespace App\Services;

use App\Models\WifiNetwork;
use Illuminate\Contracts\Process\ProcessResult;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Process;

class WifiManager
{
    public function ensurePreferredConnectivity(): array
    {
        if (! $this->isNmcliAvailable()) {
            return [
                'status' => 'error',
                'message' => 'nmcli is not available on this system.',
            ];
        }

        $knownNetworks = $this->knownNetworks();

        if ($knownNetworks->isEmpty()) {
            $this->startHotspot();

            return [
                'status' => 'hotspot',
                'message' => 'No saved Wi-Fi network found. Hotspot mode enabled.',
            ];
        }

        $currentSsid = $this->currentSsid();

        if ($currentSsid !== null && $knownNetworks->contains(fn (WifiNetwork $network): bool => $network->ssid === $currentSsid)) {
            $this->stopHotspot();

            return [
                'status' => 'connected',
                'message' => sprintf('Connected to saved Wi-Fi "%s".', $currentSsid),
            ];
        }

        $availableSsids = $this->availableSsids();

        foreach ($knownNetworks as $network) {
            if (! in_array($network->ssid, $availableSsids, true)) {
                continue;
            }

            if ($this->connectTo($network)) {
                $this->stopHotspot();

                return [
                    'status' => 'connected',
                    'message' => sprintf('Connected to "%s".', $network->ssid),
                ];
            }
        }

        $this->startHotspot();

        return [
            'status' => 'hotspot',
            'message' => 'No known Wi-Fi found. Hotspot mode enabled.',
        ];
    }

    public function getRuntimeStatus(): array
    {
        $nmcliAvailable = $this->isNmcliAvailable();

        return [
            'nmcli_available' => $nmcliAvailable,
            'current_ssid' => $nmcliAvailable ? $this->currentSsid() : null,
            'hotspot_active' => $nmcliAvailable ? $this->isHotspotActive() : false,
            'hotspot_ssid' => $this->hotspotSsid(),
        ];
    }

    /**
     * @return array<int, string>
     */
    public function availableSsids(): array
    {
        $result = $this->runCommand('nmcli -t -f SSID dev wifi list --rescan auto', 10);

        if ($result->failed()) {
            return [];
        }

        return collect(preg_split('/\r\n|\r|\n/', $result->output()) ?: [])
            ->map(fn (string $line): string => trim($line))
            ->filter(fn (string $line): bool => $line !== '')
            ->unique()
            ->values()
            ->all();
    }

    public function startHotspot(): bool
    {
        if ($this->isHotspotActive()) {
            return true;
        }

        $connectionName = $this->hotspotConnectionName();
        $ssid = $this->hotspotSsid();
        $password = $this->hotspotPassword();
        $interface = $this->hotspotInterface();

        $connectionExists = ! $this->runCommand(
            sprintf('nmcli -t -f NAME connection show %s', escapeshellarg($connectionName)),
            5,
        )->failed();

        if (! $connectionExists) {
            $createResult = $this->runCommand(
                sprintf(
                    'nmcli connection add type wifi ifname %s con-name %s autoconnect no ssid %s',
                    escapeshellarg($interface),
                    escapeshellarg($connectionName),
                    escapeshellarg($ssid),
                ),
                10,
            );

            if ($createResult->failed()) {
                return false;
            }

            $modifyResult = $this->runCommand(
                sprintf(
                    'nmcli connection modify %s 802-11-wireless.mode ap 802-11-wireless.band bg ipv4.method shared ipv6.method ignore wifi-sec.key-mgmt wpa-psk wifi-sec.psk %s',
                    escapeshellarg($connectionName),
                    escapeshellarg($password),
                ),
                10,
            );

            if ($modifyResult->failed()) {
                return false;
            }
        }

        return ! $this->runCommand(
            sprintf('nmcli connection up %s', escapeshellarg($connectionName)),
            10,
        )->failed();
    }

    public function stopHotspot(): bool
    {
        if (! $this->isHotspotActive()) {
            return true;
        }

        return ! $this->runCommand(
            sprintf('nmcli connection down %s', escapeshellarg($this->hotspotConnectionName())),
            10,
        )->failed();
    }

    public function isHotspotActive(): bool
    {
        $result = $this->runCommand('nmcli -t -f NAME connection show --active', 5);

        if ($result->failed()) {
            return false;
        }

        $activeConnections = collect(preg_split('/\r\n|\r|\n/', $result->output()) ?: [])
            ->map(fn (string $line): string => trim($line))
            ->filter(fn (string $line): bool => $line !== '')
            ->values();

        return $activeConnections->contains($this->hotspotConnectionName());
    }

    public function currentSsid(): ?string
    {
        $result = $this->runCommand('nmcli -t -f ACTIVE,SSID dev wifi', 5);

        if ($result->failed()) {
            return null;
        }

        $lines = preg_split('/\r\n|\r|\n/', $result->output()) ?: [];

        foreach ($lines as $line) {
            if (! str_starts_with($line, 'yes:')) {
                continue;
            }

            $ssid = trim(substr($line, 4));

            return $ssid === '' ? null : $ssid;
        }

        return null;
    }

    private function isNmcliAvailable(): bool
    {
        return ! $this->runCommand('command -v nmcli >/dev/null', 3)->failed();
    }

    /**
     * @return Collection<int, WifiNetwork>
     */
    private function knownNetworks(): Collection
    {
        return WifiNetwork::query()
            ->autoConnect()
            ->orderByDesc('priority')
            ->orderBy('id')
            ->get();
    }

    private function connectTo(WifiNetwork $network): bool
    {
        $ssid = escapeshellarg($network->ssid);

        $result = $network->password !== null && $network->password !== ''
            ? $this->runCommand(sprintf('nmcli --wait 10 dev wifi connect %s password %s', $ssid, escapeshellarg($network->password)), 15)
            : $this->runCommand(sprintf('nmcli --wait 10 dev wifi connect %s', $ssid), 15);

        if (! $result->failed()) {
            return true;
        }

        return ! $this->runCommand(
            sprintf('nmcli --wait 10 connection up %s', $ssid),
            15,
        )->failed();
    }

    private function hotspotConnectionName(): string
    {
        return (string) config('wifi.hotspot.connection_name', 'tonpi-hotspot');
    }

    private function hotspotSsid(): string
    {
        return (string) config('wifi.hotspot.ssid', 'TonPI-Setup');
    }

    private function hotspotPassword(): string
    {
        return (string) config('wifi.hotspot.password', 'tonpi-setup-123');
    }

    private function hotspotInterface(): string
    {
        return (string) config('wifi.hotspot.interface', 'wlan0');
    }

    private function runCommand(string $command, int $timeout): ProcessResult
    {
        return Process::path(base_path())
            ->timeout($timeout)
            ->run($command);
    }
}
