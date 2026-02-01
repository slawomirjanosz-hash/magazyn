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
        Schema::table('offer_settings', function (Blueprint $table) {
            $table->string('offer_template_path')->nullable()->after('start_number');
            $table->string('offer_template_original_name')->nullable()->after('offer_template_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('offer_settings', function (Blueprint $table) {
            $table->dropColumn(['offer_template_path', 'offer_template_original_name']);
        });
    }
};
