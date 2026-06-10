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
        Schema::create('company_rate_feedbacks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('company_id')->constrained('solar_companies')->cascadeOnDelete();
            $table->unsignedTinyInteger('rate');
            $table->text('feedback')->nullable();
            $table->timestamps();

            $table->unique(['customer_id', 'company_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_rate_feedbacks');
    }
};
