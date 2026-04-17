<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('commission_rules')) {
            return;
        }

        Schema::create('commission_rules', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique()->index();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('business_group_id')->nullable()->index()->comment('ID бизнес-группы (филиала)');
            
            $table->string('name')->comment('Название правила комиссии');
            $table->string('type')->default('percentage')->comment('percentage, fixed, tiered');
            $table->string('entity_type')->comment('order, delivery, payout, etc.');
            
            // B2C/B2B differentiation
            $table->decimal('b2c_rate', 5, 2)->default(14.00)->comment('B2C комиссия в % (default 14%)');
            $table->decimal('b2b_rate', 5, 2)->default(10.00)->comment('B2B базовая комиссия в %');
            
            // Tier-based B2B rates
            $table->json('b2b_tiers')->nullable()->comment('B2B tier rates: [{"min_volume": 0, "rate": 12}, {"min_volume": 1000000, "rate": 10}, ...]');
            
            $table->decimal('fixed_amount', 14, 2)->nullable()->comment('Фиксированная сумма (для type=fixed)');
            $table->decimal('min_amount', 14, 2)->nullable()->comment('Минимальная сумма комиссии');
            $table->decimal('max_amount', 14, 2)->nullable()->comment('Максимальная сумма комиссии');
            
            $table->boolean('is_active')->default(true)->index();
            $table->timestamp('valid_from')->nullable()->comment('Действует с');
            $table->timestamp('valid_until')->nullable()->comment('Действует до');
            
            $table->string('correlation_id', 36)->nullable()->index();
            $table->json('tags')->nullable()->comment('Теги для аналитики и фильтрации');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'entity_type', 'is_active']);
            $table->comment('Правила комиссий: B2C (14%) и B2B tier-based (8-12%)');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commission_rules');
    }
};
