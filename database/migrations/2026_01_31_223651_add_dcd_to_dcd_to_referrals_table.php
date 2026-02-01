<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // For MySQL/PostgreSQL
        if (config('database.default') !== 'sqlite') {
            DB::statement("ALTER TABLE referrals MODIFY COLUMN type ENUM('admin_to_da', 'da_to_da', 'da_to_dcd', 'dcd_to_da', 'dcd_to_dcd', 'da_to_client')");
        }
        // For SQLite, enum is not enforced, so no change needed
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // For MySQL/PostgreSQL
        if (config('database.default') !== 'sqlite') {
            DB::statement("ALTER TABLE referrals MODIFY COLUMN type ENUM('admin_to_da', 'da_to_da', 'da_to_dcd', 'dcd_to_da', 'da_to_client')");
        }
    }
};
