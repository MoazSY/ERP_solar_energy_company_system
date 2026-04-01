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
        Schema::create('deliveries', function (Blueprint $table) {
            $table->id();
            $table->morphs("deliverable_object");
            $table->nullableMorphs("entity_type");
            $table->foreignId("order_list_id")->nullable()->constrained("order_lists")->nullOnDelete();
            $table->decimal("delivery_fee", 8, 2)->default(0);
            $table->enum("currency", ["USD", "SY"])->default("SY");
            $table->enum("delivery_status", ["pending", "in_transit", "delivered", "canceled"])->default("pending");
            $table->foreignId("address_id")->nullable()->constrained("addresses")->nullOnDelete();
            $table->string("delivery_address")->nullable();
            $table->foreignId("governorate_id")->nullable()->constrained("governorates")->nullOnDelete();
            $table->foreignId("area_id")->nullable()->constrained("areas")->nullOnDelete();
            $table->string("contact_name")->nullable();
            $table->string("contact_phone")->nullable();
            $table->string("latitude")->nullable();
            $table->string("longitude")->nullable();
            $table->foreignId("driver_id")->nullable()->constrained("company_agency_employees")->nullOnDelete();
            $table->dateTime("scheduled_delivery_datetime")->nullable();
            $table->dateTime("shipped_at")->nullable();
            $table->dateTime("delivered_at")->nullable();
            $table->boolean("client_recieve_delivery")->default(false);
            $table->decimal("net_profit", 8, 2)->default(0);
            $table->decimal("weight_kg", 8, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deliveries');
    }
};
