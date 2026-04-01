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
        Schema::create('custom_commisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId("commision_police_id")->constrained("commision_polices")->onDelete("cascade");
            $table->morphs("commisionable");
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('custom_commisions');
    }
};
