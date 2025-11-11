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
            $table->foreignId('dcd_id')->nullable()->after('client_id')->constrained('users')->onDelete('cascade');
            $table->string('campaign_type')->after('title');
            $table->text('target_audience')->after('description');
            $table->string('duration')->after('target_audience');
            $table->string('objectives')->after('duration');
            $table->json('metadata')->nullable()->after('objectives');
            $table->timestamp('completed_at')->nullable()->after('metadata');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropForeign(['dcd_id']);
            $table->dropColumn(['dcd_id', 'campaign_type', 'target_audience', 'duration', 'objectives', 'metadata', 'completed_at']);
        });
    }
};
