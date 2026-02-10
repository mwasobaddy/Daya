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
            $table->dropColumn('campaign_type');
            $table->enum('campaign_objective', ['music_promotion', 'app_downloads', 'brand_awareness', 'product_launch', 'apartment_listing', 'event_promotion', 'social_cause'])->after('title');
            $table->string('digital_product_link')->after('campaign_objective');
            $table->string('explainer_video_url')->nullable()->after('digital_product_link');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn(['campaign_objective', 'digital_product_link', 'explainer_video_url']);
            $table->string('campaign_type')->after('title');
        });
    }
};
