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
        Schema::table('campaigns', function (Blueprint $table) {
            // Store the cost per click/scan for this campaign
            $table->decimal('cost_per_click', 10, 4)->default(0)->after('budget');
            
            // Track total amount spent on scans (sum of all scan earnings)
            $table->decimal('spent_amount', 10, 4)->default(0)->after('cost_per_click');
            
            // Maximum number of scans allowed (calculated: budget / cost_per_click)
            $table->integer('max_scans')->default(0)->after('spent_amount');
            
            // Total scans recorded for quick reference
            $table->integer('total_scans')->default(0)->after('max_scans');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn(['cost_per_click', 'spent_amount', 'max_scans', 'total_scans']);
        });
    }
};
