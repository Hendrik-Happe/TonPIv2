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
        Schema::table('player_state', function (Blueprint $table) {
            $table->boolean('rfid_chip_present')->default(false)->after('restart_on_next');
            $table->string('present_rfid_uid')->nullable()->after('rfid_chip_present');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('player_state', function (Blueprint $table) {
            $table->dropColumn(['rfid_chip_present', 'present_rfid_uid']);
        });
    }
};
