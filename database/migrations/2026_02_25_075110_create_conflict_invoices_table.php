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
        Schema::create('conflict_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('purchase_invoices')->onDelete('cascade');
            $table->foreignId('company_id')->constrained('solar_companies')->onDelete('cascade');
            $table->foreignId('agency_id')->constrained('agencies')->onDelete('cascade');
            $table->enum('conflict_type', ['decreased_amount', 'increased_amount', 'spoiled_amount', 'other'])->default('decreased_amount');
            $table->decimal('conflict_amount', 8, 2)->default(0);
            $table->text('conflict_description')->nullable();
            $table->string('image_related')->nullable();
            $table->enum('conflict_state', ['pending', 'resolved'])->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conflict_invoices');
    }
};
