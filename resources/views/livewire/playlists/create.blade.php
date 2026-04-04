<div class="max-w-4xl mx-auto p-6">
    <h1 class="text-3xl font-bold mb-6">Create New Playlist</h1>

    @if (session('message'))
        <div role="alert" class="alert alert-success mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 shrink-0 stroke-current" fill="none" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>{{ session('message') }}</span>
        </div>
    @endif

    <form wire:submit="save">
        <!-- Playlist Name -->
        <div class="form-control mb-6">
            <label class="label">
                <span class="label-text">Playlist Name</span>
            </label>
            <input type="text" wire:model="name" placeholder="My Awesome Playlist" class="input input-bordered" />
            @error('name')
                <label class="label">
                    <span class="label-text-alt text-error">{{ $message }}</span>
                </label>
            @enderror
        </div>

        <div class="form-control mb-6">
            <label class="label">
                <span class="label-text">RFID Chip ID</span>
            </label>
            <input type="text" wire:model="rfidUid" placeholder="04A1B2C3D4" class="input input-bordered" />
            <div class="mt-2 flex items-center gap-3">
                <button type="button" class="btn btn-outline btn-sm" wire:click="readCurrentRfidUid" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="readCurrentRfidUid">Chip jetzt einlesen</span>
                    <span wire:loading wire:target="readCurrentRfidUid" class="loading loading-spinner loading-sm"></span>
                </button>
                @if ($rfidReadFeedback)
                    <span class="text-sm text-base-content/70">{{ $rfidReadFeedback }}</span>
                @endif
            </div>
            <label class="label">
                <span class="label-text-alt">Optional. When this chip is scanned, this playlist starts automatically.</span>
            </label>
            @error('rfidUid')
                <label class="label">
                    <span class="label-text-alt text-error">{{ $message }}</span>
                </label>
            @enderror
        </div>

        <div class="form-control mb-6">
            <label class="label">
                <span class="label-text">Lautstärke-Profil (%)</span>
            </label>
            <input
                type="number"
                min="0"
                max="100"
                step="1"
                wire:model="volumeProfile"
                placeholder="z. B. 60"
                class="input input-bordered"
            />
            <label class="label">
                <span class="label-text-alt">Optional. Wird beim RFID-Start dieser Playlist automatisch gesetzt.</span>
            </label>
            @error('volumeProfile')
                <label class="label">
                    <span class="label-text-alt text-error">{{ $message }}</span>
                </label>
            @enderror
        </div>

        <!-- Upload Audio Files Section -->
        <div class="bg-base-200 p-4 rounded-lg mb-6">
            <h3 class="text-xl font-bold mb-4">Upload Audio Files</h3>

            <div class="form-control">
                <label class="label cursor-pointer flex-col items-start">
                    <span class="label-text mb-2">Select multiple audio files (MP3, WAV, OGG, FLAC, M4A, AAC, WMA - max 100MB each, 1GB total)</span>
                    <input 
                        type="file" 
                        wire:model="uploadedFiles" 
                        multiple 
                        accept="audio/*,.mp3,.wav,.ogg,.flac,.m4a,.aac,.wma"
                        class="file-input file-input-bordered file-input-primary w-full" 
                    />
                </label>
                @error('uploadedFiles.*')
                    <label class="label">
                        <span class="label-text-alt text-error">{{ $message }}</span>
                    </label>
                @enderror
            </div>

            <!-- Upload Progress Indicator -->
            <div wire:loading wire:target="uploadedFiles" class="mt-4">
                <div class="flex items-center gap-3">
                    <span class="loading loading-spinner loading-md text-primary"></span>
                    <span class="text-sm">Uploading and extracting metadata...</span>
                </div>
            </div>
        </div>

        <!-- Tracks List with Drag & Drop -->
        @if(count($tracks) > 0)
            <div class="mb-6">
                <h3 class="text-xl font-bold mb-4">
                    Tracks ({{ count($tracks) }}) - Drag to reorder
                </h3>

                <div 
                    x-data="{
                        init() {
                            const sortable = Sortable.create(this.$el, {
                                animation: 150,
                                handle: '[data-sort-handle]',
                                draggable: '[data-sort-item]',
                                ghostClass: 'opacity-50',
                                onEnd: (evt) => {
                                    const items = Array.from(this.$el.querySelectorAll('[data-sort-item]'))
                                    const orderedIds = items.map(item => item.getAttribute('data-sort-item'))
                                    $wire.call('updateTrackOrder', orderedIds)
                                }
                            })
                        }
                    }"
                    class="space-y-2"
                >
                    @foreach($tracks as $track)
                        <div 
                            data-sort-item="{{ $track['id'] }}"
                            wire:key="track-{{ $track['id'] }}"
                            class="card bg-base-100 border border-base-300 shadow-sm cursor-move hover:shadow transition"
                        >
                            <div class="card-body p-4">
                                <div class="flex items-center gap-4" data-sort-handle>
                                    <!-- Drag Handle -->
                                    <div class="text-base-content/50">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"></path>
                                        </svg>
                                    </div>

                                    <!-- Track Number -->
                                    <span class="badge badge-neutral badge-sm">
                                        {{ $track['track_number'] }}
                                    </span>

                                    <!-- Track Info -->
                                    <div class="flex-1">
                                        <div class="font-medium">
                                            {{ $track['title'] }}
                                        </div>
                                        <div class="text-sm opacity-60">
                                            {{ $track['file_name'] ?? 'Unknown file' }}
                                            @if($track['duration'])
                                                · {{ gmdate('i:s', $track['duration']) }}
                                            @endif
                                        </div>
                                    </div>

                                    <!-- Remove Button -->
                                    <button 
                                        type="button"
                                        wire:click="removeTrack('{{ $track['id'] }}')"
                                        class="btn btn-ghost btn-sm btn-circle"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            @error('tracks')
                <div class="mb-6">
                    <span class="text-error text-sm">{{ $message }}</span>
                </div>
            @enderror
        @else
            <div class="text-center py-8 opacity-60 mb-6">
                <p>No tracks added yet. Add your first track above.</p>
            </div>
        @endif

        <!-- Action Buttons -->
        <div class="flex gap-3">
            <button type="submit" class="btn btn-primary" @disabled(count($tracks) === 0)>
                Create Playlist
            </button>
            <a href="{{ route('playlists.index') }}" wire:navigate class="btn btn-ghost">
                Cancel
            </a>
        </div>
    </form>
</div>
