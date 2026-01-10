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
        Schema::table('project_removals', function (Blueprint $table) {
            $table->enum('status', ['added', 'returned'])->default('added')->after('quantity');
            $table->timestamp('returned_at')->nullable()->after('status');
            $table->unsignedBigInteger('returned_by_user_id')->nullable()->after('returned_at');
            
            $table->foreign('returned_by_user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_removals', function (Blueprint $table) {
            $table->dropForeign(['returned_by_user_id']);
            $table->dropColumn(['status', 'returned_at', 'returned_by_user_id']);
        });
    }
};
