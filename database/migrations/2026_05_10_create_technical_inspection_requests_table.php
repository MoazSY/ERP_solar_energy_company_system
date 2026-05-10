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
        Schema::create('technical_inspection_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('solar_companies')->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->string('customer_name')->nullable();
            $table->string('customer_phone')->nullable();
            $table->text('customer_address')->nullable();
            $table->enum('inspection_status', ['pending', 'rejected', 'accepted', 'completed'])->default('pending');
            $table->enum('priority', ['low', 'medium', 'high'])->default('medium');
            $table->text('issue_description')->nullable();
            $table->string('image_state')->nullable();
            $table->decimal('inspection_price', 10, 2)->nullable();
            $table->dateTime('response_date')->nullable();
            $table->dateTime('expected_date')->nullable();
            $table->enum('payment_method', ['cash', 'card', 'bank_transfer'])->default('cash');
            $table->enum('currency', ['USD', 'SY'])->default('SY');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('technical_inspection_requests');
    }
};
