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
        Schema::create('venture_shares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->decimal('kedds_amount', 15, 4)->default(0);
            $table->decimal('kedws_amount', 15, 4)->default(0);
            $table->string('reason'); // e.g., 'da_to_da_referral', 'dcd_early_adopter'
            $table->timestamp('allocated_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('venture_shares');
    }
};
