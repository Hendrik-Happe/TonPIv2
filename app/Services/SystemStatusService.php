<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

class SystemStatusService
{
    public function getStatusItems(): array
    {
        return [
            $this->commandItem('MPlayer', 'mplayer'),
            $this->commandItem('FFprobe', 'ffprobe'),
            $this->spiItem(),
            $this->serviceItem('Queue Service', 'tonpi-player-queue.service'),
            $this->serviceItem('RFID Service', 'tonpi-rfid-listener.service'),
            $this->serviceItem('GPIO Service', 'tonpi-gpio-controls.service'),
            $this->serviceItem('Web Service', 'tonpi-web.service'),
            $this->configuredCommandItem('RFID Reader Command', (string) env('RFID_READER_COMMAND', '')),
            $this->configuredCommandItem('GPIO Control Command', (string) env('GPIO_CONTROL_COMMAND', '')),
            $this->sqliteItem(),
        ];
    }

    private function commandItem(string $label, string $command): array
    {
        $result = Process::path(base_path())
            ->timeout(2)
            ->run(sprintf('command -v %s >/dev/null', escapeshellarg($command)));

        return [
            'label' => $label,
            'ok' => ! $result->failed(),
            'detail' => ! $result->failed() ? 'Installed' : 'Missing',
        ];
    }

    private function serviceItem(string $label, string $serviceName): array
    {
        $result = Process::path(base_path())
            ->timeout(2)
            ->run(sprintf('systemctl is-active %s', escapeshellarg($serviceName)));

        $isActive = ! $result->failed() && trim($result->output()) === 'active';

        return [
            'label' => $label,
            'ok' => $isActive,
            'detail' => $isActive ? 'Active' : 'Inactive',
        ];
    }

    private function spiItem(): array
    {
        $hasSpi = File::exists('/dev/spidev0.0');

        return [
            'label' => 'SPI Device',
            'ok' => $hasSpi,
            'detail' => $hasSpi ? '/dev/spidev0.0 found' : '/dev/spidev0.0 missing',
        ];
    }

    private function configuredCommandItem(string $label, string $command): array
    {
        $isConfigured = trim($command) !== '';

        return [
            'label' => $label,
            'ok' => $isConfigured,
            'detail' => $isConfigured ? 'Configured' : 'Not configured',
        ];
    }

    private function sqliteItem(): array
    {
        $databasePath = (string) config('database.connections.sqlite.database', '');

        if ($databasePath === ':memory:' || $databasePath === '') {
            return [
                'label' => 'SQLite Database',
                'ok' => false,
                'detail' => 'SQLite path is not configured',
            ];
        }

        $resolvedPath = str_starts_with($databasePath, '/') ? $databasePath : base_path($databasePath);

        return [
            'label' => 'SQLite Database',
            'ok' => File::exists($resolvedPath),
            'detail' => File::exists($resolvedPath) ? 'Database file found' : 'Database file missing',
        ];
    }
}
