<?php

declare(strict_types=1);

namespace App\Domains\Medical\Policies;

use App\Models\User;
use App\Domains\Medical\Models\MedicalRecord;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Facades\Log;

/**
 * РЕЖИМ ЛЮТЫЙ 2026: MEDICAL RECORD POLICY (ФЗ-152)
 * 
 * Регулирует доступ к чувствительным медицинским данным.
 * Обязательное условие: любое обращение (просмотр/правка) логируется в `access_log_json`.
 * Доступ имеют только:
 * 1. Лечащий врач (автор записи или назначенный на прием)
 * 2. Сам пациент (владелец данных)
 * 3. Главный врач клиники (admin|manager роли в текущем tenant)
 * 
 * @package App\Domains\Medical\Policies
 */
final class MedicalRecordPolicy
{
    use HandlesAuthorization;

    /**
     * Право на просмотр списка медицинских карт.
     * Ограничено по tenant_id и ролями клиники.
     * 
     * @param User $user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['doctor', 'admin', 'manager', 'medical_manager']);
    }

    /**
     * Право на детальный просмотр карты пациента.
     * Критично: ФЗ-152 требует обоснования просмотра.
     * 
     * @param User $user
     * @param MedicalRecord $record
     * @return bool
     */
    public function view(User $user, MedicalRecord $record): bool
    {
        // 1. Пациент видит свою карту
        if ($user->id === $record->patient_id) {
            $this->logAccess($user, $record, 'view_by_owner');
            return true;
        }

        // 2. Врач-автор видит запись
        if ($user->id === $record->doctor_id) {
            $this->logAccess($user, $record, 'view_by_author');
            return true;
        }

        // 3. Администрация клиники (tenant scoping)
        if ($user->hasRole(['admin', 'manager']) && $user->tenant_id === $record->tenant_id) {
            $this->logAccess($user, $record, 'view_by_admin_audit');
            return true;
        }

        return false;
    }

    /**
     * Право на создание новой записи (только врач или интеграция).
     * 
     * @param User $user
     * @return bool
     */
    public function create(User $user): bool
    {
        return $user->hasRole(['doctor', 'admin']);
    }

    /**
     * Право на обновление записи.
     * Только врач-автор в течение 24 часов (согласно внутренним регламентам 2026).
     * 
     * @param User $user
     * @param MedicalRecord $record
     * @return bool
     */
    public function update(User $user, MedicalRecord $record): bool
    {
        // Запрет редактирования чужих записей
        if ($user->id !== $record->doctor_id && !$user->hasRole('admin')) {
            return false;
        }

        // Ограничение по времени (правка диагноза — ответственное действие)
        if ($record->created_at->diffInHours(now()) > 24 && !$user->hasRole('admin')) {
            Log::channel('fraud_alert')->warning('Attempt to edit medical record after 24h', [
                'user_id' => $user->id,
                'record_id' => $record->id,
                'correlation_id' => request()->header('X-Correlation-ID'),
            ]);
            return false;
        }

        $this->logAccess($user, $record, 'update_initiated');
        return true;
    }

    /**
     * Удаление медицинских записей запрещено (только Soft Delete с аудитом).
     * 
     * @param User $user
     * @param MedicalRecord $record
     * @return bool
     */
    public function delete(User $user, MedicalRecord $record): bool
    {
        // Только супер-админ может инициировать процедуру архивации
        return $user->hasRole('super_admin');
    }

    /**
     * Внутренний метод логирования доступа для ФЗ-152.
     * 
     * @param User $user
     * @param MedicalRecord $record
     * @param string $action
     */
    private function logAccess(User $user, MedicalRecord $record, string $action): void
    {
        $correlationId = request()->header('X-Correlation-ID') ?? \Illuminate\Support\Str::uuid()->toString();

        // 1. Запись в БД (в массив доступа)
        $logs = $record->access_log_json ?? [];
        $logs[] = [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'action' => $action,
            'ip' => request()->ip(),
            'timestamp' => now()->toIso8601String(),
            'correlation_id' => $correlationId,
        ];

        // Используем DB direct для скорости и обхода лишних ивентов
        \Illuminate\Support\Facades\DB::table('medical_records')
            ->where('id', $record->id)
            ->update(['access_log_json' => json_encode($logs)]);

        // 2. Запись в Audit Log (физический файл/ClickHouse)
        Log::channel('audit')->info("Medical Record Access Layer", [
            'action' => $action,
            'user_id' => $user->id,
            'record_uuid' => $record->uuid,
            'patient_id' => $record->patient_id,
            'correlation_id' => $correlationId,
        ]);
    }
}
