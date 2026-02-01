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
        Schema::create('offer_settings', function (Blueprint $table) {
            $table->id();
            $table->string('element1_type')->default('text');
            $table->string('element1_value')->default('OFF');
            $table->string('separator1')->default('_');
            $table->string('element2_type')->default('date');
            $table->string('element2_value')->nullable();
            $table->string('separator2')->default('_');
            $table->string('element3_type')->default('number');
            $table->string('element3_value')->nullable();
            $table->string('separator3')->default('_');
            $table->string('element4_type')->default('empty');
            $table->string('element4_value')->nullable();
            $table->integer('start_number')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('offer_settings');
    }
};
