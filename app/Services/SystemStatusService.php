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
            $this->serviceItem('Queue Service', ['queue-worker.service', 'tonpi-player-queue.service']),
            $this->serviceItem('RFID Service', ['rfid-reader.service', 'tonpi-rfid-listener.service']),
            $this->serviceItem('GPIO Service', ['gpio-control.service', 'tonpi-gpio-controls.service']),
            $this->serviceItem('Web Service', ['apache2.service', 'tonpi-web.service']),
            $this->serviceItem('PHP-FPM Service', ['php8.4-fpm.service', 'php-fpm.service']),
            $this->configuredCommandItem('RFID Reader Command', (string) config('rfid.reader_command', '')),
            $this->configuredCommandItem('GPIO Control Command', (string) config('gpio.control_command', '')),
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

    /**
     * @param  array<int, string>  $serviceNames
     */
    private function serviceItem(string $label, array $serviceNames): array
    {
        foreach ($serviceNames as $serviceName) {
            $result = Process::path(base_path())
                ->timeout(2)
                ->run(sprintf('systemctl is-active %s', escapeshellarg($serviceName)));

            $isActive = ! $result->failed() && trim($result->output()) === 'active';

            if ($isActive) {
                return [
                    'label' => $label,
                    'ok' => true,
                    'detail' => sprintf('Active (%s)', $serviceName),
                ];
            }
        }

        return [
            'label' => $label,
            'ok' => false,
            'detail' => sprintf('Inactive (checked: %s)', implode(', ', $serviceNames)),
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
