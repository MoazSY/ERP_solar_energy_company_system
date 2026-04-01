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
        Schema::create('subscribe_offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId("offer_id")->constrained("offers")->onDelete("cascade");
            $table->foreignId("customer_id")->nullable()->constrained("customers")->nullOnDelete();
            $table->string("customer_name")->nullable();
            $table->string("customer_phone")->nullable();
            $table->string("system_sn")->unique()->nullable();
            $table->boolean("with_installation")->default(true);
            $table->enum("subscription_status", ["accepted", "rejected", "pending"])->default("pending");
            $table->dateTime("subscription_date")->nullable();
            $table->decimal("total_amount", 8, 2)->default(0);
            $table->decimal("additional_cost_amount", 8, 2)->default(0);
            $table->decimal("additional_entitlement_amount", 8, 2)->default(0);
            $table->decimal("final_amount", 8, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscribe_offers');
    }
};
