<?php declare(strict_types=1);

namespace App\Domains\Medical\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class Doctor extends Model
{
    use HasFactory;

    use SoftDeletes, LogsActivity;

        protected $table = 'medical_doctors';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'clinic_id',
            'user_id',
            'full_name',
            'specialization',
            'experience_years',
            'degree',
            'status',
            'rating',
            'consultation_price',
            'schedule_config',
            'metadata',
            'tags',
            'correlation_id'
        ];

        protected $casts = [
            'specialization' => 'array',
            'schedule_config' => 'array',
            'metadata' => 'array',
            'tags' => 'array',
            'rating' => 'float',
            'consultation_price' => 'integer'
        ];

        /**
         * КАНОН: Global Scopes и События модели.
         */
        protected static function booted_disabled(): void
        {
            static::creating(function (Doctor $doctor) {
                $doctor->uuid = $doctor->uuid ?? (string)Str::uuid();
                $doctor->tenant_id = $doctor->tenant_id ?? (int)tenant()->id;
                $doctor->correlation_id = $doctor->correlation_id ?? (string)Str::uuid();
            });

            static::addGlobalScope('tenant_id', function (Builder $builder) {
                if (tenant()) {
                    $builder->where('tenant_id', tenant()->id);
                }
            });
        }

        /**
         * Настройка логов для ФЗ-152 и аудита.
         */
        public function getActivitylogOptions(): LogOptions
        {
            return LogOptions::defaults()
                ->logOnly(['full_name', 'status', 'specialization', 'consultation_price'])
                ->logOnlyDirty()
                ->useLogName('medical_doctor_audit');
        }

        /**
         * Отношение: Клиника врача.
         */
        public function clinic(): BelongsTo
        {
            return $this->belongsTo(Clinic::class, 'clinic_id');
        }

        /**
         * Отношение: Пользователь в системе (для входа).
         */
        public function user(): BelongsTo
        {
            return $this->belongsTo(User::class, 'user_id');
        }

        /**
         * Отношение: Записи к врачу.
         */
        public function appointments(): HasMany
        {
            return $this->hasMany(Appointment::class, 'doctor_id');
        }

        /**
         * Отношение: Созданные врачом медицинские записи.
         */
        public function records(): HasMany
        {
            return $this->hasMany(MedicalRecord::class, 'doctor_id');
        }

        /**
         * Отношение: Отзывы о враче.
         */
        public function reviews(): HasMany
        {
            return $this->hasMany(Review::class, 'doctor_id');
        }

        /**
         * Проверка доступности врача в конкретные часы.
         */
        public function isAvailableAt(\Carbon\Carbon $datetime): bool
        {
            // Базовая эмуляция логики расписания (Layer 2)
            $dayOfWeek = $datetime->format('l');
            $hours = $this->schedule_config['days'][$dayOfWeek] ?? null;

            if (!$hours || $this->status !== 'active') {
                return false;
            }

            // Проверка пересечения с существующими записями
            return !$this->appointments()
                ->where('appointment_at', $datetime)
                ->whereIn('status', ['confirmed', 'in_progress'])
                ->exists();
        }
}
