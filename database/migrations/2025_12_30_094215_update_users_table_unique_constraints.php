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
        Schema::table('users', function (Blueprint $table) {
            // Drop existing unique constraints
            $table->dropUnique(['email']);
            $table->dropUnique(['phone']);
            
            // Add composite unique constraints to allow same email/phone for different roles
            $table->unique(['email', 'role']);
            $table->unique(['phone', 'role']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop composite unique constraints
            $table->dropUnique(['email', 'role']);
            $table->dropUnique(['phone', 'role']);
            
            // Restore original unique constraints
            $table->unique('email');
            $table->unique('phone');
        });
    }
};
