<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Мега-миграция: таблицы для 10 стаб-вертикалей (КАНОН 2026).
 * FarmDirect · HealthyFood · Confectionery · Pharmacy · MeatShops
 * OfficeCatering · Furniture · Electronics · ToysKids · AutoParts
 * Каждая секция идемпотентна (hasTable guard).
 */
return new class extends Migration
{
    public function up(): void
    {
        // ═══════════════════════════════════════════════════════
        // 1. FARM DIRECT
        // ═══════════════════════════════════════════════════════
        if (!Schema::hasTable('farms')) {
            Schema::create('farms', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('business_group_id')->nullable();
                $table->uuid('uuid')->nullable()->unique()->index();
                $table->string('correlation_id', 36)->nullable()->index();
                $table->string('name');
                $table->text('description')->nullable();
                $table->string('owner_name')->nullable();
                $table->string('phone', 32)->nullable();
                $table->text('address')->nullable();
                $table->decimal('geo_lat', 10, 7)->nullable();
                $table->decimal('geo_lng', 10, 7)->nullable();
                $table->string('inn', 12)->nullable();
                $table->boolean('is_verified')->default(false);
                $table->boolean('is_eco_certified')->default(false);
                $table->decimal('rating', 3, 1)->default(0.0);
                $table->string('status', 32)->default('active')->index();
                $table->jsonb('tags')->nullable();
                $table->softDeletes();
                $table->timestamps();
                $table->index(['tenant_id', 'status']);
                $table->comment('Фермы (прямые поставщики)');
            });
        }

        if (!Schema::hasTable('farm_products')) {
            Schema::create('farm_products', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('farm_id')->index();
                $table->uuid('uuid')->nullable()->unique()->index();
                $table->string('correlation_id', 36)->nullable()->index();
                $table->string('name');
                $table->string('category', 64)->nullable()->index();
                $table->string('unit', 16)->default('kg');
                $table->unsignedInteger('price')->default(0)->comment('В копейках');
                $table->unsignedInteger('current_stock')->default(0);
                $table->boolean('is_seasonal')->default(false);
                $table->jsonb('season_months')->nullable();
                $table->boolean('is_eco_certified')->default(false);
                $table->date('harvest_date')->nullable();
                $table->string('status', 32)->default('active')->index();
                $table->jsonb('tags')->nullable();
                $table->softDeletes();
                $table->timestamps();
                $table->index(['tenant_id', 'status']);
                $table->comment('Продукты ферм (прямые поставки)');
            });
        }

        if (!Schema::hasTable('farm_orders')) {
            Schema::create('farm_orders', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('client_id')->index();
                $table->unsignedBigInteger('farm_id')->index();
                $table->uuid('uuid')->nullable()->unique()->index();
                $table->string('correlation_id', 36)->nullable()->index();
                $table->string('idempotency_key', 64)->nullable()->unique()->index();
                $table->jsonb('items')->nullable();
                $table->unsignedInteger('total_amount')->default(0)->comment('В копейках');
                $table->text('delivery_address');
                $table->date('delivery_date')->nullable()->index();
                $table->string('status', 32)->default('pending')->index();
                $table->string('payment_status', 32)->default('awaiting');
                $table->timestamp('shipped_at')->nullable();
                $table->timestamp('delivered_at')->nullable();
                $table->jsonb('tags')->nullable();
                $table->softDeletes();
                $table->timestamps();
                $table->index(['tenant_id', 'status']);
                $table->comment('Заказы с ферм (Farm-to-table)');
            });
        }

        // ═══════════════════════════════════════════════════════
        // 2. HEALTHY FOOD
        // ═══════════════════════════════════════════════════════
        if (!Schema::hasTable('healthy_meals')) {
            Schema::create('healthy_meals', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->uuid('uuid')->nullable()->unique()->index();
                $table->string('correlation_id', 36)->nullable()->index();
                $table->string('name');
                $table->text('description')->nullable();
                $table->string('diet_type', 64)->nullable()->index()->comment('keto,vegan,paleo,low_carb');
                $table->unsignedSmallInteger('calories')->default(0);
                $table->unsignedSmallInteger('protein_g')->default(0);
                $table->unsignedSmallInteger('fat_g')->default(0);
                $table->unsignedSmallInteger('carbs_g')->default(0);
                $table->unsignedInteger('price')->default(0)->comment('В копейках');
                $table->unsignedSmallInteger('prep_time_min')->default(20);
                $table->jsonb('allergens')->nullable();
                $table->string('photo_url')->nullable();
                $table->string('status', 32)->default('active')->index();
                $table->jsonb('tags')->nullable();
                $table->softDeletes();
                $table->timestamps();
                $table->index(['tenant_id', 'diet_type']);
                $table->comment('Здоровые блюда (правильное питание)');
            });
        }

        if (!Schema::hasTable('diet_plans')) {
            Schema::create('diet_plans', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('client_id')->index();
                $table->uuid('uuid')->nullable()->unique()->index();
                $table->string('correlation_id', 36)->nullable()->index();
                $table->string('name');
                $table->string('diet_type', 64)->nullable();
                $table->unsignedSmallInteger('duration_days')->default(7);
                $table->unsignedSmallInteger('daily_calories')->default(2000);
                $table->unsignedInteger('price_per_day')->default(0)->comment('В копейках');
                $table->jsonb('schedule')->nullable()->comment('Расписание блюд по дням');
                $table->string('status', 32)->default('active')->index();
                $table->date('starts_at')->nullable();
                $table->date('ends_at')->nullable();
                $table->jsonb('tags')->nullable();
                $table->softDeletes();
                $table->timestamps();
                $table->index(['tenant_id', 'client_id']);
                $table->comment('Диетические планы питания');
            });
        }

        if (!Schema::hasTable('meal_subscriptions')) {
            Schema::create('meal_subscriptions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('client_id')->index();
                $table->unsignedBigInteger('diet_plan_id')->nullable()->index();
                $table->uuid('uuid')->nullable()->unique()->index();
                $table->string('correlation_id', 36)->nullable()->index();
                $table->string('frequency', 32)->default('weekly');
                $table->date('next_delivery_date')->nullable()->index();
                $table->text('delivery_address');
                $table->unsignedInteger('price_per_delivery')->default(0)->comment('В копейках');
                $table->string('status', 32)->default('active')->index();
                $table->date('paused_until')->nullable();
                $table->unsignedInteger('total_deliveries')->default(0);
                $table->jsonb('tags')->nullable();
                $table->softDeletes();
                $table->timestamps();
                $table->index(['tenant_id', 'status']);
                $table->comment('Подписки на доставку здорового питания');
            });
        }

        // ═══════════════════════════════════════════════════════
        // 3. CONFECTIONERY
        // ═══════════════════════════════════════════════════════
        if (!Schema::hasTable('confectionery_products')) {
            Schema::create('confectionery_products', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->uuid('uuid')->nullable()->unique()->index();
                $table->string('correlation_id', 36)->nullable()->index();
                $table->string('name');
                $table->text('description')->nullable();
                $table->string('type', 32)->default('cake')->index()->comment('cake,pastry,cookie,candy');
                $table->unsignedInteger('price')->default(0)->comment('В копейках');
                $table->decimal('weight_kg', 5, 2)->nullable();
                $table->string('filling')->nullable();
                $table->jsonb('allergens')->nullable();
                $table->boolean('is_custom')->default(false);
                $table->unsignedSmallInteger('prep_hours')->default(24);
                $table->string('photo_url')->nullable();
                $table->string('status', 32)->default('active')->index();
                $table->jsonb('tags')->nullable();
                $table->softDeletes();
                $table->timestamps();
                $table->index(['tenant_id', 'type']);
                $table->comment('Кондитерские изделия (торты, выпечка)');
            });
        }

        if (!Schema::hasTable('bakery_orders')) {
            Schema::create('bakery_orders', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('client_id')->index();
                $table->unsignedBigInteger('product_id')->nullable()->index();
                $table->uuid('uuid')->nullable()->unique()->index();
                $table->string('correlation_id', 36)->nullable()->index();
                $table->string('idempotency_key', 64)->nullable()->unique()->index();
                $table->unsignedInteger('quantity')->default(1);
                $table->unsignedInteger('total_amount')->default(0)->comment('В копейках');
                $table->text('custom_design_desc')->nullable();
                $table->string('inscription')->nullable();
                $table->timestamp('ready_at')->nullable();
                $table->timestamp('delivery_at')->nullable();
                $table->text('delivery_address')->nullable();
                $table->string('status', 32)->default('pending')->index();
                $table->string('payment_status', 32)->default('awaiting');
                $table->jsonb('tags')->nullable();
                $table->softDeletes();
                $table->timestamps();
                $table->index(['tenant_id', 'status']);
                $table->comment('Заказы на выпечку и торты');
            });
        }

        // ═══════════════════════════════════════════════════════
        // 4. PHARMACY
        // ═══════════════════════════════════════════════════════
        if (!Schema::hasTable('medicines')) {
            Schema::create('medicines', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('pharmacy_id')->nullable()->index();
                $table->uuid('uuid')->nullable()->unique()->index();
                $table->string('correlation_id', 36)->nullable()->index();
                $table->string('name');
                $table->string('inn_name')->nullable()->comment('МНН (действующее вещество)');
                $table->string('dosage_form', 64)->nullable()->comment('таблетки, капли, мазь...');
                $table->string('dosage', 64)->nullable();
                $table->boolean('prescription_required')->default(false)->index();
                $table->text('contraindications')->nullable();
                $table->unsignedInteger('price')->default(0)->comment('В копейках');
                $table->unsignedInteger('current_stock')->default(0);
                $table->date('expiry_date')->nullable();
                $table->boolean('requires_cold_chain')->default(false);
                $table->string('status', 32)->default('active')->index();
                $table->jsonb('tags')->nullable();
                $table->softDeletes();
                $table->timestamps();
                $table->index(['tenant_id', 'prescription_required']);
                $table->comment('Лекарственные препараты');
            });
        }

        if (!Schema::hasTable('prescriptions')) {
            Schema::create('prescriptions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('client_id')->index();
                $table->uuid('uuid')->nullable()->unique()->index();
                $table->string('correlation_id', 36)->nullable()->index();
                $table->string('doctor_name')->nullable();
                $table->string('doctor_license', 64)->nullable();
                $table->date('issued_at')->nullable();
                $table->date('valid_until')->nullable();
                $table->string('scan_url')->nullable()->comment('Скан рецепта');
                $table->string('egisz_number', 64)->nullable()->comment('Номер в ЕГИСЗ');
                $table->string('status', 32)->default('pending')->index()->comment('pending,verified,rejected,expired');
                $table->text('rejection_reason')->nullable();
                $table->timestamp('verified_at')->nullable();
                $table->jsonb('tags')->nullable();
                $table->softDeletes();
                $table->timestamps();
                $table->index(['tenant_id', 'client_id']);
                $table->comment('Рецепты (для рецептурных препаратов)');
            });
        }

        if (!Schema::hasTable('pharmacy_orders')) {
            Schema::create('pharmacy_orders', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('client_id')->index();
                $table->unsignedBigInteger('prescription_id')->nullable()->index();
                $table->uuid('uuid')->nullable()->unique()->index();
                $table->string('correlation_id', 36)->nullable()->index();
                $table->string('idempotency_key', 64)->nullable()->unique()->index();
                $table->jsonb('items')->nullable();
                $table->unsignedInteger('total_amount')->default(0)->comment('В копейках');
                $table->text('delivery_address');
                $table->boolean('requires_cold_chain')->default(false);
                $table->string('status', 32)->default('pending')->index();
                $table->string('payment_status', 32)->default('awaiting');
                $table->timestamp('delivered_at')->nullable();
                $table->jsonb('tags')->nullable();
                $table->softDeletes();
                $table->timestamps();
                $table->index(['tenant_id', 'status']);
                $table->comment('Заказы в аптеке (с доставкой)');
            });
        }

        // ═══════════════════════════════════════════════════════
        // 5. MEAT SHOPS
        // ═══════════════════════════════════════════════════════
        if (!Schema::hasTable('meat_products')) {
            Schema::create('meat_products', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->uuid('uuid')->nullable()->unique()->index();
                $table->string('correlation_id', 36)->nullable()->index();
                $table->string('name');
                $table->string('animal_type', 32)->nullable()->index()->comment('beef,pork,lamb,chicken,turkey');
                $table->string('cut_type', 64)->nullable()->comment('вырезка,шея,лопатка...');
                $table->string('unit', 16)->default('kg');
                $table->unsignedInteger('price_per_unit')->default(0)->comment('В копейках');
                $table->unsignedInteger('current_stock')->default(0)->comment('В граммах');
                $table->boolean('is_farm_raised')->default(false);
                $table->boolean('is_halal')->default(false);
                $table->boolean('has_vet_certificate')->default(false);
                $table->string('vet_certificate_num', 64)->nullable();
                $table->boolean('is_vacuum_packed')->default(true);
                $table->string('status', 32)->default('active')->index();
                $table->jsonb('tags')->nullable();
                $table->softDeletes();
                $table->timestamps();
                $table->index(['tenant_id', 'animal_type']);
                $table->comment('Мясные продукты');
            });
        }

        if (!Schema::hasTable('meat_orders')) {
            Schema::create('meat_orders', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('client_id')->index();
                $table->uuid('uuid')->nullable()->unique()->index();
                $table->string('correlation_id', 36)->nullable()->index();
                $table->string('idempotency_key', 64)->nullable()->unique()->index();
                $table->jsonb('items')->nullable();
                $table->unsignedInteger('total_amount')->default(0)->comment('В копейках');
                $table->text('delivery_address');
                $table->boolean('is_box_subscription')->default(false);
                $table->string('cutting_instructions')->nullable();
                $table->string('status', 32)->default('pending')->index();
                $table->string('payment_status', 32)->default('awaiting');
                $table->timestamp('packed_at')->nullable();
                $table->timestamp('delivered_at')->nullable();
                $table->jsonb('tags')->nullable();
                $table->softDeletes();
                $table->timestamps();
                $table->index(['tenant_id', 'status']);
                $table->comment('Заказы мясных продуктов');
            });
        }

        // ═══════════════════════════════════════════════════════
        // 6. OFFICE CATERING
        // ═══════════════════════════════════════════════════════
        if (!Schema::hasTable('corporate_clients')) {
            Schema::create('corporate_clients', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->uuid('uuid')->nullable()->unique()->index();
                $table->string('correlation_id', 36)->nullable()->index();
                $table->string('company_name');
                $table->string('inn', 12)->nullable();
                $table->string('contact_name')->nullable();
                $table->string('contact_phone', 32)->nullable();
                $table->string('contact_email', 128)->nullable();
                $table->text('office_address');
                $table->unsignedSmallInteger('employee_count')->default(1);
                $table->string('status', 32)->default('active')->index();
                $table->jsonb('tags')->nullable();
                $table->softDeletes();
                $table->timestamps();
                $table->index(['tenant_id', 'status']);
                $table->comment('Корпоративные клиенты (офисное питание)');
            });
        }

        if (!Schema::hasTable('office_menus')) {
            Schema::create('office_menus', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->uuid('uuid')->nullable()->unique()->index();
                $table->string('correlation_id', 36)->nullable()->index();
                $table->string('name');
                $table->text('description')->nullable();
                $table->unsignedInteger('price_per_person')->default(0)->comment('В копейках');
                $table->jsonb('dishes')->nullable();
                $table->string('status', 32)->default('active')->index();
                $table->jsonb('tags')->nullable();
                $table->softDeletes();
                $table->timestamps();
                $table->index(['tenant_id', 'status']);
                $table->comment('Корпоративные меню');
            });
        }

        if (!Schema::hasTable('corporate_orders')) {
            Schema::create('corporate_orders', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('corporate_client_id')->index();
                $table->unsignedBigInteger('office_menu_id')->nullable()->index();
                $table->uuid('uuid')->nullable()->unique()->index();
                $table->string('correlation_id', 36)->nullable()->index();
                $table->string('idempotency_key', 64)->nullable()->unique()->index();
                $table->unsignedSmallInteger('persons_count')->default(1);
                $table->unsignedInteger('total_amount')->default(0)->comment('В копейках');
                $table->date('delivery_date')->nullable()->index();
                $table->time('delivery_time')->nullable();
                $table->text('delivery_address');
                $table->string('status', 32)->default('pending')->index();
                $table->string('payment_status', 32)->default('awaiting');
                $table->boolean('is_recurring')->default(false);
                $table->string('recurrence', 32)->nullable()->comment('daily,weekly,monthly');
                $table->timestamp('delivered_at')->nullable();
                $table->jsonb('tags')->nullable();
                $table->softDeletes();
                $table->timestamps();
                $table->index(['tenant_id', 'status']);
                $table->comment('Корпоративные заказы питания');
            });
        }

        // ═══════════════════════════════════════════════════════
        // 7. FURNITURE
        // ═══════════════════════════════════════════════════════
        if (!Schema::hasTable('furniture_items')) {
            Schema::create('furniture_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->uuid('uuid')->nullable()->unique()->index();
                $table->string('correlation_id', 36)->nullable()->index();
                $table->string('name');
                $table->text('description')->nullable();
                $table->string('category', 64)->nullable()->index()->comment('sofa,table,chair,wardrobe...');
                $table->string('material', 64)->nullable();
                $table->string('style', 64)->nullable()->comment('modern,classic,loft...');
                $table->unsignedInteger('price')->default(0)->comment('В копейках');
                $table->unsignedInteger('current_stock')->default(0);
                $table->string('dimensions')->nullable()->comment('ДхШхВ в см');
                $table->decimal('weight_kg', 6, 2)->nullable();
                $table->boolean('assembly_required')->default(true);
                $table->unsignedInteger('assembly_price')->default(0)->comment('В копейках');
                $table->string('photo_url')->nullable();
                $table->string('status', 32)->default('active')->index();
                $table->jsonb('tags')->nullable();
                $table->softDeletes();
                $table->timestamps();
                $table->index(['tenant_id', 'category']);
                $table->comment('Мебель и товары для интерьера');
            });
        }

        if (!Schema::hasTable('furniture_orders')) {
            Schema::create('furniture_orders', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('client_id')->index();
                $table->uuid('uuid')->nullable()->unique()->index();
                $table->string('correlation_id', 36)->nullable()->index();
                $table->string('idempotency_key', 64)->nullable()->unique()->index();
                $table->jsonb('items')->nullable();
                $table->unsignedInteger('total_amount')->default(0)->comment('В копейках');
                $table->unsignedInteger('assembly_amount')->default(0)->comment('В копейках');
                $table->text('delivery_address');
                $table->timestamp('delivery_slot')->nullable();
                $table->timestamp('assembly_slot')->nullable();
                $table->string('status', 32)->default('pending')->index();
                $table->string('payment_status', 32)->default('awaiting');
                $table->timestamp('delivered_at')->nullable();
                $table->timestamp('assembled_at')->nullable();
                $table->jsonb('tags')->nullable();
                $table->softDeletes();
                $table->timestamps();
                $table->index(['tenant_id', 'status']);
                $table->comment('Заказы мебели (доставка + сборка)');
            });
        }

        // ═══════════════════════════════════════════════════════
        // 8. ELECTRONICS
        // ═══════════════════════════════════════════════════════
        if (!Schema::hasTable('electronic_products')) {
            Schema::create('electronic_products', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->uuid('uuid')->nullable()->unique()->index();
                $table->string('correlation_id', 36)->nullable()->index();
                $table->string('name');
                $table->text('description')->nullable();
                $table->string('category', 64)->nullable()->index()->comment('smartphone,laptop,tv,tablet...');
                $table->string('brand', 64)->nullable()->index();
                $table->string('sku', 64)->nullable()->index();
                $table->unsignedInteger('price')->default(0)->comment('В копейках');
                $table->unsignedInteger('current_stock')->default(0);
                $table->unsignedSmallInteger('warranty_months')->default(12);
                $table->jsonb('specifications')->nullable();
                $table->string('photo_url')->nullable();
                $table->string('status', 32)->default('active')->index();
                $table->jsonb('tags')->nullable();
                $table->softDeletes();
                $table->timestamps();
                $table->index(['tenant_id', 'brand']);
                $table->comment('Электроника и гаджеты');
            });
        }

        if (!Schema::hasTable('electronic_orders')) {
            Schema::create('electronic_orders', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('client_id')->index();
                $table->uuid('uuid')->nullable()->unique()->index();
                $table->string('correlation_id', 36)->nullable()->index();
                $table->string('idempotency_key', 64)->nullable()->unique()->index();
                $table->jsonb('items')->nullable();
                $table->unsignedInteger('total_amount')->default(0)->comment('В копейках');
                $table->text('delivery_address');
                $table->string('status', 32)->default('pending')->index();
                $table->string('payment_status', 32)->default('awaiting');
                $table->timestamp('delivered_at')->nullable();
                $table->jsonb('tags')->nullable();
                $table->softDeletes();
                $table->timestamps();
                $table->index(['tenant_id', 'status']);
                $table->comment('Заказы электроники');
            });
        }

        if (!Schema::hasTable('warranty_claims')) {
            Schema::create('warranty_claims', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('client_id')->index();
                $table->unsignedBigInteger('product_id')->index();
                $table->unsignedBigInteger('order_id')->nullable()->index();
                $table->uuid('uuid')->nullable()->unique()->index();
                $table->string('correlation_id', 36)->nullable()->index();
                $table->text('issue_description');
                $table->string('status', 32)->default('submitted')->index()->comment('submitted,reviewing,approved,rejected,repaired,replaced,refunded');
                $table->date('purchase_date')->nullable();
                $table->date('warranty_expires')->nullable();
                $table->text('resolution_notes')->nullable();
                $table->timestamp('resolved_at')->nullable();
                $table->jsonb('tags')->nullable();
                $table->softDeletes();
                $table->timestamps();
                $table->index(['tenant_id', 'status']);
                $table->comment('Гарантийные обращения по электронике');
            });
        }

        // ═══════════════════════════════════════════════════════
        // 9. TOYS & KIDS
        // ═══════════════════════════════════════════════════════
        if (!Schema::hasTable('toy_products')) {
            Schema::create('toy_products', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->uuid('uuid')->nullable()->unique()->index();
                $table->string('correlation_id', 36)->nullable()->index();
                $table->string('name');
                $table->text('description')->nullable();
                $table->string('category', 64)->nullable()->index()->comment('dolls,cars,puzzles,educational...');
                $table->string('brand', 64)->nullable();
                $table->unsignedSmallInteger('age_min_years')->default(0)->index();
                $table->unsignedSmallInteger('age_max_years')->default(12)->index();
                $table->string('gender', 16)->default('unisex')->index()->comment('boy,girl,unisex');
                $table->unsignedInteger('price')->default(0)->comment('В копейках');
                $table->unsignedInteger('current_stock')->default(0);
                $table->boolean('has_safety_certificate')->default(false);
                $table->string('safety_certificate_num', 64)->nullable();
                $table->boolean('gift_wrapping_available')->default(true);
                $table->string('photo_url')->nullable();
                $table->string('status', 32)->default('active')->index();
                $table->jsonb('tags')->nullable();
                $table->softDeletes();
                $table->timestamps();
                $table->index(['tenant_id', 'age_min_years', 'age_max_years']);
                $table->comment('Игрушки и товары для детей');
            });
        }

        if (!Schema::hasTable('toy_orders')) {
            Schema::create('toy_orders', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('client_id')->index();
                $table->uuid('uuid')->nullable()->unique()->index();
                $table->string('correlation_id', 36)->nullable()->index();
                $table->string('idempotency_key', 64)->nullable()->unique()->index();
                $table->jsonb('items')->nullable();
                $table->unsignedInteger('total_amount')->default(0)->comment('В копейках');
                $table->text('delivery_address');
                $table->boolean('gift_wrapping')->default(false);
                $table->string('gift_message')->nullable();
                $table->string('status', 32)->default('pending')->index();
                $table->string('payment_status', 32)->default('awaiting');
                $table->timestamp('delivered_at')->nullable();
                $table->jsonb('tags')->nullable();
                $table->softDeletes();
                $table->timestamps();
                $table->index(['tenant_id', 'status']);
                $table->comment('Заказы игрушек');
            });
        }

        // ═══════════════════════════════════════════════════════
        // 10. AUTO PARTS
        // ═══════════════════════════════════════════════════════
        if (!Schema::hasTable('auto_part_items')) {
            Schema::create('auto_part_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->uuid('uuid')->nullable()->unique()->index();
                $table->string('correlation_id', 36)->nullable()->index();
                $table->string('name');
                $table->text('description')->nullable();
                $table->string('part_number', 64)->nullable()->index()->comment('OEM-номер');
                $table->string('brand', 64)->nullable()->index();
                $table->string('category', 64)->nullable()->index()->comment('engine,brakes,suspension,body...');
                $table->unsignedInteger('price')->default(0)->comment('В копейках');
                $table->unsignedInteger('current_stock')->default(0);
                $table->jsonb('compatible_vehicles')->nullable()->comment('[{make,model,year_from,year_to}]');
                $table->boolean('is_original')->default(false);
                $table->boolean('has_warranty')->default(false);
                $table->unsignedSmallInteger('warranty_months')->default(0);
                $table->string('photo_url')->nullable();
                $table->string('status', 32)->default('active')->index();
                $table->jsonb('tags')->nullable();
                $table->softDeletes();
                $table->timestamps();
                $table->index(['tenant_id', 'brand']);
                $table->index(['tenant_id', 'category']);
                $table->comment('Автозапчасти и аксессуары');
            });
        }

        if (!Schema::hasTable('auto_part_orders')) {
            Schema::create('auto_part_orders', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('client_id')->index();
                $table->uuid('uuid')->nullable()->unique()->index();
                $table->string('correlation_id', 36)->nullable()->index();
                $table->string('idempotency_key', 64)->nullable()->unique()->index();
                $table->string('vin', 17)->nullable()->index()->comment('VIN автомобиля');
                $table->jsonb('items')->nullable();
                $table->unsignedInteger('total_amount')->default(0)->comment('В копейках');
                $table->text('delivery_address');
                $table->string('status', 32)->default('pending')->index();
                $table->string('payment_status', 32)->default('awaiting');
                $table->timestamp('delivered_at')->nullable();
                $table->jsonb('tags')->nullable();
                $table->softDeletes();
                $table->timestamps();
                $table->index(['tenant_id', 'status']);
                $table->comment('Заказы автозапчастей');
            });
        }
    }

    public function down(): void
    {
        $tables = [
            'auto_part_orders', 'auto_part_items',
            'toy_orders', 'toy_products',
            'warranty_claims', 'electronic_orders', 'electronic_products',
            'furniture_orders', 'furniture_items',
            'corporate_orders', 'office_menus', 'corporate_clients',
            'meat_orders', 'meat_products',
            'pharmacy_orders', 'prescriptions', 'medicines',
            'bakery_orders', 'confectionery_products',
            'meal_subscriptions', 'diet_plans', 'healthy_meals',
            'farm_orders', 'farm_products', 'farms',
        ];

        foreach ($tables as $table) {
            Schema::dropIfExists($table);
        }
    }
};
