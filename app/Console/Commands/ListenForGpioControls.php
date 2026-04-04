<?php

namespace App\Console\Commands;

use App\Services\GpioControlReader;
use App\Services\PlayerManager;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use RuntimeException;

#[Signature('gpio:listen-controls')]
#[Description('Listen for GPIO button events and control playback')]
class ListenForGpioControls extends Command
{
    public function handle(GpioControlReader $reader, PlayerManager $playerManager): int
    {
        $this->info('Listening for GPIO control events...');

        try {
            $reader->listen(function (string $event) use ($playerManager): void {
                $upperEvent = strtoupper($event);
                $volumeStep = max(1, (int) config('gpio.volume_step', 5));

                switch ($upperEvent) {
                    case 'PREVIOUS':
                        $playerManager->previous('gpio', 'PREVIOUS');
                        $this->info('GPIO action: previous track');
                        break;
                    case 'NEXT':
                        $playerManager->next('gpio', 'NEXT');
                        $this->info('GPIO action: next track');
                        break;
                    case 'VOLUME_DOWN':
                        $currentVolume = (int) ($playerManager->getState()->volume_percentage ?? 100);
                        $playerManager->setVolume(max(0, $currentVolume - $volumeStep));
                        $this->info('GPIO action: volume down');
                        break;
                    case 'VOLUME_UP':
                        $currentVolume = (int) ($playerManager->getState()->volume_percentage ?? 100);
                        $playerManager->setVolume(min(100, $currentVolume + $volumeStep));
                        $this->info('GPIO action: volume up');
                        break;
                    default:
                        $this->warn(sprintf('Unknown GPIO event: %s', $event));
                        break;
                }
            });
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
