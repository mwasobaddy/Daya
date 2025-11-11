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
        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique(); // 'kenya', 'nigeria'
            $table->string('name');
            $table->string('county_label'); // 'County' for Kenya, 'State' for Nigeria
            $table->string('subcounty_label'); // 'Sub-county' for Kenya, 'Local Government' for Nigeria
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('countries');
    }
};
