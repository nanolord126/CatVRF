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
        // Добавление поддержки Аренды недвижимости и Доставки воды во все B2B категории
        if (Schema::hasTable('b2b_products')) {
            Schema::table('b2b_products', function (Blueprint $table) {
                if (!Schema::hasColumn('b2b_products', 'extended_category')) {
                    $table->string('extended_category')->nullable()->index()->after('name');
                }
            });
        }

        if (Schema::hasTable('b2b_bulk_orders')) {
            Schema::table('b2b_bulk_orders', function (Blueprint $table) {
                if (!Schema::hasColumn('b2b_bulk_orders', 'service_type')) {
                    $table->string('service_type')->default('product')->index()->after('contract_id');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('b2b_products')) {
            Schema::table('b2b_products', function (Blueprint $table) {
                $table->dropColumn('extended_category');
            });
        }

        if (Schema::hasTable('b2b_bulk_orders')) {
            Schema::table('b2b_bulk_orders', function (Blueprint $table) {
                $table->dropColumn('service_type');
            });
        }
    }
};
