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
        Schema::table('crm_companies', function (Blueprint $table) {
            $table->foreignId('added_by')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('crm_companies', function (Blueprint $table) {
            $table->dropForeign(['added_by']);
            $table->dropColumn('added_by');
        });
    }
};
