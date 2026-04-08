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
        Schema::create('wifi_networks', function (Blueprint $table) {
            $table->id();
            $table->string('ssid')->unique();
            $table->text('password')->nullable();
            $table->integer('priority')->default(0);
            $table->boolean('auto_connect')->default(true);
            $table->timestamps();

            $table->index(['auto_connect', 'priority']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wifi_networks');
    }
};
