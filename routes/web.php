<?php

use App\Livewire\EventHistory;
use App\Livewire\Home;
use App\Livewire\Playlists\Create as PlaylistsCreate;
use App\Livewire\Playlists\Edit as PlaylistsEdit;
use App\Livewire\Playlists\Index as PlaylistsIndex;
use App\Livewire\RemoteControl;
use App\Livewire\SystemStatus;
use Illuminate\Support\Facades\Route;

Route::get('/', Home::class)->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    // Playlist Management
    Route::get('/playlists', PlaylistsIndex::class)->name('playlists.index');
    Route::get('/playlists/create', PlaylistsCreate::class)->name('playlists.create');
    Route::get('/playlists/{playlist}/edit', PlaylistsEdit::class)->name('playlists.edit');
    Route::get('/system-status', SystemStatus::class)->name('system-status');
    Route::get('/event-history', EventHistory::class)->name('event-history');
    Route::get('/remote', RemoteControl::class)->name('remote-control');
});

require __DIR__.'/settings.php';
