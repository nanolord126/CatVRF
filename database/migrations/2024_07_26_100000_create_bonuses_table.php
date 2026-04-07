<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('bonuses')) {
            return;
        }

        Schema::create('bonuses', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->nullable()->comment('Уникальный идентификатор бонуса');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade')->comment('ID пользователя');
            $table->unsignedBigInteger('tenant_id')->index()->comment('ID тенанта');
            $table->unsignedBigInteger('business_group_id')->nullable()->index()->comment('ID бизнес-группы');
            
            $table->integer('amount')->comment('Сумма бонуса в копейках');
            $table->string('type')->index()->comment('Тип бонуса: referral, turnover, promo, loyalty, manual');
            $table->string('reason')->nullable()->comment('Причина начисления');
            
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('source_type')->nullable();
            
            $table->string('correlation_id')->nullable()->index()->comment('ID для сквозной трассировки');
            $table->jsonb('tags')->nullable()->comment('Теги для аналитики и фильтрации');
            
            $table->timestamp('expires_at')->nullable()->comment('Дата сгорания бонуса');
            $table->timestamps();

            $table->comment('Таблица для хранения начисленных бонусов');
            $table->index(['source_id', 'source_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bonuses');
    }
};
