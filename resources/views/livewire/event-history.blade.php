<div class="mx-auto max-w-7xl p-4 sm:p-6 lg:p-8" wire:poll.5s>
    <div class="mb-6">
        <h1 class="text-2xl font-bold sm:text-3xl">{{ __('Event History') }}</h1>
        <p class="mt-2 text-base-content/60">{{ __('Shows who or what controlled playback and when.') }}</p>
    </div>

    <div class="card border border-base-300 bg-base-200">
        <div class="card-body gap-4 p-4">
            <div class="grid grid-cols-1 gap-3 md:grid-cols-4">
                <label class="floating-label">
                    <select class="select select-bordered w-full" wire:model.live="source">
                        <option value="all">{{ __('All sources') }}</option>
                        @foreach($this->sourceOptions as $sourceOption)
                            <option value="{{ $sourceOption }}">{{ strtoupper($sourceOption) }}</option>
                        @endforeach
                    </select>
                    <span>{{ __('Source') }}</span>
                </label>

                <label class="floating-label">
                    <select class="select select-bordered w-full" wire:model.live="action">
                        <option value="all">{{ __('All actions') }}</option>
                        @foreach($this->actionOptions as $actionOption)
                            <option value="{{ $actionOption }}">{{ ucfirst($actionOption) }}</option>
                        @endforeach
                    </select>
                    <span>{{ __('Action') }}</span>
                </label>

                <label class="floating-label md:col-span-2">
                    <input
                        type="text"
                        class="input input-bordered w-full"
                        wire:model.live.debounce.300ms="search"
                        placeholder="{{ __('Search by playlist, RFID, trigger or user') }}"
                    />
                    <span>{{ __('Search') }}</span>
                </label>
            </div>

            <div class="overflow-x-auto">
                <table class="table table-zebra table-sm">
                    <thead>
                        <tr>
                            <th>{{ __('Time') }}</th>
                            <th>{{ __('Action') }}</th>
                            <th>{{ __('Source') }}</th>
                            <th>{{ __('Playlist') }}</th>
                            <th>{{ __('Track') }}</th>
                            <th>{{ __('RFID') }}</th>
                            <th>{{ __('Trigger') }}</th>
                            <th>{{ __('Initiated by') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($this->events as $event)
                            <tr>
                                <td class="whitespace-nowrap text-xs">{{ $event->created_at?->format('Y-m-d H:i:s') }}</td>
                                <td><span class="badge badge-outline">{{ ucfirst($event->action) }}</span></td>
                                <td>{{ strtoupper($event->source) }}</td>
                                <td>{{ $event->playlist?->name ?? '–' }}</td>
                                <td>{{ $event->track?->title ?? '–' }}</td>
                                <td>{{ $event->rfid_uid ?: '–' }}</td>
                                <td>{{ $event->trigger ?: '–' }}</td>
                                <td>{{ $event->initiated_by ?: 'system' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-base-content/60">{{ __('No events found.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
