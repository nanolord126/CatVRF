<?php

declare(strict_types=1);

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
        // 1. Шаблоны конфигураторов (Кухни, Шкафы, Лестницы и т.д.)
        if (!Schema::hasTable('configurator_templates')) {
            Schema::create('configurator_templates', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->string('name')->comment('Название конструктора');
                $table->string('slug')->unique();
                $table->string('type')->comment('kitchen, wardrobe, stairs, etc');
                $table->jsonb('meta')->nullable()->comment('Настройки шагов, правила валидации');
                $table->boolean('is_active')->default(true);
                $table->string('correlation_id')->nullable()->index();
                $table->jsonb('tags')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->comment('Шаблоны мебельных и строительных конструкторов');
            });
        }

        // 2. Опции конфигуратора (Материалы, фасады, фурнитура)
        if (!Schema::hasTable('configurator_options')) {
            Schema::create('configurator_options', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->foreignId('template_id')->constrained('configurator_templates')->onDelete('cascade');
                $table->string('category')->comment('facade, body, hardware, worktop');
                $table->string('name');
                $table->string('sku')->nullable()->index();
                $table->integer('price_kopeks')->default(0);
                $table->integer('weight_grams')->default(0);
                $table->integer('volume_cm3')->default(0);
                $table->jsonb('properties')->nullable()->comment('Цвет, текстура, размеры');
                $table->jsonb('compatibility_rules')->nullable()->comment('Матрица ограничений');
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();

                $table->comment('Опции и комплектующие для мебельных конструкторов');
            });
        }

        // 3. Формулы калькуляторов (Кирпич, Бетон, Кровля)
        if (!Schema::hasTable('calculator_formulas')) {
            Schema::create('calculator_formulas', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->string('name');
                $table->string('type')->comment('brick, concrete, roofing, floor');
                $table->jsonb('formula_data')->comment('Математические веса, коэффициенты запаса, швы');
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();

                $table->comment('Формулы и константы для строительных калькуляторов (ГОСТ/СНиП)');
            });
        }

        // 4. Сохраненные конфигурации (Проекты пользователей)
        if (!Schema::hasTable('saved_configurations')) {
            Schema::create('saved_configurations', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->foreignId('template_id')->constrained('configurator_templates');
                $table->string('project_name')->nullable();
                $table->jsonb('payload')->comment('Выбранные модули, размеры, материалы');
                $table->integer('total_price_kopeks')->default(0);
                $table->integer('total_weight_grams')->default(0);
                $table->string('status')->default('draft')->comment('draft, ordered, archived');
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();

                $table->comment('Сохраненные проекты и расчеты пользователей');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('saved_configurations');
        Schema::dropIfExists('calculator_formulas');
        Schema::dropIfExists('configurator_options');
        Schema::dropIfExists('configurator_templates');
    }
};
