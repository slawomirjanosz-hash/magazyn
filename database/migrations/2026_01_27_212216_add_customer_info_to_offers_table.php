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
        Schema::table('offers', function (Blueprint $table) {
            $table->string('customer_name')->nullable()->after('offer_title');
            $table->string('customer_nip')->nullable()->after('customer_name');
            $table->string('customer_address')->nullable()->after('customer_nip');
            $table->string('customer_city')->nullable()->after('customer_address');
            $table->string('customer_postal_code')->nullable()->after('customer_city');
            $table->string('customer_phone')->nullable()->after('customer_postal_code');
            $table->string('customer_email')->nullable()->after('customer_phone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('offers', function (Blueprint $table) {
            $table->dropColumn([
                'customer_name',
                'customer_nip',
                'customer_address',
                'customer_city',
                'customer_postal_code',
                'customer_phone',
                'customer_email'
            ]);
        });
    }
};
