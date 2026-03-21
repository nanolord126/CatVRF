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
        Schema::table('venues', function (Blueprint $table) {
            $table->string('type')->after('name')->index()->default('other');
            $table->unsignedBigInteger('hotel_id')->nullable()->after('capacity');
            $table->unsignedBigInteger('restaurant_id')->nullable()->after('hotel_id');
            
            // Note: We don't use ->constrained() because these might be in different schemas in a multi-tenant setup,
            // or the tables might not exist in the central database yet.
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('venues', function (Blueprint $table) {
            $table->dropColumn(['type', 'hotel_id', 'restaurant_id']);
        });
    }
};
