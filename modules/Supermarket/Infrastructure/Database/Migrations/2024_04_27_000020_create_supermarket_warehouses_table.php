<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supermarket_warehouses', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            
            // Владелец склада
            $table->foreignId('owner_id')->constrained('users')->onDelete('cascade');
            
            // Тип склада (B2B/B2C)
            $table->enum('type', ['b2b', 'b2c', 'mixed'])->default('b2b');
            
            // Основная информация
            $table->string('name');
            $table->text('address');
            $table->string('city');
            $table->string('region');
            $table->string('postal_code');
            
            // Координаты
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            
            // Характеристики
            $table->decimal('area', 10, 2)->nullable()->comment('Площадь в м²');
            $table->decimal('capacity', 15, 3)->nullable()->comment('Вместимость в единицах');
            $table->boolean('has_cold_storage')->default(false);
            $table->boolean('has_freezer')->default(false);
            $table->json('storage_zones')->nullable()->comment('Зоны хранения');
            
            // Статус
            $table->enum('status', ['active', 'inactive', 'maintenance'])->default('active');
            
            // Metadata
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('owner_id');
            $table->index('type');
            $table->index('status');
            $table->index('city');
            $table->index('region');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supermarket_warehouses');
    }
};
