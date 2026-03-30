<?php declare(strict_types=1);

namespace App\Domains\Medical\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class MedicalRecord extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use SoftDeletes, LogsActivity;

        protected $table = 'medical_records';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'patient_id',
            'doctor_id',
            'appointment_id',
            'diagnosis_code',
            'complaints',
            'treatment_plan',
            'clinical_data',
            'correlation_id',
        ];

        protected $casts = [
            'clinical_data' => 'array',
        ];

        /**
         * КАНОН: Global Scopes и События модели.
         */
        protected static function booted(): void
        {
            static::addGlobalScope('tenant', function ($query) {
                $query->where('tenant_id', tenant()->id ?? 0);
            });
        }

        /**
         * КРИТИЧНО: Настройка логов для ФЗ-152.
         * Логируем каждое изменение и каждое обращение к конфиденциальным данным.
         */
        public function getActivitylogOptions(): LogOptions
        {
            return LogOptions::defaults()
                ->logOnly(['record_type', 'is_confidential'])
                ->logOnlyDirty()
                ->useLogName('medical_record_confidential_audit');
        }

        /**
         * Отношение: Клиент (User).
         */
        public function client(): BelongsTo
        {
            return $this->belongsTo(User::class, 'client_id');
        }

        /**
         * Отношение: Врач, создавший запись.
         */
        public function doctor(): BelongsTo
        {
            return $this->belongsTo(Doctor::class, 'doctor_id');
        }

        /**
         * Отношение: Связанный прием.
         */
        public function appointment(): BelongsTo
        {
            return $this->belongsTo(Appointment::class, 'appointment_id');
        }

        /**
         * Регистрация доступа (ФЗ-152 Audit Trail).
         */
        public function logAccess(int $userId, string $reason = 'view'): void
        {
            $currentLog = $this->access_log_json ?? [];
            $currentLog[] = [
                'user_id' => $userId,
                'accessed_at' => now()->toIso8601String(),
                'reason' => $reason,
                'correlation_id' => $this->correlation_id ?? (string)Str::uuid()
            ];

            // Используем update без событий, чтобы не вызывать бесконечный цикл логов
            $this->updateQuietly(['access_log_json' => $currentLog]);
        }

        /**
         * Форматированный вывод рецепта.
         */
        public function getPrescriptionList(): array
        {
            return $this->prescription_json['medicines'] ?? [];
        }

        /**
         * Проверка конфиденциальности.
         */
        public function canBeSharedExternally(): bool
        {
            return !$this->is_confidential;
        }
}
