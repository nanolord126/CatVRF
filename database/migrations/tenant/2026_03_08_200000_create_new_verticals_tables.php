<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('automotives', function (Blueprint $table) {
            $table->id(); $table->string('name');
            $table->string('type'); $table->string('inn')->nullable();
            $table->string('tenant_id'); $table->timestamps();
        });
        Schema::create('retail_categories', function (Blueprint $table) {
            $table->id(); $table->string('name');
            $table->string('category'); $table->string('sku_prefix');
            $table->string('tenant_id'); $table->timestamps();
        });
        Schema::create('food_venues', function (Blueprint $table) {
            $table->id(); $table->string('name');
            $table->string('sub_type'); $table->string('cuisine_type')->nullable();
            $table->string('tenant_id'); $table->timestamps();
        });
    }
};
