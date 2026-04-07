<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('beauty_consumables')) {
            return;
        }

        Schema::create('beauty_consumables', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index()->comment('Тенант-владелец расходника');
            $table->foreignId('service_id')
                ->nullable()
                ->constrained('beauty_services')
                ->nullOnDelete()
                ->comment('Услуга, к которой привязан расходник (null = общий склад)');
            $table->string('name', 150)->comment('Название расходного материала');
            $table->string('unit', 30)->default('шт')->comment('Единица измерения: шт, мл, г, пара');
            $table->integer('current_stock')->default(0)->comment('Текущий остаток (может включать зарезервированные)');
            $table->integer('hold_stock')->default(0)->comment('Зарезервированный остаток (hold при бронировании)');
            $table->integer('min_stock_threshold')->default(5)->comment('Порог уведомления о низком остатке');
            $table->decimal('quantity_per_service', 8, 2)->default(1)->comment('Количество расходника на одну услугу');
            $table->string('correlation_id', 36)->nullable()->index()->comment('ID для сквозного трейсинга');
            $table->json('tags')->nullable()->comment('Теги для аналитики и фильтрации');
            $table->timestamps();

            $table->index(['tenant_id', 'service_id']);
            $table->index(['tenant_id', 'current_stock']);
        });

        \Illuminate\Support\Facades\DB::statement(
            "COMMENT ON TABLE beauty_consumables IS 'Расходные материалы для услуг Beauty (перчатки, краска, масло и т.д.)'"
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('beauty_consumables');
    }
};
