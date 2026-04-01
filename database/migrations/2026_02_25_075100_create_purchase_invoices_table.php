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
        Schema::create('purchase_invoices', function (Blueprint $table) {
            $table->id();
            $table->morphs("seller_entity");
            $table->morphs("buyer_entity");
            $table->string("buyer_name")->nullable();
            $table->string("buyer_phone")->nullable();
            $table->foreignId("order_list_id")->nullable()->constrained("order_lists")->nullOnDelete();
            $table->morphs("object_entity");
            $table->string("invoice_number")->unique();
            $table->date("invoice_date")->nullable();
            $table->dateTime("due_date")->nullable();
            $table->enum("currency", ["USD", "SY"])->default("SY");
            $table->decimal("delivery_fee", 8, 2)->default(0);
            $table->decimal("installation_fee", 8, 2)->default(0);
            $table->decimal("subtotal", 8, 2)->default(0);
            $table->decimal("total_discount", 8, 2)->default(0);
            $table->decimal("total_amount", 8, 2)->default(0);
            $table->enum("payment_status", ["pending", "partially_paid", "paid"])->default("pending");
            $table->foreignId("consumables_id")->nullable()->constrained("consumables")->nullOnDelete();
            $table->decimal("consumables_amount", 8, 2)->default(0)->nullable();
            $table->enum("payment_method", ["bank_transfer", "cash"])->default("bank_transfer")->nullable();
            $table->enum("payment_conumables_method", ["bank_transfer", "cash"])->default("bank_transfer")->nullable();
            $table->decimal("net_profit", 8, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_invoices');
    }
};
