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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->morphs("payable");
            $table->morphs("target_table");
            $table->morphs("payment_object_table");//invoice or commision_charge or promotion or subscribe_policies or any thing else that we want to pay for it
            $table->enum("payment_object_type_name", ["invoice", "commission_charge", "promotion", "subscribe_policy","service","other"])->default("invoice");
            $table->decimal("amount", 10, 2)->default(0);
            $table->enum("currency",["USD","SY"])->default("SY");

            $table->dateTime("paid_at")->nullable();
           
            $table->enum("status", [
                "pending",
                "processing",
                "paid",
                "failed",
                "cancelled"
            ])->default("pending");
            // $table->enum("payment_method", ["cash","bank_transfer"])->default("bank_transfer");
            // $table->string("transaction_id")->nullable();
            $table->boolean("re_subscribed")->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
