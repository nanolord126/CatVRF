<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('beauty_services')) {
            return;
        }

        Schema::create('beauty_services', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique()->index();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->foreignId('salon_id')
                ->nullable()
                ->constrained('beauty_salons')
                ->nullOnDelete()
                ->comment('Услуга привязана к конкретному салону (или null — глобальная)');
            $table->string('name', 200);
            $table->string('category', 50)->index()->comment('ServiceCategory enum: haircut, manicure, massage и т.д.');
            $table->unsignedInteger('price_cents')->comment('Цена услуги в копейках');
            $table->unsignedSmallInteger('duration_minutes')->comment('Длительность услуги в минутах');
            $table->text('description')->nullable();
            $table->json('consumables_json')->nullable()->comment('Расходники: {material_id: quantity}');
            $table->json('tags')->nullable()->comment('Теги для аналитики и поиска');
            $table->boolean('is_active')->default(true)->index();
            $table->string('correlation_id', 36)->nullable()->index();
            $table->timestamps();

            $table->index(['tenant_id', 'category']);
            $table->index(['salon_id', 'is_active']);
            $table->index(['tenant_id', 'is_active']);
        });

        \Illuminate\Support\Facades\DB::statement(
            "COMMENT ON TABLE beauty_services IS 'Услуги салонов красоты (вертикаль Beauty)'"
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('beauty_services');
    }
};
