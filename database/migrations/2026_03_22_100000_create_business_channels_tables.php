<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * БИЗНЕС-КАНАЛЫ / НОВОСТИ / ПОДПИСКИ (КАНОН 2026)
 *
 * Таблицы:
 *   - channel_subscription_plans  — тарифные планы (49р / 199р)
 *   - business_channels           — каналы бизнеса (1 тенант = 1 канал)
 *   - posts                       — посты канала
 *   - post_media                  — медиафайлы поста
 *   - channel_subscribers         — подписчики (user → channel)
 *   - channel_subscription_usages — активные подписки бизнеса на тариф
 *   - post_reaction_logs          — лог реакций (фрод-детекция)
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('channel_subscription_plans')) {
            return;
        }

        // ─────────────────────────────────────────────
        // 1. Тарифные планы (BASIC / EXTENDED)
        // ─────────────────────────────────────────────
        Schema::create('channel_subscription_plans', function (Blueprint $table): void {
            $table->comment('Тарифные планы для бизнес-каналов. Не удалять — пересоздать.');
            $table->id();
            $table->string('slug', 32)->unique()->comment('basic | extended');
            $table->string('name', 128)->comment('Название тарифа');
            $table->integer('price_kopecks')->comment('Цена в копейках (4900 = 49₽)');
            $table->integer('posts_per_day')->comment('Максимум постов в сутки');
            $table->integer('photos_per_post')->comment('Максимум фото на пост');
            $table->boolean('shorts_enabled')->default(false)->comment('Разрешены Shorts/видео');
            $table->boolean('polls_enabled')->default(false)->comment('Разрешены опросы');
            $table->boolean('promo_enabled')->default(false)->comment('Разрешены промо-материалы');
            $table->boolean('advanced_stats')->default(false)->comment('Расширенная статистика');
            $table->boolean('scheduled_posts')->default(false)->comment('Отложенный постинг');
            $table->json('features')->nullable()->comment('Дополнительные возможности JSON');
            $table->boolean('is_active')->default(true)->comment('Тариф активен');
            $table->string('correlation_id', 64)->nullable()->index()->comment('Идентификатор операции');
            $table->timestamps();
        });

        // ─────────────────────────────────────────────
        // 2. Бизнес-каналы
        // ─────────────────────────────────────────────
        Schema::create('business_channels', function (Blueprint $table): void {
            $table->comment('Каналы бизнеса для публикации новостей. 1 tenant = 1 channel.');
            $table->id();
            $table->uuid('uuid')->unique()->index()->comment('Публичный UUID канала');
            $table->string('tenant_id', 128)->index()->comment('Тенант-владелец');
            $table->string('business_group_id', 128)->nullable()->index()->comment('Группа бизнеса (филиал)');
            $table->string('name', 256)->comment('Название канала');
            $table->string('slug', 128)->unique()->comment('URL-слаг канала');
            $table->text('description')->nullable()->comment('Описание канала');
            $table->string('avatar_url', 512)->nullable()->comment('Аватар канала');
            $table->string('cover_url', 512)->nullable()->comment('Обложка канала');
            $table->enum('status', ['active', 'archived', 'suspended'])->default('active')->comment('Статус канала');
            $table->timestamp('archived_at')->nullable()->comment('Дата архивации');
            $table->timestamp('last_post_at')->nullable()->comment('Дата последнего поста');
            $table->unsignedBigInteger('plan_id')->nullable()->index()->comment('FK → channel_subscription_plans');
            $table->timestamp('plan_expires_at')->nullable()->comment('Дата окончания подписки на тариф');
            $table->unsignedBigInteger('subscribers_count')->default(0)->comment('Кэш — количество подписчиков');
            $table->unsignedBigInteger('posts_count')->default(0)->comment('Кэш — общее количество постов');
            $table->json('tags')->nullable()->comment('Теги для аналитики');
            $table->string('correlation_id', 64)->nullable()->index()->comment('Идентификатор создания');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'status'], 'bc_tenant_status_idx');
            $table->foreign('plan_id')->references('id')->on('channel_subscription_plans')->nullOnDelete();
        });

        // ─────────────────────────────────────────────
        // 3. Посты
        // ─────────────────────────────────────────────
        Schema::create('posts', function (Blueprint $table): void {
            $table->comment('Посты бизнес-каналов. Проходят модерацию перед публикацией.');
            $table->id();
            $table->uuid('uuid')->unique()->index()->comment('Публичный UUID поста');
            $table->string('correlation_id', 64)->nullable()->index()->comment('Идентификатор создания');
            $table->unsignedBigInteger('channel_id')->index()->comment('FK → business_channels');
            $table->string('tenant_id', 128)->index()->comment('Денормализовано для scoping');
            $table->string('title', 512)->nullable()->comment('Заголовок (опционально)');
            $table->longText('content')->comment('Текст поста (rich text / markdown)');
            $table->string('slug', 256)->nullable()->index()->comment('SEO-слаг поста');
            $table->enum('status', ['draft', 'pending_moderation', 'published', 'archived', 'rejected'])
                ->default('draft')
                ->comment('Статус поста');
            $table->enum('visibility', ['b2c', 'b2b', 'all'])->default('all')->comment('Видимость поста');
            $table->timestamp('published_at')->nullable()->index()->comment('Дата публикации');
            $table->timestamp('scheduled_at')->nullable()->comment('Отложенная публикация');
            $table->json('reactions')->nullable()->comment('JSON: {"like":12,"heart":5,"fire":3,...}');
            $table->json('poll')->nullable()->comment('JSON опроса (только extended тариф)');
            $table->unsignedBigInteger('views_count')->default(0)->comment('Просмотры');
            $table->unsignedBigInteger('reactions_count')->default(0)->comment('Общее кол-во реакций');
            $table->boolean('is_promo')->default(false)->comment('Промо-пост');
            $table->boolean('is_moderated')->default(false)->comment('Прошёл модерацию');
            $table->string('moderated_by', 128)->nullable()->comment('Кто промодерировал');
            $table->timestamp('moderated_at')->nullable()->comment('Когда промодерировано');
            $table->text('moderation_comment')->nullable()->comment('Комментарий модератора');
            $table->json('tags')->nullable()->comment('Теги');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['channel_id', 'status', 'published_at'], 'posts_channel_status_idx');
            $table->index(['tenant_id', 'visibility', 'status'], 'posts_tenant_vis_idx');
            $table->foreign('channel_id')->references('id')->on('business_channels')->cascadeOnDelete();
        });

        // ─────────────────────────────────────────────
        // 4. Медиафайлы поста
        // ─────────────────────────────────────────────
        Schema::create('post_media', function (Blueprint $table): void {
            $table->comment('Медиафайлы прикреплённые к постам (фото, видео, shorts).');
            $table->id();
            $table->unsignedBigInteger('post_id')->index()->comment('FK → posts');
            $table->string('tenant_id', 128)->index()->comment('Денормализовано для scoping');
            $table->enum('type', ['image', 'video', 'shorts'])->comment('Тип медиафайла');
            $table->string('url', 1024)->comment('URL файла (S3/CDN)');
            $table->string('thumbnail_url', 1024)->nullable()->comment('Превью');
            $table->string('mime_type', 64)->nullable()->comment('MIME тип');
            $table->unsignedBigInteger('size_bytes')->nullable()->comment('Размер файла');
            $table->unsignedSmallInteger('width')->nullable()->comment('Ширина (px)');
            $table->unsignedSmallInteger('height')->nullable()->comment('Высота (px)');
            $table->unsignedSmallInteger('duration_seconds')->nullable()->comment('Длительность видео (сек)');
            $table->string('alt_text', 512)->nullable()->comment('ALT текст для SEO/a11y');
            $table->unsignedTinyInteger('sort_order')->default(0)->comment('Порядок отображения');
            $table->string('correlation_id', 64)->nullable()->index()->comment('Идентификатор операции');
            $table->timestamps();

            $table->foreign('post_id')->references('id')->on('posts')->cascadeOnDelete();
        });

        // ─────────────────────────────────────────────
        // 5. Подписчики каналов
        // ─────────────────────────────────────────────
        Schema::create('channel_subscribers', function (Blueprint $table): void {
            $table->comment('Подписчики каналов. Число подписчиков видит только владелец бизнеса.');
            $table->id();
            $table->unsignedBigInteger('channel_id')->index()->comment('FK → business_channels');
            $table->unsignedBigInteger('user_id')->index()->comment('FK → users');
            $table->enum('visibility_preference', ['b2c', 'b2b', 'all'])->default('all')
                ->comment('Фильтр видимости постов для данного подписчика');
            $table->string('correlation_id', 64)->nullable()->index()->comment('Идентификатор операции');
            $table->timestamp('subscribed_at')->useCurrent()->comment('Дата подписки');
            $table->timestamp('unsubscribed_at')->nullable()->comment('Дата отписки (soft)');
            $table->timestamps();

            $table->unique(['channel_id', 'user_id'], 'cs_channel_user_unique');
            $table->index(['channel_id', 'unsubscribed_at'], 'cs_active_idx');
            $table->foreign('channel_id')->references('id')->on('business_channels')->cascadeOnDelete();
        });

        // ─────────────────────────────────────────────
        // 6. Подписки бизнеса на тариф (usage tracking)
        // ─────────────────────────────────────────────
        Schema::create('channel_subscription_usages', function (Blueprint $table): void {
            $table->comment('Активные подписки бизнеса на тарифный план канала. Оплачивается через Wallet.');
            $table->id();
            $table->string('tenant_id', 128)->index()->comment('Тенант-владелец');
            $table->unsignedBigInteger('channel_id')->index()->comment('FK → business_channels');
            $table->unsignedBigInteger('plan_id')->index()->comment('FK → channel_subscription_plans');
            $table->enum('status', ['active', 'expired', 'cancelled'])->default('active')->comment('Статус подписки');
            $table->timestamp('starts_at')->comment('Начало подписки');
            $table->timestamp('expires_at')->index()->comment('Конец подписки');
            $table->timestamp('cancelled_at')->nullable()->comment('Дата отмены');
            $table->integer('amount_paid_kopecks')->comment('Сумма оплаты в копейках');
            $table->unsignedBigInteger('balance_transaction_id')->nullable()->comment('FK → balance_transactions');
            $table->string('correlation_id', 64)->nullable()->index()->comment('Идентификатор операции');
            $table->json('tags')->nullable()->comment('Теги для аналитики');
            $table->timestamps();

            $table->index(['tenant_id', 'status', 'expires_at'], 'csu_tenant_active_idx');
            $table->foreign('channel_id')->references('id')->on('business_channels')->cascadeOnDelete();
            $table->foreign('plan_id')->references('id')->on('channel_subscription_plans');
        });

        // ─────────────────────────────────────────────
        // 7. Лог реакций (фрод-детекция накруток)
        // ─────────────────────────────────────────────
        Schema::create('post_reaction_logs', function (Blueprint $table): void {
            $table->comment('Лог реакций на посты для фрод-детекции (накрутка реакций).');
            $table->id();
            $table->unsignedBigInteger('post_id')->index()->comment('FK → posts');
            $table->string('tenant_id', 128)->index()->comment('Тенант канала');
            $table->unsignedBigInteger('user_id')->nullable()->comment('Пользователь (NULL = аноним)');
            $table->string('session_hash', 64)->nullable()->comment('Хэш сессии анонима');
            $table->string('ip_address', 64)->nullable()->comment('IP адрес');
            $table->string('emoji', 16)->comment('Выбранный emoji');
            $table->string('action', 16)->default('add')->comment('add | remove');
            $table->float('fraud_score')->nullable()->comment('ML-скор фрода');
            $table->string('correlation_id', 64)->nullable()->index()->comment('Идентификатор операции');
            $table->timestamp('reacted_at')->useCurrent()->comment('Время реакции');

            $table->index(['post_id', 'user_id', 'emoji'], 'prl_post_user_emoji_idx');
            $table->index(['post_id', 'session_hash', 'emoji'], 'prl_post_session_idx');
            $table->foreign('post_id')->references('id')->on('posts')->cascadeOnDelete();
        });

        // ─────────────────────────────────────────────
        // 8. Статистика постов (daily)
        // ─────────────────────────────────────────────
        Schema::create('post_stats_daily', function (Blueprint $table): void {
            $table->comment('Ежедневная статистика постов (просмотры, CTR, реакции, гео).');
            $table->id();
            $table->unsignedBigInteger('post_id')->index()->comment('FK → posts');
            $table->string('tenant_id', 128)->index()->comment('Тенант');
            $table->date('stat_date')->comment('Дата статистики');
            $table->unsignedBigInteger('views')->default(0)->comment('Просмотры за день');
            $table->unsignedBigInteger('unique_views')->default(0)->comment('Уникальные просмотры');
            $table->unsignedBigInteger('reactions_total')->default(0)->comment('Реакции за день');
            $table->unsignedBigInteger('link_clicks')->default(0)->comment('Клики по ссылкам (CTR)');
            $table->json('reactions_breakdown')->nullable()->comment('JSON разбивка по emoji');
            $table->json('geo_breakdown')->nullable()->comment('JSON гео-разбивка (extended тариф)');
            $table->json('device_breakdown')->nullable()->comment('JSON разбивка по устройствам');
            $table->timestamps();

            $table->unique(['post_id', 'stat_date'], 'psd_post_date_unique');
            $table->foreign('post_id')->references('id')->on('posts')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_stats_daily');
        Schema::dropIfExists('post_reaction_logs');
        Schema::dropIfExists('channel_subscription_usages');
        Schema::dropIfExists('channel_subscribers');
        Schema::dropIfExists('post_media');
        Schema::dropIfExists('posts');
        Schema::dropIfExists('business_channels');
        Schema::dropIfExists('channel_subscription_plans');
    }
};
