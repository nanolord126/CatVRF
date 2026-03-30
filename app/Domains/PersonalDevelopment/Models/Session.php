<?php declare(strict_types=1);

namespace App\Domains\PersonalDevelopment\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class Session extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected $table = 'pd_sessions';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'coach_id',
            'client_id',
            'scheduled_at',
            'duration_minutes',
            'status',
            'video_link',
            'notes_after',
            'amount_kopecks',
            'correlation_id',
        ];

        protected $hidden = [
            'id',
            'created_at',
            'updated_at',
        ];

        protected $casts = [
            'scheduled_at' => 'datetime',
            'duration_minutes' => 'integer',
            'amount_kopecks' => 'integer',
            'tenant_id' => 'integer',
            'coach_id' => 'integer',
            'client_id' => 'integer',
        ];

        /**
         * Booted method for global scoping and UUID generation.
         */
        protected static function booted(): void
        {
            // Изоляция данных на уровне базы (Tenant Scoping)
            static::addGlobalScope('tenant', function (Builder $builder) {
                if (function_exists('tenant') && tenant('id')) {
                    $builder->where('tenant_id', tenant('id'));
                }
            });

            // Автогенерация UUID и Correlation ID
            static::creating(function (Session $model) {
                if (empty($model->uuid)) {
                    $model->uuid = (string) Str::uuid();
                }
                if (empty($model->correlation_id)) {
                    $model->correlation_id = (string) Str::uuid();
                }
                if (empty($model->tenant_id) && function_exists('tenant')) {
                    $model->tenant_id = (int) tenant('id');
                }
            });
        }

        /**
         * Преподаватель (коуч), проводящий сессию.
         */
        public function coach(): BelongsTo
        {
            return $this->belongsTo(Coach::class, 'coach_id');
        }

        /**
         * Клиент, проходящий обучение.
         */
        public function client(): BelongsTo
        {
            /** @var \App\Models\User $userModel */
            return $this->belongsTo(\App\Models\User::class, 'client_id');
        }

        /**
         * Завершение сессии с добавлением заметок.
         */
        public function complete(string $notes): void
        {
            $this->update([
                'status' => 'completed',
                'notes_after' => $notes,
                'correlation_id' => (string) Str::uuid()
            ]);
        }

        /**
         * Генерация ссылки на видеозвонок.
         */
        public function generateVideoLink(): void
        {
            $this->update([
                'video_link' => 'https://meet.jit.si/' . Str::slug($this->coach->name) . '-' . $this->uuid,
                'correlation_id' => (string) Str::uuid()
            ]);
        }
}
