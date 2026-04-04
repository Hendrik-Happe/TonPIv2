<?php

namespace App\Services;

use Closure;
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
}
