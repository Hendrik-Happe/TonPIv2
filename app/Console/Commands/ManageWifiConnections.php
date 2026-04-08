<?php

namespace App\Console\Commands;

use App\Services\WifiManager;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('wifi:manage {--once : Run a single Wi-Fi check cycle} {--interval=20 : Seconds between cycles in watch mode}')]
#[Description('Manage Wi-Fi auto-connect and hotspot fallback behavior')]
class ManageWifiConnections extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(WifiManager $wifiManager): int
    {
        if ($this->option('once')) {
            $this->runCycle($wifiManager);

            return self::SUCCESS;
        }

        $interval = max(5, (int) $this->option('interval'));

        $this->info(sprintf('Starting Wi-Fi manager loop (interval: %d seconds).', $interval));

        while (true) {
            $this->runCycle($wifiManager);
            sleep($interval);
        }

        return self::SUCCESS;
    }

    private function runCycle(WifiManager $wifiManager): void
    {
        $result = $wifiManager->ensurePreferredConnectivity();
        $line = sprintf('[%s] %s', now()->format('H:i:s'), $result['message']);

        if (($result['status'] ?? null) === 'error') {
            $this->warn($line);

            return;
        }

        $this->line($line);
    }
}
