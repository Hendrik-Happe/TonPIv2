<?php

namespace App\Services;

use Illuminate\Support\Str;

class RfidTagNormalizer
{
    public function normalize(?string $value): ?string
    {
        $normalized = preg_replace('/[^[:alnum:]]+/u', '', Str::upper(trim((string) $value)));

        if (! is_string($normalized) || $normalized === '') {
            return null;
        }

        // MFRC522 UIDs are emitted as uppercase hexadecimal text (usually 10 chars).
        // Reject arbitrary status/log lines like "CLEANINGUPRFIDREADER".
        if (! preg_match('/^[A-F0-9]{8,20}$/', $normalized)) {
            return null;
        }

        return $normalized;
    }
}
