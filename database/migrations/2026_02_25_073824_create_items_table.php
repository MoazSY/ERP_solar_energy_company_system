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
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->morphs("itemable");
            $table->foreignId("product_id")->nullable()->constrained("products")->nullOnDelete();
            $table->string("item_name_snapshot")->nullable();
            $table->integer("quantity")->default(1)->nullable();
            $table->decimal("unit_price", 8, 2)->default(0)->nullable();
            $table->decimal("total_price", 8, 2)->default(0)->nullable();
            $table->decimal("unit_discount_amount", 8, 2)->default(0)->nullable();
            $table->decimal("total_discount_amount", 8, 2)->default(0)->nullable();
            $table->enum("discount_type", ["percentage", "fixed"])->nullable();
            $table->enum("currency", ["USD", "SY"])->default("SY")->nullable();
            $table->string("serial_numbers")->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
