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
        Schema::table('earnings', function (Blueprint $table) {
            // Check and drop related_id if it exists (we're using scan_id now)
            if (Schema::hasColumn('earnings', 'related_id')) {
                $table->dropColumn('related_id');
            }
            
            // Remove month column if it exists (not needed with timestamps)
            if (Schema::hasColumn('earnings', 'month')) {
                $table->dropColumn('month');
            }
        });
        
        // Update existing type values to new naming convention
        DB::statement("UPDATE earnings SET type = 'scan_earning' WHERE type = 'scan'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('earnings', function (Blueprint $table) {
            $table->foreignId('related_id')->nullable();
            $table->string('month')->nullable();
        });
    }
};
