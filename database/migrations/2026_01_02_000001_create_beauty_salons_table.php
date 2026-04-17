<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('beauty_salons')) {
            return;
        }

        Schema::create('beauty_salons', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique()->index();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('business_group_id')->nullable()->index()->comment('ID бизнес-группы (филиала)');
            $table->string('name', 200);
            $table->string('address', 300)->nullable();
            $table->string('city', 100)->nullable()->index();
            $table->double('latitude', 10, 7)->nullable();
            $table->double('longitude', 11, 7)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('website', 200)->nullable();
            $table->string('type', 50)->nullable()->comment('salon, master_home, spa и т.д.');
            $table->json('schedule')->nullable()->comment('Расписание работы по дням');
            $table->decimal('rating', 3, 2)->default(0)->comment('Средний рейтинг 0.00–5.00');
            $table->unsignedInteger('review_count')->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('is_verified')->default(false)->index();
            $table->string('correlation_id', 36)->nullable()->index();
            $table->json('tags')->nullable()->comment('Теги для аналитики и фильтрации');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'city']);
            $table->index(['tenant_id', 'is_active']);
        });

        \Illuminate\Support\Facades\DB::statement(
            "COMMENT ON TABLE beauty_salons IS 'Салоны красоты и SPA (вертикаль Beauty)'"
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('beauty_salons');
    }
};
