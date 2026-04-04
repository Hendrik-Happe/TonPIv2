<?php

namespace App\Services;

use Closure;
use RuntimeException;

class GpioControlReader
{
    public function listen(Closure $onEvent): void
    {
        $command = trim((string) config('gpio.control_command'));

        if ($command === '') {
            throw new RuntimeException('GPIO_CONTROL_COMMAND is not configured.');
        }

        $handle = popen($command, 'r');

        if (! is_resource($handle)) {
            throw new RuntimeException('Unable to start the configured GPIO control command.');
        }

        try {
            while (! feof($handle)) {
                $line = fgets($handle);

                if ($line === false) {
                    continue;
                }

                $value = trim($line);

                if ($value === '') {
                    continue;
                }

                if (str_starts_with($value, 'EVENT:')) {
                    $onEvent(trim(substr($value, 6)));

                    continue;
                }

                $onEvent($value);
            }
        } finally {
            pclose($handle);
        }
    }
}
