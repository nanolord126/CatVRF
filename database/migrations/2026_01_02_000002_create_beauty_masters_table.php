<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('beauty_masters')) {
            return;
        }

        Schema::create('beauty_masters', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique()->index();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('business_group_id')->nullable()->index()->comment('ID бизнес-группы (филиала)');
            $table->foreignId('salon_id')
                ->constrained('beauty_salons')
                ->cascadeOnDelete()
                ->comment('Салон, в котором работает мастер');
            $table->string('name', 150);
            $table->string('specialization', 100)->index()->comment('Специализация: маникюр, парикмахер и т.д.');
            $table->unsignedSmallInteger('experience_years')->default(0);
            $table->string('phone', 30)->nullable();
            $table->string('email', 150)->nullable();
            $table->json('schedule')->nullable()->comment('График работы мастера по дням');
            $table->decimal('rating', 3, 2)->default(0)->comment('Средний рейтинг 0.00–5.00');
            $table->unsignedInteger('review_count')->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->string('correlation_id', 36)->nullable()->index();
            $table->json('tags')->nullable()->comment('Теги для аналитики и фильтрации');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['salon_id', 'specialization']);
            $table->index(['salon_id', 'is_active']);
        });

        \Illuminate\Support\Facades\DB::statement(
            "COMMENT ON TABLE beauty_masters IS 'Мастера в салонах красоты (вертикаль Beauty)'"
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('beauty_masters');
    }
};
