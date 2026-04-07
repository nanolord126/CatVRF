<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Запустить миграцию (создать таблицу payout_requests)
     * CANON 2026 - Production Ready
     */
    public function up(): void
    {
        if (Schema::hasTable('payout_requests')) {
            return;
        }

        Schema::create('payout_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('business_group_id')->nullable()->constrained('business_groups')->onDelete('set null');
            
            // Amount in cents
            $table->bigInteger('amount')->unsigned()
                ->comment('Сумма выплаты в копейках');
            
            // Status tracking
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'cancelled'])
                ->default('pending')
                ->index()
                ->comment('Статус обработки: pending, processing, completed, failed, cancelled');
            
            // Bank details (JSON for security - не храним открытым текстом в обычном поле)
            $table->json('bank_details')->nullable()
                ->comment('Реквизиты для выплаты: account_number, bic, inn, name');
            
            // Tracing & audit
            $table->string('correlation_id')->nullable()->index()
                ->comment('ID корреляции для трейсирования');
            
            $table->json('tags')->nullable()
                ->comment('Теги для фильтрации и анализа');
            
            // Cancellation
            $table->string('cancellation_reason')->nullable()
                ->comment('Причина отмены (если отменена)');
            
            $table->json('metadata')->nullable()
                ->comment('Дополнительные данные и контекст');
            
            // Timestamps
            $table->timestamps();
            
            // Indices
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'created_at']);
            $table->index(['tenant_id', 'business_group_id']);
            $table->unique('correlation_id');
            
            // Table comment
            $table->comment('Заявки на выплату бизнесу, фрилансерам, партнёрам');
        });
    }

    /**
     * Откатить миграцию (удалить таблицу)
     */
    public function down(): void
    {
        Schema::dropIfExists('payout_requests');
    }
};


