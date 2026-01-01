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
        Schema::create('admin_actions', function (Blueprint $table) {
            $table->id();
            $table->string('action', 50); // approve_campaign, reject_campaign, complete_campaign, mark_payment_complete
            $table->string('resource_type', 50); // campaign, earning, user
            $table->unsignedBigInteger('resource_id');
            $table->string('token', 64)->unique();
            $table->json('metadata')->nullable();
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->timestamps();

            $table->index(['token', 'action']);
            $table->index(['resource_type', 'resource_id']);
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_actions');
    }
};
