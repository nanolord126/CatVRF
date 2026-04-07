<?php declare(strict_types=1);

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
        if (Schema::hasTable('beauty_consumables')) {
            return;
        }

        Schema::create('beauty_consumables', function (Blueprint $table) {
            $table->id()->comment('Уникальный идентификатор расходника');
            $table->uuid('uuid')->unique()->comment('Уникальный идентификатор UUID');
            
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade')->comment('ID тенанта');
            $table->foreignId('business_group_id')->nullable()->constrained('business_groups')->onDelete('set null')->comment('ID бизнес-группы (филиала)');
            $table->foreignId('salon_id')->constrained('beauty_salons')->onDelete('cascade')->comment('ID салона, к которому относится расходник');

            $table->string('name')->comment('Название расходного материала (напр. "Нитриловые перчатки")');
            $table->string('sku')->nullable()->unique()->comment('Артикул (SKU)');
            $table->string('unit')->comment('Единица измерения (шт, мл, гр)');
            
            $table->integer('current_stock')->default(0)->comment('Текущий остаток на складе');
            $table->integer('min_stock_threshold')->default(0)->comment('Минимальный порог для уведомления');
            $table->unsignedInteger('price_per_unit_kopeki')->default(0)->comment('Цена закупки за единицу в копейках');

            $table->string('correlation_id')->nullable()->index()->comment('ID для сквозной трассировки');
            $table->jsonb('tags')->nullable()->comment('Теги для аналитики и фильтрации');
            
            $table->timestamps();
            $table->softDeletes();

            $table->comment('Расходные материалы для вертикали Beauty');
            $table->index(['tenant_id', 'salon_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('beauty_consumables');
    }
};


