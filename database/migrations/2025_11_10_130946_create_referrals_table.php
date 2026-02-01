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
        Schema::create('referrals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referrer_id')->constrained('users')->onDelete('cascade'); // DA
            $table->foreignId('referred_id')->constrained('users')->onDelete('cascade'); // DCD, DA, or Client
            $table->enum('type', ['admin_to_da', 'da_to_da', 'da_to_dcd', 'dcd_to_da', 'dcd_to_dcd', 'da_to_client']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('referrals');
    }
};
