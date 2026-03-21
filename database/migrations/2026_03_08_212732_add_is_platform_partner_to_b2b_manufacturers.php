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
        Schema::table('b2b_manufacturers', function (Blueprint $table) {
            $table->boolean('is_platform_partner')->default(false)->after('is_active');
            $table->integer('shipping_radius_km')->nullable()->after('is_platform_partner');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('b2b_manufacturers', function (Blueprint $table) {
            $table->dropColumn(['is_platform_partner', 'shipping_radius_km']);
        });
    }
};
