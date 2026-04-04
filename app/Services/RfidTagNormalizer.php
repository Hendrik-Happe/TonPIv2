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

        return $normalized;
    }
}
