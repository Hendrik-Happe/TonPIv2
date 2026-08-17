<?php

namespace Tests\Feature;

use Tests\TestCase;

class TranslationTest extends TestCase
{
    public function test_pagination_labels_are_translated_in_german(): void
    {
        $this->assertSame('Weiter', __('pagination.next'));
        $this->assertSame('Zurück', __('pagination.previous'));
    }
}
