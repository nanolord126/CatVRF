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
        Schema::create('warehouse_products', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('sku')->unique();
            $table->string('name');
            $table->string('barcode')->nullable()->unique();
            $table->text('description')->nullable();
            $table->string('unit')->default('шт');
            $table->decimal('weight', 8, 3)->default(0);
            $table->string('weight_unit')->default('kg');
            $table->json('dimensions')->nullable();
            $table->boolean('is_hazardous')->default(false);
            $table->boolean('is_fragile')->default(false);
            $table->boolean('requires_temperature_control')->default(false);
            $table->decimal('min_temperature', 5, 2)->nullable();
            $table->decimal('max_temperature', 5, 2)->nullable();
            $table->string('category')->nullable();
            $table->string('brand')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->string('correlation_id')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['sku', 'is_active']);
            $table->index(['barcode']);
            $table->index(['category']);
            $table->index(['brand']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warehouse_products');
    }
};
