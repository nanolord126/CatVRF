<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('real_estate_viewings')) {
            return;
        }

        Schema::create('real_estate_viewings', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->uuid('property_id')->index();
            $table->unsignedBigInteger('client_id')->nullable()->index()->comment('ID пользователя-клиента (nullable для гостей)');
            $table->uuid('agent_id')->nullable()->index();
            $table->dateTime('scheduled_at')->index()->comment('Запланированное время просмотра');
            $table->string('status', 30)->default('pending')->index()->comment('pending|confirmed|completed|cancelled');
            $table->string('client_name', 255);
            $table->string('client_phone', 20);
            $table->text('notes')->nullable()->comment('Заметки клиента или агента');
            $table->text('cancellation_reason')->nullable()->comment('Причина отмены');
            $table->string('correlation_id', 36)->nullable()->index();
            $table->timestamps();

            $table->foreign('property_id')
                ->references('id')
                ->on('real_estate_properties')
                ->cascadeOnDelete();

            $table->foreign('agent_id')
                ->references('id')
                ->on('real_estate_agents')
                ->nullOnDelete();

            $table->index(['property_id', 'scheduled_at', 'status'], 'real_estate_viewings_conflict_idx');
            $table->index(['tenant_id', 'status'], 'real_estate_viewings_tenant_status_idx');
        });

        \Illuminate\Support\Facades\DB::statement(
            "ALTER TABLE real_estate_viewings COMMENT = 'Записи на просмотр объектов недвижимости'"
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('real_estate_viewings');
    }
};
