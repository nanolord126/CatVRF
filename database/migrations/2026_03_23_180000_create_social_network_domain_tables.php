<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Миграция для Раздела 6: Социальная сеть, Рекомендации и Shorts (КАНОН 2026)
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Посты и Shorts
        if (!Schema::hasTable('social_posts')) {
            Schema::create('social_posts', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('user_id')->index();
                $table->text('content')->nullable();
                $table->string('type')->index();             // text, image, video, shorts
                $table->string('media_url')->nullable();      // S3 path
                $table->string('thumbnail_url')->nullable();
                $table->string('transcoding_status')->nullable()->index(); // pending, processing, completed, failed
                $table->bigInteger('view_count')->default(0);
                $table->bigInteger('like_count')->default(0);
                $table->string('correlation_id')->nullable()->index();
                $table->jsonb('tags')->nullable();
                $table->timestamps();
                $table->softDeletes();
                $table->comment('Посты и Shorts социальной сети');
            });
        }

        // 2. Комментарии
        if (!Schema::hasTable('social_comments')) {
            Schema::create('social_comments', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('post_id')->index();
                $table->unsignedBigInteger('user_id')->index();
                $table->text('content');
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();
                $table->softDeletes();
                $table->comment('Комментарии к постам');
            });
        }

        // 3. Лайки (для AI-обучения и логов)
        if (!Schema::hasTable('social_likes')) {
            Schema::create('social_likes', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('post_id')->index();
                $table->unsignedBigInteger('user_id')->index();
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();
                $table->unique(['post_id', 'user_id']);
                $table->comment('Лайки пользователей');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('social_likes');
        Schema::dropIfExists('social_comments');
        Schema::dropIfExists('social_posts');
    }
};
