<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CRM-система полная: клиенты, сегменты, автоматизация, взаимодействия, кампании.
 * Расширение для вертикалей Beauty, Hotels, Flowers и универсальная структура.
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 */
return new class extends Migration
{
    public function up(): void
    {
        // =====================================================================
        // 1. Клиенты CRM (основная карточка клиента)
        // =====================================================================
        Schema::create('crm_clients', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('business_group_id')->nullable()->constrained('business_groups')->onDelete('set null');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('uuid', 36)->unique();
            $table->string('correlation_id', 64)->nullable()->index();
            $table->json('tags')->nullable();

            // Основная информация
            $table->string('first_name');
            $table->string('last_name')->nullable();
            $table->string('company_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('phone_secondary', 30)->nullable();

            // Тип и статус
            $table->string('client_type', 30)->default('individual'); // individual, corporate, partner, wholesaler
            $table->string('status', 30)->default('active'); // active, inactive, blacklist, vip
            $table->string('source', 50)->nullable(); // website, referral, social, walk_in, ad_campaign
            $table->string('vertical', 50)->nullable(); // beauty, hotels, flowers, food и т.д.

            // Адреса (до 5)
            $table->json('addresses')->nullable();

            // Финансовая сводка (денормализация для быстрого отображения)
            $table->decimal('total_spent', 14, 2)->default(0);
            $table->unsignedInteger('total_orders')->default(0);
            $table->decimal('average_order_value', 14, 2)->default(0);
            $table->unsignedInteger('bonus_points')->default(0);
            $table->string('loyalty_tier', 20)->default('standard'); // standard, silver, gold, platinum, vip

            // Сегментация
            $table->string('segment', 30)->nullable(); // vip, loyal, sleeping, new, at_risk
            $table->timestamp('last_interaction_at')->nullable();
            $table->timestamp('last_order_at')->nullable();

            // Предпочтения (универсальные)
            $table->json('preferences')->nullable();
            $table->json('special_notes')->nullable();
            $table->text('internal_notes')->nullable();

            // Дополнительные вертикально-специфичные данные
            $table->json('vertical_data')->nullable();

            // Фото
            $table->string('avatar_url')->nullable();

            // Предпочтительный язык
            $table->string('preferred_language', 5)->default('ru');

            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'segment']);
            $table->index(['tenant_id', 'vertical']);
            $table->index(['tenant_id', 'client_type']);
            $table->index(['tenant_id', 'last_order_at']);
            $table->index(['tenant_id', 'loyalty_tier']);
            $table->index(['email']);
            $table->index(['phone']);
            $table->comment('CRM clients — карточки клиентов (универсальная + vertical_data)');
        });

        // =====================================================================
        // 2. История взаимодействий (лента)
        // =====================================================================
        Schema::create('crm_interactions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('crm_client_id')->constrained('crm_clients')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null'); // менеджер
            $table->string('uuid', 36)->unique();
            $table->string('correlation_id', 64)->nullable()->index();

            $table->string('type', 30); // call, email, sms, visit, order, complaint, note, system
            $table->string('channel', 30)->nullable(); // phone, email, whatsapp, telegram, in_app, walk_in
            $table->string('direction', 10)->nullable(); // inbound, outbound
            $table->string('subject')->nullable();
            $table->text('content');
            $table->json('metadata')->nullable(); // duration, attachments и т.д.

            $table->timestamp('interacted_at');
            $table->timestamps();

            $table->index(['crm_client_id', 'interacted_at']);
            $table->index(['tenant_id', 'type']);
            $table->comment('CRM interactions — история всех взаимодействий с клиентом');
        });

        // =====================================================================
        // 3. Сегменты клиентов
        // =====================================================================
        Schema::create('crm_segments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('uuid', 36)->unique();
            $table->string('correlation_id', 64)->nullable()->index();
            $table->json('tags')->nullable();

            $table->string('name');
            $table->string('slug', 50)->nullable();
            $table->text('description')->nullable();
            $table->string('vertical', 50)->nullable();
            $table->boolean('is_dynamic')->default(true); // динамический или статический

            // Правила фильтрации (JSON-документ)
            $table->json('rules'); // [{field, operator, value}, ...]
            $table->unsignedInteger('clients_count')->default(0);
            $table->timestamp('last_calculated_at')->nullable();
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(['tenant_id', 'is_active']);
            $table->unique(['tenant_id', 'slug']);
            $table->comment('CRM segments — правила и подсчёт клиентов');
        });

        // Связь сегментов с клиентами (для статических сегментов)
        Schema::create('crm_client_segment', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('crm_client_id')->constrained('crm_clients')->onDelete('cascade');
            $table->foreignId('crm_segment_id')->constrained('crm_segments')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['crm_client_id', 'crm_segment_id']);
        });

        // =====================================================================
        // 4. Автоматизация маркетинга (триггерные кампании)
        // =====================================================================
        Schema::create('crm_automations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('uuid', 36)->unique();
            $table->string('correlation_id', 64)->nullable()->index();
            $table->json('tags')->nullable();

            $table->string('name');
            $table->text('description')->nullable();
            $table->string('vertical', 50)->nullable();
            $table->boolean('is_active')->default(false);

            // Триггер
            $table->string('trigger_type', 50); // birthday, inactivity, post_order, post_visit, signup, custom_date, abandoned_cart
            $table->json('trigger_config'); // {days_before: 3, segment_id: 5, ...}

            // Действие
            $table->string('action_type', 50); // send_email, send_sms, send_push, send_whatsapp, award_bonus, create_task
            $table->json('action_config'); // {template_id: 10, bonus_amount: 100, ...}

            // Расписание / задержка
            $table->string('delay_type', 20)->default('immediate'); // immediate, delay, scheduled
            $table->unsignedInteger('delay_minutes')->default(0);

            // Статистика
            $table->unsignedInteger('total_sent')->default(0);
            $table->unsignedInteger('total_opened')->default(0);
            $table->unsignedInteger('total_clicked')->default(0);
            $table->unsignedInteger('total_converted')->default(0);

            $table->timestamps();

            $table->index(['tenant_id', 'is_active']);
            $table->index(['trigger_type']);
            $table->comment('CRM automations — триггерные кампании маркетинга');
        });

        // =====================================================================
        // 5. Журнал выполнения автоматизаций
        // =====================================================================
        Schema::create('crm_automation_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('crm_automation_id')->constrained('crm_automations')->onDelete('cascade');
            $table->foreignId('crm_client_id')->constrained('crm_clients')->onDelete('cascade');
            $table->string('correlation_id', 64)->nullable()->index();

            $table->string('status', 20)->default('sent'); // sent, opened, clicked, converted, failed, skipped
            $table->json('result_data')->nullable();
            $table->text('error_message')->nullable();

            $table->timestamp('executed_at');
            $table->timestamps();

            $table->index(['crm_automation_id', 'executed_at']);
            $table->index(['crm_client_id', 'executed_at']);
            $table->comment('CRM automation execution log');
        });

        // =====================================================================
        // 6. Beauty-специфичные данные клиента
        // =====================================================================
        Schema::create('crm_beauty_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('crm_client_id')->constrained('crm_clients')->onDelete('cascade');
            $table->string('correlation_id', 64)->nullable()->index();

            // Медицинская карта
            $table->json('allergies')->nullable(); // ['никель', 'латекс', ...]
            $table->string('skin_type', 30)->nullable(); // dry, oily, combination, normal, sensitive
            $table->json('contraindications')->nullable();
            $table->string('hair_type', 30)->nullable();
            $table->string('hair_color', 30)->nullable();
            $table->string('face_shape', 30)->nullable();

            // Предпочтения по мастерам и услугам
            $table->json('preferred_masters')->nullable(); // [master_id, ...]
            $table->json('preferred_services')->nullable(); // [service_id, ...]
            $table->json('favorite_products')->nullable();

            // История фото «до/после»
            $table->json('before_after_photos')->nullable(); // [{date, before_url, after_url, service}, ...]

            // Важные даты
            $table->date('birthday')->nullable();
            $table->json('special_dates')->nullable(); // [{label, date}, ...]

            $table->timestamps();

            $table->unique(['crm_client_id']);
            $table->comment('CRM Beauty — медкарта и предпочтения клиента салона');
        });

        // =====================================================================
        // 7. Hotels-специфичные данные гостя
        // =====================================================================
        Schema::create('crm_hotel_guest_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('crm_client_id')->constrained('crm_clients')->onDelete('cascade');
            $table->string('correlation_id', 64)->nullable()->index();

            // Предпочтения проживания
            $table->string('preferred_room_type', 50)->nullable();
            $table->string('preferred_floor', 20)->nullable(); // high, low, any
            $table->string('preferred_view', 30)->nullable(); // sea, city, garden, pool
            $table->json('preferred_amenities')->nullable(); // ['завтрак в номер', 'поздний выезд', ...]

            // Особые отметки
            $table->boolean('is_smoking')->default(false);
            $table->boolean('has_pets')->default(false);
            $table->boolean('is_vip_service')->default(false);
            $table->json('dietary_restrictions')->nullable();
            $table->json('allergies')->nullable();
            $table->string('preferred_language', 5)->default('ru');

            // Документы
            $table->string('passport_country', 3)->nullable();
            $table->string('frequent_guest_number', 50)->nullable();

            // Важные даты
            $table->date('birthday')->nullable();
            $table->json('special_dates')->nullable();

            // История отзывов (средний рейтинг)
            $table->decimal('average_review_rating', 3, 2)->default(0);
            $table->unsignedInteger('total_stays')->default(0);
            $table->unsignedInteger('total_nights')->default(0);

            $table->timestamps();

            $table->unique(['crm_client_id']);
            $table->comment('CRM Hotels — профиль гостя и предпочтения проживания');
        });

        // =====================================================================
        // 8. Flowers-специфичные данные клиента
        // =====================================================================
        Schema::create('crm_flower_client_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('crm_client_id')->constrained('crm_clients')->onDelete('cascade');
            $table->string('correlation_id', 64)->nullable()->index();

            // Предпочтения по цветам
            $table->json('favorite_flowers')->nullable(); // ['розы', 'пионы', ...]
            $table->json('disliked_flowers')->nullable(); // запрет на определённые цветы
            $table->json('preferred_styles')->nullable(); // ['классический', 'современный', ...]
            $table->json('preferred_colors')->nullable(); // ['красный', 'белый', ...]
            $table->decimal('average_budget', 10, 2)->default(0);

            // Поводы и важные даты (дни рождения близких, годовщины, корпоративы)
            $table->json('occasions')->nullable(); // [{label, date, recipient_name, note}, ...]

            // Предпочтения упаковки
            $table->json('packaging_preferences')->nullable();

            // Аллергии на цветы
            $table->json('flower_allergies')->nullable();

            // Получатели (часто отправляют одним и тем же людям)
            $table->json('frequent_recipients')->nullable(); // [{name, phone, address}, ...]

            // Корпоративные данные
            $table->boolean('is_corporate')->default(false);
            $table->json('corporate_holidays')->nullable(); // [{name, date}, ...]

            $table->timestamps();

            $table->unique(['crm_client_id']);
            $table->comment('CRM Flowers — предпочтения клиента цветочного магазина');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_flower_client_profiles');
        Schema::dropIfExists('crm_hotel_guest_profiles');
        Schema::dropIfExists('crm_beauty_profiles');
        Schema::dropIfExists('crm_automation_logs');
        Schema::dropIfExists('crm_automations');
        Schema::dropIfExists('crm_client_segment');
        Schema::dropIfExists('crm_segments');
        Schema::dropIfExists('crm_interactions');
        Schema::dropIfExists('crm_clients');
    }
};
