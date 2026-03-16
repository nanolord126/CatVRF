<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->onDelete('cascade');
            $table->string('vertical')->index();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('icon')->nullable();
            $table->text('description')->nullable();
            $table->integer('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->string('correlation_id')->nullable()->index();        });

        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('logo')->nullable();
            $table->string('country')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();

            $table->string('correlation_id')->nullable()->index();        });

        Schema::create('filters', function (Blueprint $table) {
            $table->id();
            $table->string('vertical')->index();
            $table->string('name');
            $table->enum('type', ['select', 'range', 'color', 'boolean']);
            $table->string('unit')->nullable();
            $table->timestamps();

            $table->string('correlation_id')->nullable()->index();        });

        Schema::create('filter_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('filter_id')->constrained()->onDelete('cascade');
            $table->string('value');
            $table->string('label');
            $table->timestamps();

            $table->string('correlation_id')->nullable()->index();        });

        Schema::table('b2b_products', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->constrained();
            $table->foreignId('brand_id')->nullable()->constrained();
            $table->string('sku')->unique()->nullable();
            $table->json('specifications')->nullable();
        });
    }

    public function down(): void {
        Schema::dropIfExists('filter_values');
        Schema::dropIfExists('filters');
        Schema::dropIfExists('brands');
        Schema::dropIfExists('categories');
        Schema::table('b2b_products', function (Blueprint $table) {
            $table->dropColumn(['category_id', 'brand_id', 'sku', 'specifications']);
        });
    }
};

