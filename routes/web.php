<?php

use App\Livewire\Home;
use App\Livewire\Playlists\Create as PlaylistsCreate;
use App\Livewire\Playlists\Edit as PlaylistsEdit;
use App\Livewire\Playlists\Index as PlaylistsIndex;
use Illuminate\Support\Facades\Route;

Route::get('/', Home::class)->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');

    // Playlist Management
    Route::get('/playlists', PlaylistsIndex::class)->name('playlists.index');
    Route::get('/playlists/create', PlaylistsCreate::class)->name('playlists.create');
    Route::get('/playlists/{playlist}/edit', PlaylistsEdit::class)->name('playlists.edit');
});

require __DIR__.'/settings.php';
