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
        Schema::create('offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId("company_id")->constrained("solar_companies")->onDelete("cascade");
            $table->foreignId("customer_id")->nullable()->constrained("customers")->nullOnDelete();
            $table->string("customer_name")->nullable();
            $table->string("offer_name")->nullable();
            $table->text("offer_details")->nullable();
            $table->enum("system_type", ["on_grid", "off_grid", "hybrid"])->default("off_grid");
            $table->decimal("subtotal_amount", 8, 2)->default(0);
            $table->decimal("discount_amount", 8, 2)->default(0);
            $table->enum("discount_type", ["fixed", "percentage"])->default("fixed");
            $table->decimal("average_total_amount", 8, 2)->default(0);
            $table->enum("currency", ["USD", "SY"])->default("SY");
            $table->decimal("validity_days", 2, 1)->default(0);
            $table->decimal("average_delivery_cost", 8, 2)->default(0);
            $table->decimal("average_installation_cost", 8, 2)->default(0);
            $table->decimal("average_metal_installation_cost", 8, 2)->default(0);
            $table->enum("status_reply", ["pending", "accepted", "rejected"])->default("pending");
            $table->boolean("offer_available")->default(true);
            $table->string("panar_image")->nullable();
            $table->enum("public_private", ["public", "private"])->default("private");
            $table->date("offer_date")->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('offers');
    }
};
