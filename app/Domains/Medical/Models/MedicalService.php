<?php declare(strict_types=1);

namespace App\Domains\Medical\Models;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class MedicalService extends Model
{
    use HasFactory;

    use SoftDeletes, LogsActivity;

        protected $table = 'medical_services';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'clinic_id',
            'name',
            'category_name',
            'description',
            'duration_minutes',
            'price_kopecks',
            'is_active',
            'consumables_json',
            'age_limit_min',
            'age_limit_max',
            'required_prepayment_percentage',
            'metadata',
            'tags',
            'correlation_id'
        ];

        protected $casts = [
            'is_active' => 'boolean',
            'price_kopecks' => 'integer',
            'duration_minutes' => 'integer',
            'age_limit_min' => 'integer',
            'age_limit_max' => 'integer',
            'required_prepayment_percentage' => 'integer',
            'consumables_json' => 'array',
            'metadata' => 'array',
            'tags' => 'array',
        ];

        /**
         * КАНОН: Global Scopes и События модели.
         */
        protected static function booted_disabled(): void
        {
            static::addGlobalScope('tenant', function ($query) {
                $query->where('tenant_id', tenant()->id ?? 0);
            });
        }

        /**
         * Настройка логов для аудита и ФЗ-152.
         */
        public function getActivitylogOptions(): LogOptions
        {
            return LogOptions::defaults()
                ->logOnly(['name', 'base_price', 'is_active', 'age_limit_min', 'required_prepayment_percentage'])
                ->logOnlyDirty()
                ->useLogName('medical_service_audit');
        }

        /**
         * Отношение: Клиника, предоставляющая услугу.
         */
        public function clinic(): BelongsTo
        {
            return $this->belongsTo(Clinic::class, 'clinic_id');
        }

        /**
         * Отношение: Записи на данную услугу.
         */
        public function appointments(): HasMany
        {
            return $this->hasMany(Appointment::class, 'service_id');
        }

        /**
         * Проверка: соответствие возрасту.
         */
        public function checkAgeLimits(int $age): bool
        {
            if ($this->age_limit_min && $age < $this->age_limit_min) {
                return false;
            }

            if ($this->age_limit_max && $age > $this->age_limit_max) {
                return false;
            }

            return true;
        }

        /**
         * Расчёт обязательной предоплаты.
         */
        public function calculateRequiredPrepayment(): int
        {
            if (!$this->required_prepayment_percentage) {
                return 0;
            }

            return (int)($this->base_price * ($this->required_prepayment_percentage / 100));
        }

        /**
         * Отношение: Расходники, привязанные к услуге.
         */
        public function consumables(): HasMany
        {
            return $this->hasMany(MedicalConsumable::class, 'service_id');
        }
}
