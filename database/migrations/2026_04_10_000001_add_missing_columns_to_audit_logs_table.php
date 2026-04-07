<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Дополняет таблицу audit_logs колонками, требуемыми AuditService / AuditLogJob.
 *
 * Исходная миграция (2026_03_25_000004) создала таблицу с минимальным набором
 * колонок (model_type / model_id / changes / user_agent).
 * AuditService отправляет в job payload с расширенным набором полей
 * (subject_type, subject_id, old_values, new_values, device_fingerprint,
 * business_group_id) — без них insert в БД падает с «column not found».
 *
 * Обе группы колонок сохраняются: новые добавляются рядом со старыми.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table): void {
            // Альтернативные имена для model_type / model_id (используются в AuditService)
            if (!Schema::hasColumn('audit_logs', 'subject_type')) {
                $table->string('subject_type', 255)->nullable()->after('action');
            }
            if (!Schema::hasColumn('audit_logs', 'subject_id')) {
                $table->unsignedBigInteger('subject_id')->nullable()->after('subject_type');
            }

            // Детальная история изменений (раздельно, удобнее для KeyValue UI)
            if (!Schema::hasColumn('audit_logs', 'old_values')) {
                $table->json('old_values')->nullable()->after('subject_id');
            }
            if (!Schema::hasColumn('audit_logs', 'new_values')) {
                $table->json('new_values')->nullable()->after('old_values');
            }

            // Отпечаток устройства (SHA-256 IP + User-Agent)
            if (!Schema::hasColumn('audit_logs', 'device_fingerprint')) {
                $table->string('device_fingerprint', 64)->nullable()->after('ip_address');
            }

            // Привязка к бизнес-группе / филиалу (без FK — таблица может ещё не существовать)
            if (!Schema::hasColumn('audit_logs', 'business_group_id')) {
                $table->unsignedBigInteger('business_group_id')->nullable()->after('tenant_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table): void {
            $columns = ['subject_type', 'subject_id', 'old_values', 'new_values', 'device_fingerprint', 'business_group_id'];

            foreach ($columns as $column) {
                if (Schema::hasColumn('audit_logs', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
