<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('real_estate_properties')) {
            return;
        }

        Schema::create('real_estate_properties', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('business_group_id')->nullable()->index();
            $table->uuid('agent_id')->nullable()->index();
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->string('address', 500);
            $table->double('latitude', 10, 8)->nullable()->comment('Широта для ST_Distance_Sphere');
            $table->double('longitude', 11, 8)->nullable()->comment('Долгота для ST_Distance_Sphere');
            $table->string('type', 50)->index()->comment('apartment|house|land|commercial');
            $table->unsignedBigInteger('price_kopecks')->default(0)->comment('Цена в копейках');
            $table->decimal('area_sqm', 10, 2)->nullable()->comment('Площадь в кв.м.');
            $table->unsignedSmallInteger('rooms')->nullable()->comment('Количество комнат');
            $table->unsignedSmallInteger('floor')->nullable()->comment('Этаж');
            $table->unsignedSmallInteger('total_floors')->nullable()->comment('Всего этажей');
            $table->string('status', 30)->default('draft')->index()->comment('draft|active|sold|rented|archived');
            $table->string('correlation_id', 36)->nullable()->index();
            $table->json('tags')->nullable()->comment('Дополнительные метаданные');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('agent_id')
                ->references('id')
                ->on('real_estate_agents')
                ->nullOnDelete();

            $table->index(['tenant_id', 'status'], 'real_estate_properties_tenant_status_idx');
            $table->index(['latitude', 'longitude'], 'real_estate_properties_geo_idx');
            $table->index(['tenant_id', 'type', 'status'], 'real_estate_properties_tenant_type_status_idx');
        });

        \Illuminate\Support\Facades\DB::statement(
            "ALTER TABLE real_estate_properties COMMENT = 'Объекты недвижимости для аренды и продажи'"
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('real_estate_properties');
    }
};
