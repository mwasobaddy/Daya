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
        // For SQLite, we need to recreate the column with the new enum values
        Schema::table('campaigns', function (Blueprint $table) {
            $table->enum('status', ['draft', 'submitted', 'under_review', 'approved', 'active', 'live', 'completed', 'rejected'])
                  ->default('submitted')
                  ->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->enum('status', ['draft', 'submitted', 'under_review', 'approved', 'paid', 'live', 'completed', 'rejected'])
                  ->default('submitted')
                  ->change();
        });
    }
};
