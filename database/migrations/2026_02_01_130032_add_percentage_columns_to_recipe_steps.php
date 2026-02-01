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
        Schema::table('recipe_steps', function (Blueprint $table) {
            $table->decimal('percentage', 8, 2)->nullable()->after('quantity');
            $table->boolean('is_flour')->default(false)->after('percentage');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('recipe_steps', function (Blueprint $table) {
            $table->dropColumn(['percentage', 'is_flour']);
        });
    }
};
