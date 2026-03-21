<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('office_caterings')) return;

        Schema::create('office_caterings', function (Blueprint $table) {
            $table->id()->comment('Идентификатор');
            $table->uuid()->unique()->comment('UUID');
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade')->comment('ID тенанта');
            $table->foreignId('business_group_id')->nullable()->constrained()->onDelete('cascade')->comment('ID филиала');
            $table->string('correlation_id')->nullable()->index()->comment('ID корреляции');
            $table->string('name')->comment('Название меню');
            $table->string('sku')->unique()->comment('SKU');
            $table->enum('meal_type', ['breakfast', 'lunch', 'dinner', 'snacks', 'combo'])->index()->comment('Тип приёма пищи');
            $table->integer('servings')->comment('Количество порций');
            $table->integer('price_per_serving')->comment('Цена за порцию (коп)');
            $table->integer('total_price')->comment('Общая цена (коп)');
            $table->integer('current_stock')->default(0)->comment('Остаток');
            $table->integer('min_order')->default(1)->comment('Минимальный заказ');
            $table->float('rating')->default(0)->comment('Рейтинг');
            $table->json('tags')->nullable()->comment('Теги');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['tenant_id', 'meal_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('office_caterings');
    }
};
