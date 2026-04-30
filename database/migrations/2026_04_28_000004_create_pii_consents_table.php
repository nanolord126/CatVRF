<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create PII Consents Table
 * 
 * Таблица для хранения согласий на обработку персональных данных
 * в соответствии с 152-ФЗ (статья 9 - согласие на обработку персональных данных)
 * 
 * @author CatVRF Team
 * @version 2026.04.28
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pii_consents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->enum('consent_type', [
                'personal_data_processing',
                'data_sharing',
                'marketing_communications',
                'analytics',
                'biometric_data',
            ])->index()->comment('Тип согласия');
            $table->text('consent_text')->comment('Текст согласия');
            $table->timestamp('granted_at')->useCurrent()->comment('Дата предоставления согласия');
            $table->timestamp('expires_at')->nullable()->index()->comment('Дата истечения согласия');
            $table->timestamp('revoked_at')->nullable()->index()->comment('Дата отзыва согласия');
            $table->foreignId('revoked_by')->nullable()->constrained('users')->onDelete('restrict')->comment('Кто отозвал согласие');
            $table->text('revocation_reason')->nullable()->comment('Причина отзыва');
            $table->string('ip_address', 45)->comment('IP адрес при предоставлении');
            $table->string('user_agent')->nullable()->comment('User Agent');
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();
            $table->softDeletes();

            // Indexes
            $table->index(['user_id', 'consent_type']);
            $table->index(['user_id', 'granted_at']);
            $table->index(['consent_type', 'granted_at']);
            $table->index('created_at');
        });

        // Add comment
        DB::statement("ALTER TABLE pii_consents COMMENT = 'PII consents per 152-FZ Article 9 (consent to processing)'");
    }

    public function down(): void
    {
        Schema::dropIfExists('pii_consents');
    }
};
