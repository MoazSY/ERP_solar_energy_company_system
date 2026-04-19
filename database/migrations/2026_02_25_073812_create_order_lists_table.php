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
        Schema::create('order_lists', function (Blueprint $table) {
            $table->id();
            $table->nullableMorphs("request_entity");
            $table->nullableMorphs("orderable_entity");
            $table->string("customer_first_name")->nullable();
            $table->string("customer_last_name")->nullable();
            $table->enum("status", ["pending", "in_progress", "completed", "canceled"])->default("pending");
            $table->decimal("sub_total_amount", 10, 2)->default(0)->nullable();
            $table->decimal("total_discount_amount", 10, 2)->default(0)->nullable();
            $table->decimal("total_amount", 10, 2)->default(0)->nullable();
            $table->boolean("with_delivery")->default(false)->nullable();
            $table->foreignId("inventory_manager_id")->nullable()->constrained("company_agency_employees")->nullOnDelete();
            $table->boolean("identical_state")->default(true)->nullable();
            $table->dateTime("request_datetime")->nullable();
            $table->dateTime("discharge_datetime")->nullable();
            $table->dateTime("recieve_datetime")->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_lists');
    }
};
