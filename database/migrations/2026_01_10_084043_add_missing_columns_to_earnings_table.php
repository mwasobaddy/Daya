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
            // Add campaign_id column if it doesn't exist
            if (!Schema::hasColumn('earnings', 'campaign_id')) {
                $table->unsignedBigInteger('campaign_id')->nullable()->after('user_id');
                $table->foreign('campaign_id')->references('id')->on('campaigns')->onDelete('cascade');
            }
            
            // Add scan_id column if it doesn't exist
            if (!Schema::hasColumn('earnings', 'scan_id')) {
                $table->unsignedBigInteger('scan_id')->nullable()->after('campaign_id');
                $table->foreign('scan_id')->references('id')->on('scans')->onDelete('cascade');
            }
            
            // Add commission_amount column if it doesn't exist
            if (!Schema::hasColumn('earnings', 'commission_amount')) {
                $table->decimal('commission_amount', 10, 4)->nullable()->after('amount');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('earnings', function (Blueprint $table) {
            // Drop foreign keys first
            if (Schema::hasColumn('earnings', 'campaign_id')) {
                $table->dropForeign(['campaign_id']);
                $table->dropColumn('campaign_id');
            }
            
            if (Schema::hasColumn('earnings', 'scan_id')) {
                $table->dropForeign(['scan_id']);
                $table->dropColumn('scan_id');
            }
            
            if (Schema::hasColumn('earnings', 'commission_amount')) {
                $table->dropColumn('commission_amount');
            }
        });
    }
};
