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
            $table->enum('status', ['pending', 'paid', 'cancelled'])->default('pending')->after('related_id');
            $table->text('description')->nullable()->after('status');
            $table->timestamp('paid_at')->nullable()->after('description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('earnings', function (Blueprint $table) {
            $table->dropColumn(['status', 'description', 'paid_at']);
        });
    }
};
