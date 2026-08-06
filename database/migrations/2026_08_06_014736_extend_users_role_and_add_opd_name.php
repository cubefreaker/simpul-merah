<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * SQLite does not support ALTER COLUMN for enums, so we recreate using a text column
     * with a check constraint approach — or simply change to string and validate in app layer.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Add new columns
            $table->string('opd_name')->nullable()->after('role')->comment('Nama OPD untuk user SKPD (U1)');
        });

        // SQLite does not support modifying enums directly.
        // We use a workaround: drop old role column and recreate with new values.
        // Since this is a fresh project, this is safe.
        DB::statement('PRAGMA foreign_keys=OFF;');

        Schema::table('users', function (Blueprint $table) {
            $table->string('role_new')->default('user')->after('role');
        });

        DB::statement('UPDATE users SET role_new = role');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('role_new', 'role');
        });

        DB::statement('PRAGMA foreign_keys=ON;');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('opd_name');
        });
    }
};
