<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('restaurant_loyalty_programs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('business_group_id')->nullable()->index();
            $table->unsignedBigInteger('restaurant_id')->index();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            
            // Настройки программы
            $table->boolean('is_active')->default(true)->index();
            $table->decimal('bonus_percentage', 5, 4)->default(0.0100)->comment('% бонуса от суммы заказа (например, 1.00 = 1%)');
            $table->decimal('signup_bonus_amount', 10, 2)->default(0)->comment('Бонус в рублях при регистрации');
            $table->decimal('birthday_bonus_amount', 10, 2)->default(0)->comment('Бонус в рублях на день рождения');
            
            // Уровни лояльности
            $table->json('tiers')->nullable()->comment('Настройки уровней (bronze, silver, gold, platinum)');
            
            // Промо-акции
            $table->boolean('happy_hours_enabled')->default(false);
            $table->json('happy_hours_config')->nullable();
            $table->boolean('double_bonus_enabled')->default(false)->comment('Удвоение бонуса');
            $table->json('double_bonus_config')->nullable();
            
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            
            $table->json('metadata')->nullable();
            $table->string('correlation_id')->nullable()->index();
            
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('restaurant_id')->references('id')->on('restaurants')->onDelete('cascade');

            $table->index(['tenant_id', 'restaurant_id']);
            $table->index(['restaurant_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restaurant_loyalty_programs');
    }
};
