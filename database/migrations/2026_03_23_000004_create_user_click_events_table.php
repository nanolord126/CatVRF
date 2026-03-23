<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

final class CreateUserClickEventsTable extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('user_click_events')) {
            return;
        }

        Schema::create('user_click_events', static function (Blueprint $table): void {
            $table->id()->comment('Первичный ключ');
            $table->uuid()->unique()->indexed()->comment('UUID события');
            $table->unsignedBigInteger('tenant_id')->nullable()->indexed()->comment('ID тенанта');
            $table->unsignedBigInteger('user_id')->nullable()->indexed()->comment('ID пользователя (анонимизирован)');
            $table->string('page_url', 500)->comment('URL страницы');
            $table->string('page_title', 255)->nullable()->comment('Заголовок страницы');
            $table->integer('click_x')->comment('Координата X клика (нормализуется блоками 50px)');
            $table->integer('click_y')->comment('Координата Y клика');
            $table->integer('screen_width')->comment('Ширина экрана');
            $table->integer('screen_height')->comment('Высота экрана');
            $table->string('element_selector', 500)->nullable()->comment('CSS селектор элемента');
            $table->string('browser', 50)->nullable()->comment('Браузер (Chrome, Firefox и т.д.)');
            $table->string('device_type', 20)->nullable()->comment('Тип устройства (desktop, mobile, tablet)');
            $table->string('correlation_id', 36)->nullable()->indexed()->comment('ID для трейсинга');
            $table->timestamp('recorded_at')->useCurrent()->comment('Время события');

            $table->index(['tenant_id', 'page_url', 'recorded_at'], 'idx_click_events_composite');
            $table->index(['device_type', 'recorded_at'], 'idx_click_events_device');

            $table->comment('Таблица кликов пользователей для клик-тепловых карт');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_click_events');
    }
}
