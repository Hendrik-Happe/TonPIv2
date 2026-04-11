<div class="max-w-4xl mx-auto p-4 sm:p-6 lg:p-8">
    <div class="mb-6">
        <h1 class="text-2xl sm:text-3xl font-bold">{{ __('Edit Playlist') }}</h1>
        <p class="text-base-content/60 mt-2">{{ __('Update playlist name and tracks') }}</p>
    </div>

    <form wire:submit="save" class="space-y-6">
        <!-- Playlist Name -->
        <div>
            <label class="input">
                <span class="label">{{ __('Playlist Name') }}</span>
                <input 
                    type="text" 
                    wire:model="name" 
                    placeholder="{{ __('Enter playlist name') }}"
                    class="grow"
                    required
                >
            </label>
            @error('name')
                <p class="text-error text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="input">
                <span class="label">{{ __('RFID Chip ID') }}</span>
                <input
                    type="text"
                    wire:model="rfidUid"
                    placeholder="04A1B2C3D4"
                    class="grow"
                >
            </label>
            <div class="mt-2 flex items-center gap-3">
                <button type="button" class="btn btn-outline btn-sm" wire:click="readCurrentRfidUid" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="readCurrentRfidUid">{{ __('Chip jetzt einlesen') }}</span>
                    <span wire:loading wire:target="readCurrentRfidUid" class="loading loading-spinner loading-sm"></span>
                </button>
                @if ($rfidReadFeedback)
                    <span class="text-sm text-base-content/70">{{ $rfidReadFeedback }}</span>
                @endif
            </div>
            <p class="text-base-content/60 text-sm mt-2">{{ __('Optional. When this chip is scanned, this playlist starts automatically.') }}</p>
            @error('rfidUid')
                <p class="text-error text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="input">
                <span class="label">{{ __('Lautstärke-Profil (%)') }}</span>
                <input
                    type="number"
                    min="0"
                    max="100"
                    step="1"
                    wire:model="volumeProfile"
                    placeholder="60"
                    class="grow"
                >
            </label>
            <p class="text-base-content/60 text-sm mt-2">{{ __('Optional. Wird beim RFID-Start dieser Playlist automatisch gesetzt.') }}</p>
            @error('volumeProfile')
                <p class="text-error text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="block mb-2 font-medium">{{ __('Playlist Cover') }}</label>
            <input
                type="file"
                wire:model="coverImage"
                class="file-input file-input-primary w-full"
                accept="image/*"
            >
            @error('coverImage')
                <p class="text-error text-sm mt-1">{{ $message }}</p>
            @enderror

            @if($coverImage)
                <div class="mt-3">
                    <img src="{{ $coverImage->temporaryUrl() }}" alt="Cover preview" class="h-32 w-32 rounded-box bg-base-300/30 object-contain p-1" />
                </div>
            @elseif($playlist->cover_path && ! $removeCoverImage)
                <div class="mt-3 flex items-start gap-3">
                    <img src="{{ asset('storage/'.$playlist->cover_path) }}" alt="Current cover" class="h-32 w-32 rounded-box bg-base-300/30 object-contain p-1" />
                    <button type="button" class="btn btn-sm btn-outline" wire:click="removeCover">
                        {{ __('Remove cover') }}
                    </button>
                </div>
            @endif
        </div>

        <div>
            <label class="input">
                <span class="label">{{ __('Tags') }}</span>
                <input
                    type="text"
                    wire:model="tags"
                    placeholder="sleep, kids, relax"
                    class="grow"
                >
            </label>
            <p class="text-base-content/60 text-sm mt-2">{{ __('Kommagetrennt. Beispiel: sleep, bedtime, calm') }}</p>
            @error('tags')
                <p class="text-error text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <!-- File Upload -->
        <div>
            <label class="block mb-2 font-medium">{{ __('Add New Tracks') }}</label>
            <input 
                type="file" 
                wire:model="uploadedFiles" 
                class="file-input file-input-primary w-full"
                accept="audio/*"
                multiple
            >
            @error('uploadedFiles.*')
                <p class="text-error text-sm mt-1">{{ $message }}</p>
            @enderror
            
            <div wire:loading wire:target="uploadedFiles" class="mt-2">
                <span class="loading loading-spinner loading-sm"></span>
                <span class="ml-2 text-sm">{{ __('Processing files...') }}</span>
            </div>
        </div>

        <div class="bg-base-200 p-4 rounded-lg">
            <h3 class="text-lg font-semibold mb-3">{{ __('Add Web Stream (M3U/M3U8)') }}</h3>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <input
                    type="url"
                    wire:model="streamUrl"
                    placeholder="https://example.com/stream.m3u8"
                    class="input input-bordered md:col-span-2"
                >
                <input
                    type="text"
                    wire:model="streamTitle"
                    placeholder="{{ __('Optional title') }}"
                    class="input input-bordered"
                >
            </div>

            <div class="mt-3">
                <button type="button" class="btn btn-outline btn-sm" wire:click="addStreamTrack">{{ __('Add Stream') }}</button>
            </div>

            @error('streamUrl')
                <p class="text-error text-sm mt-2">{{ $message }}</p>
            @enderror
        </div>

        <!-- Tracks List -->
        @if(count($tracks) > 0)
            <div>
                <label class="block mb-2 font-medium">{{ __('Tracks') }} ({{ count($tracks) }})</label>
                
                <div 
                    x-data="{
                        init() {
                            const el = this.$refs.tracksList;
                            Sortable.create(el, {
                                handle: '[data-sort-handle]',
                                animation: 150,
                                onEnd: (evt) => {
                                    const orderedIds = Array.from(el.children).map(child => child.dataset.sortItem);
                                    $wire.call('updateTrackOrder', orderedIds);
                                }
                            });
                        }
                    }"
                    x-ref="tracksList"
                    class="space-y-2"
                >
                    @foreach($tracks as $track)
                        <div 
                            class="card card-border bg-base-100"
                            data-sort-item="{{ $track['id'] }}"
                        >
                            <div class="card-body p-3 sm:p-4">
                                <div class="flex items-center gap-3">
                                    <!-- Drag Handle -->
                                    <button 
                                        type="button"
                                        data-sort-handle
                                        class="cursor-move text-base-content/40 hover:text-base-content"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"/>
                                        </svg>
                                    </button>

                                    <!-- Track Info -->
                                    <div class="flex-1 min-w-0">
                                        <div class="font-medium truncate">{{ $track['title'] }}</div>
                                        <div class="text-sm text-base-content/60 flex flex-wrap gap-2">
                                            <span>{{ $track['file_name'] }}</span>
                                            @if($track['duration'])
                                                <span>•</span>
                                                <span>{{ gmdate('i:s', $track['duration']) }}</span>
                                            @endif
                                        </div>
                                    </div>

                                    <!-- Remove Button -->
                                    <button 
                                        type="button"
                                        wire:click="removeTrack('{{ $track['id'] }}')"
                                        class="btn btn-sm btn-ghost btn-circle text-error"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        @error('tracks')
            <p class="text-error text-sm">{{ $message }}</p>
        @enderror

        <!-- Actions -->
        <div class="flex flex-col sm:flex-row gap-3 pt-4">
            <button 
                type="submit" 
                class="btn btn-primary btn-sm sm:btn-md"
                wire:loading.attr="disabled"
            >
                <span wire:loading.remove wire:target="save">{{ __('Update Playlist') }}</span>
                <span wire:loading wire:target="save" class="loading loading-spinner loading-sm"></span>
            </button>
            
            <a 
                href="/" 
                wire:navigate 
                class="btn btn-ghost btn-sm sm:btn-md"
            >
                {{ __('Cancel') }}
            </a>
        </div>
    </form>
</div>
