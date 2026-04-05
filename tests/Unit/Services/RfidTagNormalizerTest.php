<?php

namespace Tests\Unit\Services;

use App\Services\RfidTagNormalizer;
use Tests\TestCase;

class RfidTagNormalizerTest extends TestCase
{
    public function test_it_normalizes_valid_hex_uid(): void
    {
        $normalizer = new RfidTagNormalizer;

        $this->assertSame('AB12CD34EF', $normalizer->normalize('ab12-cd34 ef'));
    }

    public function test_it_rejects_non_uid_status_text(): void
    {
        $normalizer = new RfidTagNormalizer;

        $this->assertNull($normalizer->normalize('Cleaning up RFID reader...'));
        $this->assertNull($normalizer->normalize('ABSENT'));
        $this->assertNull($normalizer->normalize('Starting continuous RFID monitoring...'));
    }
}
