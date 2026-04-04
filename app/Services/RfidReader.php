<?php

namespace App\Services;

use Closure;
use Illuminate\Support\Facades\Process;
use RuntimeException;

class RfidReader
{
    public function listen(Closure $onEvent): void
    {
        $command = trim((string) config('rfid.reader_command'));

        if ($command === '') {
            throw new RuntimeException('RFID_READER_COMMAND is not configured.');
        }

        $handle = popen($command, 'r');

        if (! is_resource($handle)) {
            throw new RuntimeException('Unable to start the configured RFID reader command.');
        }

        try {
            while (! feof($handle)) {
                $line = fgets($handle);

                if ($line === false) {
                    continue;
                }

                $uid = trim($line);

                if ($uid === '') {
                    continue;
                }

                if (str_starts_with($uid, 'PRESENT:')) {
                    $onEvent('present', trim(substr($uid, 8)));

                    continue;
                }

                if (str_starts_with($uid, 'REMOVED:')) {
                    $onEvent('removed', trim(substr($uid, 8)));

                    continue;
                }

                // Backward compatibility for raw UID output.
                $onEvent('present', $uid);
            }
        } finally {
            pclose($handle);
        }
    }

    public function readSingleUid(int $timeoutSeconds = 10): ?string
    {
        $timeout = max(1, $timeoutSeconds);
        $command = trim((string) config('rfid.read_once_command'));

        if ($command === '') {
            $continuousCommand = trim((string) config('rfid.reader_command'));

            if ($continuousCommand === '') {
                throw new RuntimeException('RFID reader command is not configured.');
            }

            $command = sprintf('%s --once --timeout %d', $continuousCommand, $timeout);
        }

        $result = Process::path(base_path())
            ->timeout($timeout + 2)
            ->run($command);

        $output = trim($result->output());

        if ($output === '') {
            return null;
        }

        foreach (array_reverse(preg_split('/\R/', $output) ?: []) as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            if (str_starts_with($line, 'PRESENT:')) {
                return trim(substr($line, 8));
            }

            if (! str_starts_with($line, 'REMOVED:')) {
                return $line;
            }
        }

        if ($result->failed()) {
            throw new RuntimeException('RFID chip could not be read: '.trim($result->errorOutput()));
        }

        return null;
    }
}
