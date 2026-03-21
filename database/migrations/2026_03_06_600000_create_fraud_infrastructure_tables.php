<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Таблица для хранения логов подозрительных действий В ОБЩЕЙ СХЕМЕ (Central)
        Schema::create('fraud_events', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->string('tenant_id');
            $blueprint->unsignedBigInteger('user_id')->nullable();
            $blueprint->string('event_type'); // 'velocity', 'geoshift', 'large_payout', 'pattern_anomaly'
            $blueprint->json('payload'); // Контекст: IP, User-Agent, Device fingerprint, сумма
            $blueprint->decimal('risk_score', 5, 2)->default(0); // 0.00 to 100.00
            $blueprint->string('correlation_id')->index();
            $blueprint->boolean('is_blocked')->default(false);
            $blueprint->timestamp('created_at')->useCurrent();

            $blueprint->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
        });

        // Правила безопасности
        Schema::create('fraud_rules', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->string('rule_name');
            $blueprint->string('rule_slug')->unique();
            $blueprint->integer('weight'); // Вес риска (напр. 50 за смену страны)
            $blueprint->boolean('is_active')->default(true);
            $blueprint->json('config')->nullable(); // Пороги срабатывания
            $blueprint->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fraud_events');
        Schema::dropIfExists('fraud_rules');
    }
};
