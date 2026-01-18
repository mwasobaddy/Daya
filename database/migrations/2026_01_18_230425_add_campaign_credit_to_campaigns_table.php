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
            // Campaign credit is the remaining budget that gets deducted as scans happen
            // Initialized to budget amount when campaign is approved
            $table->decimal('campaign_credit', 10, 4)->default(0)->after('spent_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn('campaign_credit');
        });
    }
};
