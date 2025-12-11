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
        Schema::table('referrals', function (Blueprint $table) {
            // For SQLite, we need to recreate the table to add new enum value
            // Add temporary column with new enum values
            $table->string('type_new')->default('admin_to_da');
        });

        // Copy data from old column to new column
        \DB::statement("UPDATE referrals SET type_new = type");

        Schema::table('referrals', function (Blueprint $table) {
            // Drop old column and rename new column
            $table->dropColumn('type');
        });

        Schema::table('referrals', function (Blueprint $table) {
            // Add the new column with proper enum constraint
            $table->enum('type', ['admin_to_da', 'da_to_da', 'da_to_dcd', 'dcd_to_da', 'da_to_client'])->default('admin_to_da');
        });

        // Copy data back
        \DB::statement("UPDATE referrals SET type = type_new");

        Schema::table('referrals', function (Blueprint $table) {
            // Drop temporary column
            $table->dropColumn('type_new');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('referrals', function (Blueprint $table) {
            // Revert back to original enum values (similar process in reverse)
            $table->string('type_old')->default('admin_to_da');
        });

        \DB::statement("UPDATE referrals SET type_old = type WHERE type != 'da_to_client'");

        Schema::table('referrals', function (Blueprint $table) {
            $table->dropColumn('type');
        });

        Schema::table('referrals', function (Blueprint $table) {
            $table->enum('type', ['admin_to_da', 'da_to_da', 'da_to_dcd', 'dcd_to_da'])->default('admin_to_da');
        });

        \DB::statement("UPDATE referrals SET type = type_old");

        Schema::table('referrals', function (Blueprint $table) {
            $table->dropColumn('type_old');
        });
    }
};
