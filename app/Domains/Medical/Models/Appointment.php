<?php declare(strict_types=1);

namespace App\Domains\Medical\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class Appointment extends Model
{
    use HasFactory;

    use SoftDeletes, LogsActivity;

        protected $table = 'medical_appointments';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'clinic_id',
            'doctor_id',
            'service_id',
            'client_id',
            'appointment_at',
            'status', // pending, confirmed, in_progress, completed, cancelled
            'total_price',
            'prepayment_amount',
            'payment_status', // unpaid, partial, paid, refunded
            'client_notes',
            'internal_notes',
            'metadata',
            'tags',
            'correlation_id'
        ];

        protected $casts = [
            'appointment_at' => 'datetime',
            'total_price' => 'integer',
            'prepayment_amount' => 'integer',
            'metadata' => 'array',
            'tags' => 'array',
        ];

        /**
         * КАНОН: Global Scopes и События модели.
         */
        protected static function booted_disabled(): void
        {
            static::creating(function (Appointment $appointment) {
                $appointment->uuid = $appointment->uuid ?? (string)Str::uuid();
                $appointment->tenant_id = $appointment->tenant_id ?? (int)tenant()->id;
                $appointment->correlation_id = $appointment->correlation_id ?? (string)Str::uuid();
            });

            static::addGlobalScope('tenant_id', function (Builder $builder) {
                if (tenant()) {
                    $builder->where('tenant_id', tenant()->id);
                }
            });
        }

        /**
         * Настройка логов для ФЗ-152.
         */
        public function getActivitylogOptions(): LogOptions
        {
            return LogOptions::defaults()
                ->logOnly(['status', 'payment_status', 'appointment_at', 'total_price'])
                ->logOnlyDirty()
                ->useLogName('medical_appointment_audit');
        }

        /**
         * Отношение: Клиент (User).
         */
        public function client(): BelongsTo
        {
            return $this->belongsTo(User::class, 'client_id');
        }

        /**
         * Отношение: Клиника.
         */
        public function clinic(): BelongsTo
        {
            return $this->belongsTo(Clinic::class, 'clinic_id');
        }

        /**
         * Отношение: Врач.
         */
        public function doctor(): BelongsTo
        {
            return $this->belongsTo(Doctor::class, 'doctor_id');
        }

        /**
         * Отношение: Услуга.
         */
        public function medicalService(): BelongsTo
        {
            return $this->belongsTo(MedicalService::class, 'service_id');
        }

        /**
         * Отношение: Медицинские записи, созданные во время этого приема.
         */
        public function medicalRecords(): HasMany
        {
            return $this->hasMany(MedicalRecord::class, 'appointment_id');
        }

        /**
         * Проверка: оплачена ли запись полностью.
         */
        public function isFullyPaid(): bool
        {
            return $this->payment_status === 'paid';
        }

        /**
         * Перевод в статус "В процессе".
         */
        public function start(): void
        {
            $this->update([
                'status' => 'in_progress',
                'correlation_id' => $this->correlation_id ?? (string)Str::uuid()
            ]);
        }

        /**
         * Завершение приема.
         */
        public function complete(): void
        {
            $this->update([
                'status' => 'completed',
                'correlation_id' => $this->correlation_id ?? (string)Str::uuid()
            ]);
        }
}
