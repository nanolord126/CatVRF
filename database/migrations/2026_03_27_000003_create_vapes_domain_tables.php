<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Vapes Domain Migration — Production Ready 2026
 *
 * Создание инфраструктуры для вейп-шопа с учетом требований 18+,
 * обязательной маркировки "Честный ЗНАК" и интеграции с ЕСИА/ЕБС.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Бренды вейпов и производителей
        if (!Schema::hasTable('vapes_brands')) {
            Schema::create('vapes_brands', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->string('name')->index();
                $table->string('country_code', 2)->nullable();
                $table->text('description')->nullable();
                $table->jsonb('metadata')->nullable();
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();
                $table->softDeletes();

                $table->comment('Бренды вейп-продукции и производители');
            });
        }

        // 2. Устройства и девайсы (POD-системы, Боксмоды)
        if (!Schema::hasTable('vapes_devices')) {
            Schema::create('vapes_devices', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->foreignId('brand_id')->constrained('vapes_brands');
                $table->string('model_name')->index();
                $table->string('type')->default('pod'); // pod, boxmod, disposable
                $table->integer('wattage_max')->nullable();
                $table->integer('battery_capacity_mah')->nullable();
                $table->bigInteger('price_kopecks');
                $table->integer('current_stock')->default(0);
                $table->boolean('has_marking_znack')->default(true); // Честный ЗНАК
                $table->jsonb('tags')->nullable();
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();
                $table->softDeletes();

                $table->comment('Устройства для парения (вейпы, поды)');
            });
        }

        // 3. Жидкости для электронных сигарет
        if (!Schema::hasTable('vapes_liquids')) {
            Schema::create('vapes_liquids', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->foreignId('brand_id')->constrained('vapes_brands');
                $table->string('flavor_name')->index();
                $table->integer('volume_ml')->index();
                $table->integer('nicotine_strength')->index(); // mg/ml
                $table->string('nicotine_type')->default('salt'); // salt, classic
                $table->bigInteger('price_kopecks');
                $table->integer('current_stock')->default(0);
                $table->string('gtin')->nullable()->index(); // Глобальный номер товарной продукции
                $table->string('marking_code_template')->nullable(); // Маска Честного ЗНАКа
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();
                $table->softDeletes();

                $table->comment('Жидкости для ЭСДН');
            });
        }

        // 4. Верификация возраста (ЕСИА / ЕБС / ID)
        if (!Schema::hasTable('vapes_age_verifications')) {
            Schema::create('vapes_age_verifications', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('user_id')->index();
                $table->string('method'); // esia, ebs, sber_id, t_id, manual
                $table->string('external_id')->nullable()->index(); // ID в стороннем сервисе
                $table->string('status')->default('pending'); // pending, verified, rejected
                $table->date('birth_date')->nullable();
                $table->timestamp('verified_at')->nullable();
                $table->jsonb('provider_response')->nullable();
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();

                $table->comment('История проверок возраста (18+) для вейп-вертикали');
            });
        }

        // 5. Заказы вейп-продукции
        if (!Schema::hasTable('vapes_orders')) {
            Schema::create('vapes_orders', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('user_id')->index();
                $table->foreignId('age_verification_id')->nullable()->constrained('vapes_age_verifications');
                $table->string('status')->default('draft'); // draft, age_pending, paid, shipped, delivered, cancelled
                $table->bigInteger('total_amount_kopecks')->default(0);
                $table->jsonb('items')->nullable(); // Список SKU, цен и кодов маркировки
                $table->string('marking_session_id')->nullable(); // ID сессии Честного ЗНАКа
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();
                $table->softDeletes();

                $table->comment('Заказы вейп-продукции с жесткой привязкой к верификатору возраста');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vapes_orders');
        Schema::dropIfExists('vapes_age_verifications');
        Schema::dropIfExists('vapes_liquids');
        Schema::dropIfExists('vapes_devices');
        Schema::dropIfExists('vapes_brands');
    }
};
