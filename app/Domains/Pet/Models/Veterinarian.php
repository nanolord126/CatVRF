<?php declare(strict_types=1);

namespace App\Domains\Pet\Models;

use Illuminate\Http\Request;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class Veterinarian extends Model
{
    use HasFactory;

        protected $table = 'veterinarians';

        /**
         * Поля, доступные для массового заполнения.
         */
        protected $fillable = [
            'uuid',
            'tenant_id',
            'clinic_id',
            'full_name',
            'specialization',
            'experience_years',
            'education',
            'rating',
            'is_active',
            'correlation_id',
            'tags',
        ];

        /**
         * Приведение типов.
         */
        protected $casts = [
            'specialization' => 'json',
            'education' => 'json',
            'tags' => 'json',
            'rating' => 'float',
            'is_active' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];

        /**
         * Инициализация модели.
         */
        protected static function booted_disabled(): void
        {
            // Global scope для изоляции тенантов
            static::addGlobalScope('tenant', function ($query) {
                if (function_exists('tenant') && tenant()) {
                    $query->where('tenant_id', tenant()->id);
                }
            });

            // Автогенерация UUID и correlation_id
            static::creating(function (Veterinarian $model) {
                $model->uuid = $model->uuid ?? (string) Str::uuid();
                $model->correlation_id = $model->correlation_id ?? $this->request->header('X-Correlation-ID', (string) Str::uuid());

                if (function_exists('tenant') && tenant()) {
                    $model->tenant_id = $model->tenant_id ?? tenant()->id;
                }
            });
        }

        /**
         * Клиника, к которой привязан специалист.
         */
        public function clinic(): BelongsTo
        {
            return $this->belongsTo(PetClinic::class, 'clinic_id');
        }

        /**
         * Записи на прием к этому врачу.
         */
        public function appointments(): HasMany
        {
            return $this->hasMany(PetAppointment::class, 'veterinarian_id');
        }

        /**
         * Проверка специалиста на наличие конкретной специализации.
         */
        public function hasSpecialization(string $spec): bool
        {
            $specs = $this->specialization ?? [];
            return in_array($spec, $specs);
        }

        /**
         * Получение человекочитаемого списка специализаций.
         */
        public function getSpecializationsString(): string
        {
            return implode(', ', $this->specialization ?? []);
        }

        /**
         * Проверка опытности сотрудника.
         */
        public function isSenior(): bool
        {
            return $this->experience_years >= 10;
        }

        /**
         * Получить краткое описание доктора.
         */
        public function getShortBio(): string
        {
            return sprintf(
                '%s, Опыт %d лет. Специализация: %s',
                $this->full_name,
                $this->experience_years,
                $this->getSpecializationsString()
            );
        }

        /**
         * Получить активных врачей.
         */
        public function scopeActive($query)
        {
            return $query->where('is_active', true);
        }
}
