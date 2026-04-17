<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add accessory fields to fashion_product_categories table.
 * PRODUCTION MANDATORY — канон CatVRF 2026.
 * 
 * Adds support for:
 * - Scarves and accessories
 * - Headwear
 * - Care products
 * - Umbrellas
 * - Men's and women's accessories
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fashion_product_categories', function (Blueprint $table) {
            if (!Schema::hasColumn('fashion_product_categories', 'gender')) {
                $table->enum('gender', ['men', 'women', 'children', 'unisex'])->nullable()->after('target_audience');
                $table->index('gender');
            }

            if (!Schema::hasColumn('fashion_product_categories', 'accessory_type')) {
                $table->string('accessory_type')->nullable()->after('gender');
                $table->index('accessory_type');
            }

            if (!Schema::hasColumn('fashion_product_categories', 'material_type')) {
                $table->string('material_type')->nullable()->after('accessory_type');
                $table->index('material_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('fashion_product_categories', function (Blueprint $table) {
            if (Schema::hasColumn('fashion_product_categories', 'gender')) {
                $table->dropIndex(['gender']);
                $table->dropColumn('gender');
            }

            if (Schema::hasColumn('fashion_product_categories', 'accessory_type')) {
                $table->dropIndex(['accessory_type']);
                $table->dropColumn('accessory_type');
            }

            if (Schema::hasColumn('fashion_product_categories', 'material_type')) {
                $table->dropIndex(['material_type']);
                $table->dropColumn('material_type');
            }
        });
    }
};
