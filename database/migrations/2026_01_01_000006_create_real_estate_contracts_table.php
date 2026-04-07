<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('real_estate_contracts')) {
            return;
        }

        Schema::create('real_estate_contracts', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->uuid('property_id')->index();
            $table->uuid('agent_id')->nullable()->index();
            $table->unsignedBigInteger('client_id')->nullable()->index()->comment('ID пользователя-клиента');
            $table->string('client_name', 255)->nullable()->comment('Имя клиента (если не зарегистрирован)');
            $table->string('client_phone', 20)->nullable();
            $table->string('type', 30)->index()->comment('rental|sale');
            $table->unsignedBigInteger('price_kopecks')->default(0)->comment('Сумма сделки в копейках');
            $table->unsignedBigInteger('commission_kopecks')->default(0)->comment('Комиссия платформы в копейках');
            $table->string('status', 30)->default('pending')->index()->comment('pending|signed|terminated');
            $table->unsignedSmallInteger('lease_duration_months')->nullable()->comment('Срок аренды в месяцах (только для rental)');
            $table->string('document_url', 2048)->nullable()->comment('Ссылка на подписанный документ');
            $table->timestamp('signed_at')->nullable()->comment('Дата и время подписания');
            $table->timestamp('terminated_at')->nullable()->comment('Дата и время расторжения');
            $table->string('correlation_id', 36)->nullable()->index();
            $table->json('tags')->nullable()->comment('Дополнительные метаданные');
            $table->timestamps();

            $table->foreign('property_id')
                ->references('id')
                ->on('real_estate_properties')
                ->cascadeOnDelete();

            $table->foreign('agent_id')
                ->references('id')
                ->on('real_estate_agents')
                ->nullOnDelete();

            $table->index(['tenant_id', 'status', 'signed_at'], 'real_estate_contracts_tenant_status_idx');
            $table->index(['property_id', 'status'], 'real_estate_contracts_property_status_idx');
        });

        \Illuminate\Support\Facades\DB::statement(
            "ALTER TABLE real_estate_contracts COMMENT = 'Контракты аренды и продажи недвижимости'"
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('real_estate_contracts');
    }
};
