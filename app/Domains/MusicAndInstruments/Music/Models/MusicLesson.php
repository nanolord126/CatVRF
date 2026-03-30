<?php declare(strict_types=1);

namespace App\Domains\MusicAndInstruments\Music\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class MusicLesson extends Model
{
    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasFactory;

        protected $table = 'music_lessons';

        protected $fillable = [
            'uuid',
            'music_store_id',
            'tenant_id',
            'correlation_id',
            'teacher_name',
            'subject',
            'level',
            'duration_minutes',
            'price_cents',
            'format',
            'tags',
        ];

        protected $casts = [
            'tags' => 'array',
            'price_cents' => 'integer',
            'duration_minutes' => 'integer',
        ];

        protected static function booted(): void
        {
            static::creating(function ($model) {
                $model->uuid = $model->uuid ?? (string) Str::uuid();
                $model->correlation_id = $model->correlation_id ?? request()->header('X-Correlation-ID', (string) Str::uuid());

                if (empty($model->tenant_id) && function_exists('tenant')) {
                    $model->tenant_id = tenant()->id ?? 'null';
                }
            });

            static::addGlobalScope('tenant', function ($builder) {
                if (function_exists('tenant') && tenant()) {
                    $builder->where('music_lessons.tenant_id', tenant()->id);
                }
            });
        }

        public function store(): BelongsTo
        {
            return $this->belongsTo(MusicStore::class, 'music_store_id');
        }

        public function bookings(): \Illuminate\Database\Eloquent\Relations\MorphMany
        {
            return $this->morphMany(MusicBooking::class, 'bookable');
        }
}
