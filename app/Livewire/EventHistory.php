<?php

namespace App\Livewire;

use App\Models\PlaybackEvent;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Event History')]
class EventHistory extends Component
{
    public string $source = 'all';

    public string $action = 'all';

    public string $search = '';

    #[Computed]
    public function sourceOptions(): array
    {
        return PlaybackEvent::query()
            ->select('source')
            ->distinct()
            ->orderBy('source')
            ->pluck('source')
            ->all();
    }

    #[Computed]
    public function actionOptions(): array
    {
        return PlaybackEvent::query()
            ->select('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action')
            ->all();
    }

    #[Computed]
    public function events()
    {
        return PlaybackEvent::query()
            ->with(['playlist', 'track'])
            ->when($this->source !== 'all', fn ($query) => $query->where('source', $this->source))
            ->when($this->action !== 'all', fn ($query) => $query->where('action', $this->action))
            ->when($this->search !== '', function ($query) {
                $search = '%'.$this->search.'%';

                $query->where(function ($innerQuery) use ($search) {
                    $innerQuery->where('rfid_uid', 'like', $search)
                        ->orWhere('trigger', 'like', $search)
                        ->orWhere('initiated_by', 'like', $search)
                        ->orWhereHas('playlist', fn ($playlistQuery) => $playlistQuery->where('name', 'like', $search));
                });
            })
            ->latest()
            ->limit(150)
            ->get();
    }

    public function render()
    {
        return view('livewire.event-history');
    }
}
