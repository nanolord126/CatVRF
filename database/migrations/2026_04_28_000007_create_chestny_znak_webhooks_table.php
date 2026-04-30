<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create Chestny ZNAK Webhooks Table
 * 
 * Таблица для логирования webhook уведомлений от Честный ЗНАК
 * 
 * @author CatVRF Team
 * @version 2026.04.28
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chestny_znak_webhooks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('document_id')->index()->comment('ID документа');
            $table->enum('event_type', [
                'document_processed',
                'document_rejected',
                'document_signed',
                'document_error',
            ])->index()->comment('Тип события');
            $table->json('payload')->comment('Полезная нагрузка webhook');
            $table->timestamp('processed_at')->comment('Дата обработки');
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->onDelete('set null');
            $table->timestamp('created_at')->useCurrent();
            $table->softDeletes();

            // Indexes
            $table->index(['document_id', 'event_type']);
            $table->index(['event_type', 'created_at']);
            $table->index('created_at');
        });

        DB::statement("ALTER TABLE chestny_znak_webhooks COMMENT = 'Chestny ZNAK webhook notifications'");
    }

    public function down(): void
    {
        Schema::dropIfExists('chestny_znak_webhooks');
    }
};
