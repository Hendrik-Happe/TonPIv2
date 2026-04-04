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
        Schema::table('users', function (Blueprint $table) {
            $columns = Schema::getColumnListing('users');
            $indexes = array_column(Schema::getIndexes('users'), 'name');

            if (in_array('users_email_unique', $indexes, true)) {
                $table->dropUnique('users_email_unique');
            }

            if (in_array('email', $columns, true)) {
                $table->dropColumn('email');
            }

            if (in_array('email_verified_at', $columns, true)) {
                $table->dropColumn('email_verified_at');
            }

            if (! in_array('users_name_unique', $indexes, true)) {
                $table->unique('name');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['name']);
            $table->string('email')->unique()->after('name');
            $table->timestamp('email_verified_at')->nullable()->after('email');
        });
    }
};
