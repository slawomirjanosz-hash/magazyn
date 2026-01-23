<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_stages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('color')->default('#gray');
            $table->integer('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Dodaj domyślne etapy
        DB::table('crm_stages')->insert([
            ['name' => 'Nowy Lead', 'slug' => 'nowy_lead', 'color' => '#gray', 'order' => 1, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Kontakt', 'slug' => 'kontakt', 'color' => '#blue', 'order' => 2, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Wycena', 'slug' => 'wycena', 'color' => '#yellow', 'order' => 3, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Negocjacje', 'slug' => 'negocjacje', 'color' => '#orange', 'order' => 4, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Wygrana', 'slug' => 'wygrana', 'color' => '#green', 'order' => 5, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Przegrana', 'slug' => 'przegrana', 'color' => '#red', 'order' => 6, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_stages');
    }
};
