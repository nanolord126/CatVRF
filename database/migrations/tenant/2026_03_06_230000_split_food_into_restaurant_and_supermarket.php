<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Дифференциация типов заведений еды
        Schema::table('restaurants', function (Blueprint $table) {
            $table->enum('type', ['horeca', 'grocery'])->default('horeca')->after('name'); // HoReCa (рестораны) vs Grocery (супермаркеты)
            $table->json('business_hours')->nullable()->after('type'); // Разное время работы для завтраков и ночных смен
            $table->boolean('has_kitchen')->default(true)->after('business_hours'); // Флаг для ресторанов
            $table->boolean('has_warehouse')->default(false)->after('has_kitchen'); // Флаг для супермаркетов
        });

        // 2. Расширение продуктов для супермаркетов (Inventory Integration)
        Schema::table('restaurant_menus', function (Blueprint $table) {
            $table->string('sku')->nullable()->after('name'); // Артикул для сканирования в супермаркете
            $table->string('barcode')->nullable()->after('sku'); 
            $table->decimal('weight_unit', 8, 3)->nullable()->after('price'); // Вес/Объем (кг, л)
            $table->boolean('is_perishable')->default(false); // Скоропортящийся товар
            $table->integer('stock_quantity')->default(0); // Остатки на полке
        });

        // 3. Таблица категорий (разные для еды и продуктов)
        Schema::create('food_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('target_type', ['restaurant', 'supermarket']);
            $table->string('icon')->nullable();
            $table->timestamps();

            $table->string('correlation_id')->nullable()->index();        });

        // 4. Логика заказов (Resto vs Grocery)
        Schema::table('restaurant_orders', function (Blueprint $table) {
            $table->enum('order_type', ['dine_in', 'delivery', 'pickup', 'grocery_shelf'])->default('delivery');
            $table->timestamp('preparation_started_at')->nullable(); // Для кухни
            $table->timestamp('picking_started_at')->nullable(); // Для сборщика в супермаркете
            $table->json('delivery_meta')->nullable(); // Данные курьера/дрона
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('food_categories');
        Schema::table('restaurant_orders', function (Blueprint $table) {
            $table->dropColumn(['order_type', 'preparation_started_at', 'picking_started_at', 'delivery_meta']);
        });
        Schema::table('restaurant_menus', function (Blueprint $table) {
            $table->dropColumn(['sku', 'barcode', 'weight_unit', 'is_perishable', 'stock_quantity']);
        });
        Schema::table('restaurants', function (Blueprint $table) {
            $table->dropColumn(['type', 'business_hours', 'has_kitchen', 'has_warehouse']);
        });
    }
};

