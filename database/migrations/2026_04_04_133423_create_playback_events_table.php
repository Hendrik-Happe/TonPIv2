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
        Schema::create('playback_events', function (Blueprint $table) {
            $table->id();
            $table->string('action', 32)->index();
            $table->string('source', 32)->index();
            $table->foreignId('playlist_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('track_id')->nullable()->constrained()->nullOnDelete();
            $table->string('rfid_uid', 64)->nullable()->index();
            $table->string('trigger', 64)->nullable();
            $table->string('initiated_by', 255)->nullable();
            $table->json('context')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('playback_events');
    }
};
