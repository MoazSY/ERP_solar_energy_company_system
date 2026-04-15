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
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
     $table->foreignId('payment_id')->constrained()->cascadeOnDelete();

    $table->string('gateway'); // shamcash
    $table->string('external_id')->nullable(); // id من shamcash
    $table->string('payment_url')->nullable();
    $table->enum('status', ['initiated','pending','paid','failed'])->default('initiated');
    $table->json('response')->nullable(); // حفظ رد shamcash
    $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
    }
};
