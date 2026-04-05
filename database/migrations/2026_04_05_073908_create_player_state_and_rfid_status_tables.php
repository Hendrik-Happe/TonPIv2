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
        Schema::create('rfid_status', function (Blueprint $table) {
            $table->id();
            $table->string('current_uid')->nullable();
            $table->timestamp('last_seen')->nullable();
            $table->timestamps();
        });

        // Insert default RFID status
        DB::table('rfid_status')->insert([
            'current_uid' => null,
            'last_seen' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rfid_status');
    }
};
