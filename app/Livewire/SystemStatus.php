<?php

namespace App\Livewire;

use App\Services\SystemStatusService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Hardware Status')]
class SystemStatus extends Component
{
    #[Computed]
    public function statusItems(): array
    {
        return app(SystemStatusService::class)->getStatusItems();
    }

    public function render()
    {
        return view('livewire.system-status');
    }
}
