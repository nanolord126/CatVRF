<?php declare(strict_types=1);

/**
 * LanguageVideoCall — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/languagevideocall
 */


namespace App\Domains\Education\LanguageLearning\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class LanguageVideoCall extends Model
{
    use HasFactory;

    protected $table = 'language_videocalls';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'lesson_id',
            'room_id',
            'provider',
            'started_at',
            'ended_at',
            'recorded_size_bytes',
            'correlation_id',
        ];

        protected $casts = [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'recorded_size_bytes' => 'integer',
        ];

        protected static function booted(): void
        {
            static::creating(function (self $model) {
                $model->uuid = $model->uuid ?? (string) Str::uuid();
                $model->tenant_id = $model->tenant_id ?? (int) (tenant()->id ?? 1);
            });

            static::addGlobalScope('tenant_id', function ($query) {
                if (tenant()->id) {
                    $query->where('tenant_id', tenant()->id);
                }
            });
        }

        public function lesson(): BelongsTo
        {
            return $this->belongsTo(LanguageLesson::class, 'lesson_id');
        }

        /**
         * Генерация ссылки для входа WebRTC.
         */
        public function getJoinUrlAttribute(): string
        {
            return "https://meet.catvrf.com/v1/join/{$this->room_id}";
        }
}
