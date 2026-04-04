<div class="max-w-6xl mx-auto p-4 sm:p-6 lg:p-8" wire:poll.5s>
    <div class="mb-6">
        <h1 class="text-2xl sm:text-3xl font-bold">{{ __('Hardware Status') }}</h1>
        <p class="text-base-content/60 mt-2">{{ __('Live status of audio, RFID, GPIO and system services.') }}</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
        @foreach($this->statusItems as $item)
            <div class="card bg-base-200 border border-base-300">
                <div class="card-body p-4">
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="font-semibold">{{ $item['label'] }}</h2>
                        @if($item['ok'])
                            <span class="badge badge-success badge-sm">{{ __('OK') }}</span>
                        @else
                            <span class="badge badge-error badge-sm">{{ __('Issue') }}</span>
                        @endif
                    </div>
                    <p class="text-sm text-base-content/70">{{ $item['detail'] }}</p>
                </div>
            </div>
        @endforeach
    </div>
</div>