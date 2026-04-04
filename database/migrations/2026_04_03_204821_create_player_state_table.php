<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('player_state', function (Blueprint $table) {
            $table->id();
            $table->foreignId('current_playlist_id')->nullable()->constrained('playlists')->nullOnDelete();
            $table->foreignId('current_track_id')->nullable()->constrained('tracks')->nullOnDelete();
            $table->integer('current_position')->default(0); // Position in playlist (track index)
            $table->string('status')->default('stopped'); // playing, paused, stopped
            $table->string('repeat_mode')->default('none'); // none, one, all
            $table->integer('mplayer_pid')->nullable(); // Process ID of current mplayer
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('player_state');
    }
};
