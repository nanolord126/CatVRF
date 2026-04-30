<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create PII Deletion Requests Table
 * 
 * Таблица для отслеживания запросов на удаление персональных данных
 * в соответствии с 152-ФЗ (право на забвение)
 * 
 * @author CatVRF Team
 * @version 2026.04.28
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pii_deletion_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('requested_by')->constrained('users')->onDelete('restrict');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('restrict');
            $table->string('reason')->comment('Причина запроса на удаление');
            $table->enum('status', ['pending', 'processing', 'completed', 'rejected'])->default('pending')->index();
            $table->json('deletion_report')->nullable()->comment('Отчет об удалении');
            $table->text('rejection_reason')->nullable();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->softDeletes();

            // Indexes
            $table->index(['user_id', 'status']);
            $table->index(['status', 'created_at']);
            $table->index('created_at');
        });

        // Add comment
        DB::statement("ALTER TABLE pii_deletion_requests COMMENT = 'PII deletion requests per 152-FZ Article 10 (right to be forgotten)'");
    }

    public function down(): void
    {
        Schema::dropIfExists('pii_deletion_requests');
    }
};
