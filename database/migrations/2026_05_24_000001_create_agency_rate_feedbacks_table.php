<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('agency_rate_feedbacks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('solar_companies')->cascadeOnDelete();
            $table->foreignId('agency_id')->constrained('agencies')->cascadeOnDelete();
            $table->unsignedTinyInteger('rate');
            $table->text('feedback')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'agency_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agency_rate_feedbacks');
    }
};
